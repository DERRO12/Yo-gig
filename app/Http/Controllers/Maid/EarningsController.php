<?php

namespace App\Http\Controllers\Maid;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Payout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EarningsController extends Controller
{
    public function index(Request $request): View
    {
        $maid = $request->user()->load('maidProfile');

        $payouts = Payout::query()
            ->where('maid_id', $maid->id)
            ->with('job')
            ->latest()
            ->get();

        return view('maid.earnings', [
            'maid' => $maid,
            'payouts' => $payouts,
            'payoutMethods' => Payment::METHODS,
            'totals' => [
                'paid' => $payouts->where('status', Payout::STATUS_PAID)->sum('amount'),
                'pending' => $payouts->where('status', Payout::STATUS_PENDING)->sum('amount'),
                'count' => $payouts->count(),
            ],
        ]);
    }

    public function updatePayoutDetails(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'payout_method' => ['required', Rule::in(array_keys(Payment::METHODS))],
            'payout_account_name' => ['required', 'string', 'max:255'],
            'payout_account_number' => ['required', 'string', 'max:50'],
        ]);

        $profile = $request->user()->maidProfile;
        abort_unless($profile !== null, 404);

        $profile->update($validated);

        // Payouts not yet sent go to the newly saved account.
        Payout::query()
            ->where('maid_id', $request->user()->id)
            ->where('status', Payout::STATUS_PENDING)
            ->update([
                'method' => $validated['payout_method'],
                'account_name' => $validated['payout_account_name'],
                'account_number' => $validated['payout_account_number'],
            ]);

        return back()->with('success', 'Payout details saved.');
    }
}
