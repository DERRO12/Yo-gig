<?php

namespace App\Http\Controllers\Maid;

use App\Http\Controllers\Controller;
use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GigController extends Controller
{
    /**
     * List all open gigs.
     * Sort: same service area first, then newest.
     */
    public function index()
    {
        $maid = Auth::user();
        $maidArea = optional($maid->maidProfile)->service_area;

        // Query 1: Gigs in maid's service area (if set)
        $areaGigs = collect();
        if ($maidArea) {
            $areaGigs = Job::where('status', 'open')
                ->where('location', 'like', '%' . $maidArea . '%')
                ->with('client')
                ->latest()
                ->get();
        }

        // Query 2: All other open gigs
        $areaGigIds = $areaGigs->pluck('id')->toArray();
        $otherGigs = Job::where('status', 'open')
            ->whereNotIn('id', $areaGigIds)
            ->with('client')
            ->latest()
            ->get();

        // My assigned gigs
        $myJobs = Job::where('maid_id', $maid->id)
            ->with('client')
            ->latest()
            ->get();

        return view('maid.gigs.index', compact('areaGigs', 'otherGigs', 'myJobs', 'maidArea'));
    }

    /**
     * Show a single gig's details (including images).
     */
    public function show(Job $job)
    {
        // Only show open gigs or gigs already claimed by this maid
        if ($job->status !== 'open' && $job->maid_id !== Auth::id()) {
            abort(404);
        }

        $job->load('client', 'assignedMaid');

        return view('maid.gigs.show', compact('job'));
    }

    /**
     * Claim an open gig as the current maid.
     */
    public function claim(Job $job)
    {
        // Sanity checks
        if ($job->status !== 'open') {
            return back()->with('error', 'Sorry, this gig is no longer available.');
        }

        if ($job->client_id === Auth::id()) {
            return back()->with('error', 'You cannot claim your own gig.');
        }

        $job->update([
            'maid_id'    => Auth::id(),
            'status'     => 'matched',
            'claimed_at' => now(),
        ]);

        return redirect()
            ->route('maid.dashboard')
            ->with('success', 'Gig claimed! Client contact details are now visible below.');
    }

    /**
     * Release a gig — return it to the open pool.
     */
    public function release(Job $job)
    {
        if ($job->maid_id !== Auth::id()) {
            abort(403, 'You can only release gigs assigned to you.');
        }

        if (in_array($job->status, ['in_progress', 'completed'])) {
            return back()->with('error', 'You cannot release a gig that is already in progress or completed.');
        }

        $job->update([
            'maid_id'    => null,
            'status'     => 'open',
            'claimed_at' => null,
        ]);

        return redirect()
            ->route('maid.dashboard')
            ->with('success', 'Gig released. It is now available for other maids.');
    }
}