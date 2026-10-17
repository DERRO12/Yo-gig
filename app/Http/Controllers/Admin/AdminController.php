<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\Job;
use App\Models\MaidProfile;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\User;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function dashboard(): View
    {
        $successfulPayments = Payment::query()->where('status', Payment::STATUS_SUCCESSFUL);

        return view('admin.dashboard', [
            'stats' => [
                'revenue' => (clone $successfulPayments)->sum('amount'),
                'commission' => Job::query()->whereIn('payment_status', [Job::PAYMENT_PAID, Job::PAYMENT_RELEASED])->sum('platform_fee'),
                'escrow' => Job::query()->where('payment_status', Job::PAYMENT_PAID)->sum('budget'),
                'pendingPayouts' => Payout::query()->where('status', Payout::STATUS_PENDING)->sum('amount'),
                'paidOut' => Payout::query()->where('status', Payout::STATUS_PAID)->sum('amount'),
                'clients' => User::query()->where('role', 'client')->count(),
                'maids' => User::query()->where('role', 'maid')->count(),
                'bookings' => Job::query()->count(),
            ],
            'statusCounts' => Job::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'todo' => [
                'payments' => Payment::query()->where('status', Payment::STATUS_PENDING)->count(),
                'payouts' => Payout::query()->where('status', Payout::STATUS_PENDING)->count(),
                'maids' => MaidProfile::query()->where('verification_status', 'pending')->count(),
                'refunds' => Job::query()->where('payment_status', Job::PAYMENT_REFUND_DUE)->count(),
                'messages' => ContactMessage::query()->whereNull('read_at')->count(),
            ],
            'recentBookings' => Job::query()->with(['client', 'assignedMaid'])->latest()->take(8)->get(),
        ]);
    }
}
