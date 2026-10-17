<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Models\Job;
use App\Models\Payment;
use App\Models\Service;
use App\Support\BookingWorkflow;
use App\Support\Media;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function __construct(public BookingWorkflow $workflow) {}

    public function create(Request $request): View
    {
        return view('client.bookings.create', [
            'services' => Service::query()->active()->ordered()->get(),
            'selectedServiceId' => $request->integer('service') ?: null,
        ]);
    }

    public function store(StoreBookingRequest $request): RedirectResponse
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

        return redirect()
            ->route('client.bookings.show', $job)
            ->with('success', 'Booking created! Complete the payment below so a maid can take your gig.');
    }

    public function show(Request $request, Job $job): View
    {
        $this->ensureOwner($request, $job);

        $job->load(['assignedMaid.maidProfile', 'service', 'payments']);

        return view('client.bookings.show', [
            'job' => $job,
            'paymentMethods' => Payment::METHODS,
        ]);
    }

    public function approve(Request $request, Job $job): RedirectResponse
    {
        $this->ensureOwner($request, $job);

        $validated = $request->validate([
            'rating' => ['nullable', 'integer', 'between:1,5'],
            'review' => ['nullable', 'string', 'max:1000'],
        ]);

        if (! $job->isAwaitingClientApproval()) {
            return back()->with('error', 'This booking is not waiting for your approval.');
        }

        $this->workflow->approve($job, $validated['rating'] ?? null, $validated['review'] ?? null);

        return back()->with('success', 'Thank you! The payment has been released and the maid will be paid.');
    }

    public function cancel(Request $request, Job $job): RedirectResponse
    {
        $this->ensureOwner($request, $job);

        if (! $job->canBeCancelledByClient()) {
            return back()->with('error', 'This booking can no longer be cancelled.');
        }

        $this->workflow->cancel($job);

        $message = $job->payment_status === Job::PAYMENT_REFUND_DUE
            ? 'Booking cancelled. Your payment will be refunded by our team.'
            : 'Booking cancelled.';

        return redirect()->route('client.dashboard')->with('success', $message);
    }

    protected function ensureOwner(Request $request, Job $job): void
    {
        abort_unless($job->client_id === $request->user()->id, 403);
    }
}
