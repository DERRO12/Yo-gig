<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Http\Resources\UserResource;
use App\Models\Job;
use App\Models\User;
use App\Support\BookingWorkflow;
use App\Support\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    public function __construct(public BookingWorkflow $workflow) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return BookingResource::collection(
            Job::query()
                ->with(['client', 'assignedMaid', 'service'])
                ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
                ->when($request->filled('payment'), fn ($query) => $query->where('payment_status', $request->string('payment')))
                ->when($request->filled('q'), function ($query) use ($request): void {
                    $term = '%'.$request->string('q').'%';
                    $query->where(fn ($q) => $q->where('reference', 'like', $term)
                        ->orWhere('title', 'like', $term)
                        ->orWhere('location', 'like', $term)
                        ->orWhereHas('client', fn ($c) => $c->where('name', 'like', $term)));
                })
                ->latest()
                ->paginate(20)
                ->withQueryString()
        );
    }

    public function show(Job $job): BookingResource
    {
        return $this->detail($job);
    }

    public function assign(Request $request, Job $job): BookingResource
    {
        $validated = $request->validate([
            'maid_id' => ['required', Rule::exists('users', 'id')->where('role', 'maid')],
        ]);

        abort_unless(in_array($job->status, [Job::STATUS_OPEN, Job::STATUS_MATCHED], true), 422, 'Maids can only be assigned to open or matched bookings.');

        $this->workflow->assignMaid($job, User::query()->findOrFail($validated['maid_id']));

        return $this->detail($job->refresh(), 'Maid assigned to booking.');
    }

    /**
     * Admin overrides for disputes and support: move a booking through the escrow flow.
     */
    public function updateStatus(Request $request, Job $job): BookingResource
    {
        $validated = $request->validate([
            'action' => ['required', Rule::in(['start', 'complete', 'approve', 'cancel', 'refund', 'unassign'])],
        ]);

        [$allowed, $error] = match ($validated['action']) {
            'start' => [$job->isMatched(), 'Only matched bookings can be started.'],
            'complete' => [$job->isInProgress(), 'Only bookings in progress can be completed.'],
            'approve' => [$job->isAwaitingClientApproval(), 'Only completed bookings awaiting approval can be released.'],
            'cancel' => [! $job->isCompleted() && ! $job->isCancelled(), 'This booking cannot be cancelled.'],
            'refund' => [$job->payment_status === Job::PAYMENT_REFUND_DUE, 'This booking has no refund due.'],
            'unassign' => [$job->isMatched(), 'Only matched bookings can be unassigned.'],
        };

        abort_unless($allowed, 422, $error);

        match ($validated['action']) {
            'start' => $this->workflow->start($job),
            'complete' => $this->workflow->complete($job),
            'approve' => $this->workflow->approve($job),
            'cancel' => $this->workflow->cancel($job),
            'refund' => $this->workflow->markRefunded($job),
            'unassign' => $this->workflow->releaseMaid($job),
        };

        return $this->detail($job->refresh(), 'Booking updated.');
    }

    public function destroy(Job $job): JsonResponse
    {
        foreach ($job->images ?? [] as $path) {
            Media::delete($path);
        }

        $job->delete();

        return response()->json(['message' => 'Booking deleted.']);
    }

    /**
     * The booking with its money trail and the verified maids it can be assigned to.
     */
    protected function detail(Job $job, ?string $message = null): BookingResource
    {
        $job->load(['client', 'assignedMaid.maidProfile', 'service', 'payments.verifier', 'payout']);

        $maids = User::query()
            ->where('role', 'maid')
            ->where('is_active', true)
            ->whereHas('maidProfile', fn ($q) => $q->where('verification_status', 'verified'))
            ->orderBy('name')
            ->get();

        return BookingResource::make($job)->additional(array_filter([
            'assignable_maids' => UserResource::collection($maids),
            'message' => $message,
        ]));
    }
}
