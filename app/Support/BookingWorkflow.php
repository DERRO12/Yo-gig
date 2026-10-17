<?php

namespace App\Support;

use App\Models\Job;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Money and status transitions for a booking.
 *
 * Clients pay the platform, the platform holds the money in escrow, and once
 * the client approves the finished job the maid's share becomes a payout.
 */
class BookingWorkflow
{
    public function __construct(public SiteSettings $settings) {}

    /**
     * Record a payment a client says they made for a booking.
     *
     * @param  array{method: string, payer_phone?: string|null, transaction_reference: string}  $details
     */
    public function submitPayment(Job $job, array $details): Payment
    {
        if (! $job->needsPayment()) {
            throw new InvalidArgumentException('This booking does not need a payment.');
        }

        return DB::transaction(function () use ($job, $details): Payment {
            $payment = $job->payments()->create([
                'client_id' => $job->client_id,
                'amount' => $job->budget,
                'method' => $details['method'],
                'provider' => Payment::METHODS[$details['method']] ?? null,
                'payer_phone' => $details['payer_phone'] ?? null,
                'transaction_reference' => $details['transaction_reference'],
                'status' => Payment::STATUS_PENDING,
            ]);

            $job->update(['payment_status' => Job::PAYMENT_PENDING]);

            if ($this->settings->bool('auto_confirm_payments')) {
                $this->confirmPayment($payment);
            }

            return $payment->refresh();
        });
    }

    /**
     * Mark a payment as received: the money is now held in escrow.
     */
    public function confirmPayment(Payment $payment, ?User $admin = null): void
    {
        DB::transaction(function () use ($payment, $admin): void {
            $payment->update([
                'status' => Payment::STATUS_SUCCESSFUL,
                'paid_at' => now(),
                'verified_by' => $admin?->id,
            ]);

            $job = $payment->job;
            $fee = round((float) $job->budget * $this->settings->commissionRate() / 100, 2);

            $job->update([
                'payment_status' => Job::PAYMENT_PAID,
                'platform_fee' => $fee,
                'maid_earnings' => (float) $job->budget - $fee,
            ]);
        });
    }

    public function rejectPayment(Payment $payment, User $admin, ?string $notes = null): void
    {
        DB::transaction(function () use ($payment, $admin, $notes): void {
            $payment->update([
                'status' => Payment::STATUS_FAILED,
                'verified_by' => $admin->id,
                'notes' => $notes,
            ]);

            $payment->job->update(['payment_status' => Job::PAYMENT_UNPAID]);
        });
    }

    public function assignMaid(Job $job, User $maid): void
    {
        $job->update([
            'maid_id' => $maid->id,
            'status' => Job::STATUS_MATCHED,
            'claimed_at' => now(),
        ]);
    }

    public function releaseMaid(Job $job): void
    {
        $job->update([
            'maid_id' => null,
            'status' => Job::STATUS_OPEN,
            'claimed_at' => null,
        ]);
    }

    public function start(Job $job): void
    {
        $job->update([
            'status' => Job::STATUS_IN_PROGRESS,
            'started_at' => now(),
        ]);
    }

    public function complete(Job $job): void
    {
        $job->update([
            'status' => Job::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }

    /**
     * The client signs off on the work: escrow is released and the maid's payout is queued.
     */
    public function approve(Job $job, ?int $rating = null, ?string $review = null): Payout
    {
        if (! $job->isAwaitingClientApproval()) {
            throw new InvalidArgumentException('Only completed jobs awaiting approval can be approved.');
        }

        return DB::transaction(function () use ($job, $rating, $review): Payout {
            $job->update([
                'client_approved_at' => now(),
                'payment_status' => Job::PAYMENT_RELEASED,
                'client_rating' => $rating,
                'client_review' => $review,
            ]);

            $maid = $job->assignedMaid;
            $profile = $maid?->maidProfile;

            if ($profile) {
                $profile->increment('completed_jobs');

                $average = Job::query()
                    ->where('maid_id', $maid->id)
                    ->whereNotNull('client_rating')
                    ->avg('client_rating');

                $profile->update(['average_rating' => $average]);
            }

            return Payout::query()->firstOrCreate(
                ['job_id' => $job->id],
                [
                    'maid_id' => $job->maid_id,
                    'amount' => $job->maid_earnings ?? $job->budget,
                    'method' => $profile?->payout_method,
                    'account_name' => $profile?->payout_account_name,
                    'account_number' => $profile?->payout_account_number,
                    'status' => Payout::STATUS_PENDING,
                ]
            );
        });
    }

    public function cancel(Job $job): void
    {
        $paymentStatus = match ($job->payment_status) {
            Job::PAYMENT_PAID, Job::PAYMENT_PENDING => Job::PAYMENT_REFUND_DUE,
            default => $job->payment_status,
        };

        $job->update([
            'status' => Job::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'payment_status' => $paymentStatus,
        ]);
    }

    public function markRefunded(Job $job): void
    {
        DB::transaction(function () use ($job): void {
            $job->payments()
                ->whereIn('status', [Payment::STATUS_SUCCESSFUL, Payment::STATUS_PENDING])
                ->update(['status' => Payment::STATUS_REFUNDED]);

            $job->update(['payment_status' => Job::PAYMENT_REFUNDED]);
        });
    }

    public function markPayoutPaid(Payout $payout, User $admin, string $reference): void
    {
        $payout->update([
            'status' => Payout::STATUS_PAID,
            'reference' => $reference,
            'paid_at' => now(),
            'processed_by' => $admin->id,
        ]);
    }
}
