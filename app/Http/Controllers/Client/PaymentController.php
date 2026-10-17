<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\Payment;
use App\Support\BookingWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public function __construct(public BookingWorkflow $workflow) {}

    public function store(Request $request, Job $job): RedirectResponse
    {
        abort_unless($job->client_id === $request->user()->id, 403);

        if (! $job->needsPayment()) {
            return back()->with('error', 'This booking does not need a payment right now.');
        }

        $validated = $request->validate([
            'method' => ['required', Rule::in(array_keys(Payment::METHODS))],
            'payer_phone' => ['nullable', 'required_unless:method,bank_transfer', 'string', 'max:20'],
            'transaction_reference' => ['required', 'string', 'max:100'],
        ]);

        $payment = $this->workflow->submitPayment($job, $validated);

        $message = $payment->status === Payment::STATUS_SUCCESSFUL
            ? 'Payment confirmed! Your money is held in escrow and maids can now take your gig.'
            : 'Payment submitted. We will confirm it shortly and then open your gig to maids.';

        return back()->with('success', $message);
    }
}
