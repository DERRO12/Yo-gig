<?php

namespace App\Http\Controllers\Api\V1\Maid;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Http\Resources\UserResource;
use App\Models\Job;
use App\Models\Payout;
use App\Support\BookingWorkflow;
use App\Support\SiteSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class GigController extends Controller
{
    public function __construct(public BookingWorkflow $workflow, public SiteSettings $settings) {}

    public function dashboard(Request $request): JsonResponse
    {
        $maid = $request->user()->load('maidProfile');

        $myJobs = Job::query()
            ->where('maid_id', $maid->id)
            ->with(['client', 'service'])
            ->latest('scheduled_date')
            ->get();

        $payouts = Payout::query()->where('maid_id', $maid->id)->get();
        $activeJobs = $myJobs->whereIn('status', [Job::STATUS_MATCHED, Job::STATUS_IN_PROGRESS]);

        return response()->json([
            'maid' => UserResource::make($maid),
            'can_claim' => $this->canClaim($request),
            'stats' => [
                'open_gigs' => Job::query()->availableForMaids()->count(),
                'active_jobs' => $activeJobs->count(),
                'completed_jobs' => $myJobs->where('status', Job::STATUS_COMPLETED)->count(),
                'pending_payouts' => (float) $payouts->where('status', Payout::STATUS_PENDING)->sum('amount'),
                'total_earned' => (float) $payouts->where('status', Payout::STATUS_PAID)->sum('amount'),
            ],
            'active_jobs' => BookingResource::collection($activeJobs->values()),
            'awaiting_approval' => BookingResource::collection($myJobs->filter->isAwaitingClientApproval()->values()),
            'recent_gigs' => BookingResource::collection(Job::query()->availableForMaids()->with('service')->latest()->take(4)->get()),
        ]);
    }

    /**
     * Funded open gigs, with gigs in the maid's service area flagged and listed first.
     */
    public function index(Request $request): JsonResponse
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

        return response()->json([
            'service_area' => $maidArea,
            'can_claim' => $this->canClaim($request),
            'area_gigs' => BookingResource::collection($areaGigs->values()),
            'other_gigs' => BookingResource::collection($otherGigs->values()),
        ]);
    }

    /**
     * Every gig assigned to the maid, newest first.
     */
    public function mine(Request $request): AnonymousResourceCollection
    {
        return BookingResource::collection(
            Job::query()
                ->where('maid_id', $request->user()->id)
                ->with(['client', 'service', 'payout'])
                ->latest('scheduled_date')
                ->get()
        );
    }

    public function show(Request $request, Job $job): BookingResource
    {
        $isMine = $job->maid_id === $request->user()->id;

        abort_unless($isMine || ($job->isOpen() && $job->isFunded()), 404);

        // Client contact details are only shared once the maid has taken the gig.
        $job->load($isMine ? ['client', 'service', 'payout'] : ['service']);

        return BookingResource::make($job)->additional([
            'is_mine' => $isMine,
            'can_claim' => $this->canClaim($request),
        ]);
    }

    public function claim(Request $request, Job $job): BookingResource
    {
        abort_unless($this->canClaim($request), 422, 'Your ID must be verified by our team before you can claim gigs.');
        abort_unless($job->isOpen() && $job->isFunded(), 422, 'Sorry, this gig is no longer available.');

        $this->workflow->assignMaid($job, $request->user());

        return $this->fresh($request, $job, 'Gig claimed! The client contact details are now visible.');
    }

    public function release(Request $request, Job $job): BookingResource
    {
        $this->ensureAssigned($request, $job);

        abort_unless($job->isMatched(), 422, 'You cannot release a gig that is already in progress or completed.');

        $this->workflow->releaseMaid($job);

        return BookingResource::make($job->refresh()->load('service'))
            ->additional(['message' => 'Gig released. It is now available for other maids.', 'is_mine' => false]);
    }

    public function start(Request $request, Job $job): BookingResource
    {
        $this->ensureAssigned($request, $job);

        abort_unless($job->isMatched(), 422, 'This gig cannot be started.');

        $this->workflow->start($job);

        return $this->fresh($request, $job, 'Job started. Mark it complete when you finish.');
    }

    public function complete(Request $request, Job $job): BookingResource
    {
        $this->ensureAssigned($request, $job);

        abort_unless($job->isInProgress(), 422, 'Only jobs in progress can be marked complete.');

        $this->workflow->complete($job);

        return $this->fresh($request, $job, 'Great work! The client has been asked to approve so your payout can be released.');
    }

    protected function fresh(Request $request, Job $job, string $message): BookingResource
    {
        return BookingResource::make($job->refresh()->load(['client', 'service', 'payout']))
            ->additional(['message' => $message, 'is_mine' => true, 'can_claim' => $this->canClaim($request)]);
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
