<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Models\ContactMessage;
use App\Models\Job;
use App\Models\MaidProfile;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'stats' => [
                'revenue' => (float) Payment::query()->where('status', Payment::STATUS_SUCCESSFUL)->sum('amount'),
                'commission' => (float) Job::query()->whereIn('payment_status', [Job::PAYMENT_PAID, Job::PAYMENT_RELEASED])->sum('platform_fee'),
                'escrow' => (float) Job::query()->where('payment_status', Job::PAYMENT_PAID)->sum('budget'),
                'pending_payouts' => (float) Payout::query()->where('status', Payout::STATUS_PENDING)->sum('amount'),
                'paid_out' => (float) Payout::query()->where('status', Payout::STATUS_PAID)->sum('amount'),
                'clients' => User::query()->where('role', 'client')->count(),
                'maids' => User::query()->where('role', 'maid')->count(),
                'bookings' => Job::query()->count(),
            ],
            'status_counts' => Job::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'todo' => [
                'payments' => Payment::query()->where('status', Payment::STATUS_PENDING)->count(),
                'payouts' => Payout::query()->where('status', Payout::STATUS_PENDING)->count(),
                'maids' => MaidProfile::query()->where('verification_status', 'pending')->count(),
                'refunds' => Job::query()->where('payment_status', Job::PAYMENT_REFUND_DUE)->count(),
                'messages' => ContactMessage::query()->whereNull('read_at')->count(),
            ],
            'recent_bookings' => BookingResource::collection(Job::query()->with(['client', 'assignedMaid'])->latest()->take(8)->get()),
        ]);
    }
}
