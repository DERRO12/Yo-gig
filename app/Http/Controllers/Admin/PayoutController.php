<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payout;
use App\Support\BookingWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayoutController extends Controller
{
    public function __construct(public BookingWorkflow $workflow) {}

    public function index(Request $request): View
    {
        $status = $request->string('status', Payout::STATUS_PENDING)->toString();

        return view('admin.payouts.index', [
            'status' => $status,
            'payouts' => Payout::query()
                ->with(['job', 'maid', 'processor'])
                ->when($status !== 'all', fn ($query) => $query->where('status', $status))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'counts' => Payout::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function markPaid(Request $request, Payout $payout): RedirectResponse
    {
        $validated = $request->validate([
            'reference' => ['required', 'string', 'max:100'],
        ]);

        if ($payout->status === Payout::STATUS_PAID) {
            return back()->with('error', 'This payout has already been paid.');
        }

        $this->workflow->markPayoutPaid($payout, $request->user(), $validated['reference']);

        return back()->with('success', 'Payout marked as paid.');
    }

    public function markFailed(Request $request, Payout $payout): RedirectResponse
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        if ($payout->status === Payout::STATUS_PAID) {
            return back()->with('error', 'A paid payout cannot be marked as failed.');
        }

        $payout->update([
            'status' => Payout::STATUS_FAILED,
            'notes' => $validated['notes'] ?? null,
            'processed_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Payout marked as failed.');
    }
}
