<?php

namespace App\Http\Controllers\Api\V1\Maid;

use App\Http\Controllers\Controller;
use App\Http\Resources\PayoutResource;
use App\Http\Resources\UserResource;
use App\Models\Payment;
use App\Models\Payout;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EarningsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $maid = $request->user()->load('maidProfile');

        $payouts = Payout::query()
            ->where('maid_id', $maid->id)
            ->with('job')
            ->latest()
            ->get();

        return response()->json([
            'maid' => UserResource::make($maid),
            'payout_methods' => Payment::METHODS,
            'totals' => [
                'paid' => (float) $payouts->where('status', Payout::STATUS_PAID)->sum('amount'),
                'pending' => (float) $payouts->where('status', Payout::STATUS_PENDING)->sum('amount'),
                'count' => $payouts->count(),
            ],
            'payouts' => PayoutResource::collection($payouts),
        ]);
    }

    public function updatePayoutDetails(Request $request): UserResource
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

        return UserResource::make($request->user()->load('maidProfile'))
            ->additional(['message' => 'Payout details saved.']);
    }
}
