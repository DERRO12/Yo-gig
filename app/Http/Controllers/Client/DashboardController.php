<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $bookings = Job::query()
            ->where('client_id', $request->user()->id)
            ->with(['assignedMaid', 'service', 'latestPayment'])
            ->latest()
            ->get();

        return view('client.dashboard', [
            'bookings' => $bookings,
            'stats' => [
                'active' => $bookings->whereIn('status', [Job::STATUS_OPEN, Job::STATUS_MATCHED, Job::STATUS_IN_PROGRESS])->count(),
                'awaitingPayment' => $bookings->where('payment_status', Job::PAYMENT_UNPAID)->where('status', '!=', Job::STATUS_CANCELLED)->count(),
                'awaitingApproval' => $bookings->filter->isAwaitingClientApproval()->count(),
                'inEscrow' => $bookings->where('payment_status', Job::PAYMENT_PAID)->sum('budget'),
            ],
        ]);
    }
}
