<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Support\BookingWorkflow;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PaymentController extends Controller
{
    public function __construct(public BookingWorkflow $workflow) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $status = $request->string('status', Payment::STATUS_PENDING)->toString();

        return PaymentResource::collection(
            Payment::query()
                ->with(['job', 'client', 'verifier'])
                ->when($status !== 'all', fn ($query) => $query->where('status', $status))
                ->latest()
                ->paginate(20)
                ->withQueryString()
        )->additional([
            'counts' => Payment::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function confirm(Request $request, Payment $payment): PaymentResource
    {
        abort_unless($payment->isPending(), 422, 'Only pending payments can be confirmed.');

        $this->workflow->confirmPayment($payment, $request->user());

        return PaymentResource::make($payment->refresh()->load(['job', 'client', 'verifier']))
            ->additional(['message' => 'Payment confirmed. The gig is now open to maids.']);
    }

    public function reject(Request $request, Payment $payment): PaymentResource
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        abort_unless($payment->isPending(), 422, 'Only pending payments can be rejected.');

        $this->workflow->rejectPayment($payment, $request->user(), $validated['notes'] ?? null);

        return PaymentResource::make($payment->refresh()->load(['job', 'client', 'verifier']))
            ->additional(['message' => 'Payment rejected. The client has been asked to pay again.']);
    }
}
