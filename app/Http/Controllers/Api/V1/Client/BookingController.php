<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Job;
use App\Models\Payment;
use App\Support\BookingWorkflow;
use App\Support\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    public function __construct(public BookingWorkflow $workflow) {}

    public function dashboard(Request $request): JsonResponse
    {
        $bookings = Job::query()
            ->where('client_id', $request->user()->id)
            ->with(['assignedMaid', 'service', 'latestPayment'])
            ->latest()
            ->get();

        return response()->json([
            'stats' => [
                'active' => $bookings->whereIn('status', [Job::STATUS_OPEN, Job::STATUS_MATCHED, Job::STATUS_IN_PROGRESS])->count(),
                'awaiting_payment' => $bookings->where('payment_status', Job::PAYMENT_UNPAID)->where('status', '!=', Job::STATUS_CANCELLED)->count(),
                'awaiting_approval' => $bookings->filter->isAwaitingClientApproval()->count(),
                'in_escrow' => (float) $bookings->where('payment_status', Job::PAYMENT_PAID)->sum('budget'),
            ],
            'bookings' => BookingResource::collection($bookings),
        ]);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        return BookingResource::collection(
            Job::query()
                ->where('client_id', $request->user()->id)
                ->with(['assignedMaid', 'service', 'latestPayment'])
                ->latest()
                ->get()
        );
    }

    public function store(StoreBookingRequest $request): JsonResponse
    {
        $service = $request->service();

        $imagePaths = [];
        foreach ($request->file('images', []) as $image) {
            $imagePaths[] = Media::store($image, 'job_images');
        }

        $job = Job::query()->create([
            'client_id' => $request->user()->id,
            'service_id' => $service->id,
            'title' => $request->input('title') ?: $service->name,
            'location' => $request->input('location'),
            'description' => $request->input('description'),
            'quantity' => $request->integer('quantity'),
            'budget' => $request->totalAmount(),
            'scheduled_date' => $request->input('scheduled_date'),
            'scheduled_time' => $request->input('scheduled_time'),
            'images' => $imagePaths,
            'status' => Job::STATUS_OPEN,
        ]);

        return BookingResource::make($job->refresh()->load('service'))
            ->additional(['message' => 'Booking created! Complete the payment so a maid can take your gig.'])
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Job $job): BookingResource
    {
        $this->ensureOwner($request, $job);

        return BookingResource::make($job->load(['assignedMaid.maidProfile', 'service', 'payments']));
    }

    public function approve(Request $request, Job $job): BookingResource
    {
        $this->ensureOwner($request, $job);

        $validated = $request->validate([
            'rating' => ['nullable', 'integer', 'between:1,5'],
            'review' => ['nullable', 'string', 'max:1000'],
        ]);

        abort_unless($job->isAwaitingClientApproval(), 422, 'This booking is not waiting for your approval.');

        $this->workflow->approve($job, $validated['rating'] ?? null, $validated['review'] ?? null);

        return $this->fresh($job, 'Thank you! The payment has been released and the maid will be paid.');
    }

    public function cancel(Request $request, Job $job): BookingResource
    {
        $this->ensureOwner($request, $job);

        abort_unless($job->canBeCancelledByClient(), 422, 'This booking can no longer be cancelled.');

        $this->workflow->cancel($job);

        return $this->fresh($job, $job->payment_status === Job::PAYMENT_REFUND_DUE
            ? 'Booking cancelled. Your payment will be refunded by our team.'
            : 'Booking cancelled.');
    }

    public function pay(Request $request, Job $job): BookingResource
    {
        $this->ensureOwner($request, $job);

        abort_unless($job->needsPayment(), 422, 'This booking does not need a payment right now.');

        $validated = $request->validate([
            'method' => ['required', Rule::in(array_keys(Payment::METHODS))],
            'payer_phone' => ['nullable', 'required_unless:method,bank_transfer', 'string', 'max:20'],
            'transaction_reference' => ['required', 'string', 'max:100'],
        ]);

        $payment = $this->workflow->submitPayment($job, $validated);

        return $this->fresh($job, $payment->status === Payment::STATUS_SUCCESSFUL
            ? 'Payment confirmed! Your money is held in escrow and maids can now take your gig.'
            : 'Payment submitted. We will confirm it shortly and then open your gig to maids.');
    }

    protected function fresh(Job $job, string $message): BookingResource
    {
        return BookingResource::make($job->refresh()->load(['assignedMaid.maidProfile', 'service', 'payments']))
            ->additional(['message' => $message]);
    }

    protected function ensureOwner(Request $request, Job $job): void
    {
        abort_unless($job->client_id === $request->user()->id, 403);
    }
}
