<?php

namespace App\Http\Controllers\Maid;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\Payout;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $maid = $request->user()->load('maidProfile');

        $myJobs = Job::query()
            ->where('maid_id', $maid->id)
            ->with(['client', 'service'])
            ->latest('scheduled_date')
            ->get();

        $payouts = Payout::query()->where('maid_id', $maid->id)->get();

        return view('maid.dashboard', [
            'maid' => $maid,
            'activeJobs' => $myJobs->whereIn('status', [Job::STATUS_MATCHED, Job::STATUS_IN_PROGRESS]),
            'awaitingApproval' => $myJobs->filter->isAwaitingClientApproval(),
            'recentGigs' => Job::query()->availableForMaids()->with('service')->latest()->take(4)->get(),
            'stats' => [
                'openGigs' => Job::query()->availableForMaids()->count(),
                'activeJobs' => $myJobs->whereIn('status', [Job::STATUS_MATCHED, Job::STATUS_IN_PROGRESS])->count(),
                'completedJobs' => $myJobs->where('status', Job::STATUS_COMPLETED)->count(),
                'pendingPayouts' => $payouts->where('status', Payout::STATUS_PENDING)->sum('amount'),
                'totalEarned' => $payouts->where('status', Payout::STATUS_PAID)->sum('amount'),
            ],
        ]);
    }
}
