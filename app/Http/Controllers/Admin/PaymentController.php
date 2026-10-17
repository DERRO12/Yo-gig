<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Support\BookingWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(public BookingWorkflow $workflow) {}

    public function index(Request $request): View
    {
        $status = $request->string('status', Payment::STATUS_PENDING)->toString();

        return view('admin.payments.index', [
            'status' => $status,
            'payments' => Payment::query()
                ->with(['job', 'client', 'verifier'])
                ->when($status !== 'all', fn ($query) => $query->where('status', $status))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'counts' => Payment::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function confirm(Request $request, Payment $payment): RedirectResponse
    {
        if (! $payment->isPending()) {
            return back()->with('error', 'Only pending payments can be confirmed.');
        }

        $this->workflow->confirmPayment($payment, $request->user());

        return back()->with('success', 'Payment confirmed. The gig is now open to maids.');
    }

    public function reject(Request $request, Payment $payment): RedirectResponse
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        if (! $payment->isPending()) {
            return back()->with('error', 'Only pending payments can be rejected.');
        }

        $this->workflow->rejectPayment($payment, $request->user(), $validated['notes'] ?? null);

        return back()->with('success', 'Payment rejected. The client has been asked to pay again.');
    }
}
