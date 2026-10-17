<?php

namespace App\Http\Controllers\Maid;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Support\BookingWorkflow;
use App\Support\SiteSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GigController extends Controller
{
    public function __construct(public BookingWorkflow $workflow, public SiteSettings $settings) {}

    /**
     * List funded open gigs, with gigs in the maid's service area first.
     */
    public function index(Request $request): View
    {
        $maidArea = $request->user()->maidProfile?->service_area;

        $gigs = Job::query()
            ->availableForMaids()
            ->with('service')
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%'.$request->string('q').'%';
                $query->where(fn ($q) => $q->where('title', 'like', $term)->orWhere('location', 'like', $term));
            })
            ->latest()
            ->get();

        [$areaGigs, $otherGigs] = $maidArea
            ? $gigs->partition(fn (Job $job): bool => str_contains(strtolower((string) $job->location), strtolower($maidArea)))
            : [collect(), $gigs];

        return view('maid.gigs.index', [
            'areaGigs' => $areaGigs,
            'otherGigs' => $otherGigs,
            'maidArea' => $maidArea,
            'canClaim' => $this->canClaim($request),
        ]);
    }

    public function show(Request $request, Job $job): View
    {
        $isMine = $job->maid_id === $request->user()->id;

        abort_unless($isMine || ($job->isOpen() && $job->isFunded()), 404);

        $job->load(['client', 'service', 'payout']);

        return view('maid.gigs.show', [
            'job' => $job,
            'isMine' => $isMine,
            'canClaim' => $this->canClaim($request),
        ]);
    }

    public function claim(Request $request, Job $job): RedirectResponse
    {
        if (! $this->canClaim($request)) {
            return back()->with('error', 'Your ID must be verified by our team before you can claim gigs.');
        }

        if (! $job->isOpen() || ! $job->isFunded()) {
            return back()->with('error', 'Sorry, this gig is no longer available.');
        }

        $this->workflow->assignMaid($job, $request->user());

        return redirect()
            ->route('maid.gigs.show', $job)
            ->with('success', 'Gig claimed! The client contact details are now visible.');
    }

    public function release(Request $request, Job $job): RedirectResponse
    {
        $this->ensureAssigned($request, $job);

        if (! $job->isMatched()) {
            return back()->with('error', 'You cannot release a gig that is already in progress or completed.');
        }

        $this->workflow->releaseMaid($job);

        return redirect()
            ->route('maid.dashboard')
            ->with('success', 'Gig released. It is now available for other maids.');
    }

    public function start(Request $request, Job $job): RedirectResponse
    {
        $this->ensureAssigned($request, $job);

        if (! $job->isMatched()) {
            return back()->with('error', 'This gig cannot be started.');
        }

        $this->workflow->start($job);

        return back()->with('success', 'Job started. Mark it complete when you finish.');
    }

    public function complete(Request $request, Job $job): RedirectResponse
    {
        $this->ensureAssigned($request, $job);

        if (! $job->isInProgress()) {
            return back()->with('error', 'Only jobs in progress can be marked complete.');
        }

        $this->workflow->complete($job);

        return back()->with('success', 'Great work! The client has been asked to approve so your payout can be released.');
    }

    protected function ensureAssigned(Request $request, Job $job): void
    {
        abort_unless($job->maid_id === $request->user()->id, 403, 'You can only manage gigs assigned to you.');
    }

    protected function canClaim(Request $request): bool
    {
        return ! $this->settings->bool('require_maid_verification') || $request->user()->isVerifiedMaid();
    }
}
