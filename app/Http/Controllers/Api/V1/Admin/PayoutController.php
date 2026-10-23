<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\PayoutResource;
use App\Models\Payout;
use App\Support\BookingWorkflow;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PayoutController extends Controller
{
    public function __construct(public BookingWorkflow $workflow) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $status = $request->string('status', Payout::STATUS_PENDING)->toString();

        return PayoutResource::collection(
            Payout::query()
                ->with(['job', 'maid', 'processor'])
                ->when($status !== 'all', fn ($query) => $query->where('status', $status))
                ->latest()
                ->paginate(20)
                ->withQueryString()
        )->additional([
            'counts' => Payout::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function markPaid(Request $request, Payout $payout): PayoutResource
    {
        $validated = $request->validate([
            'reference' => ['required', 'string', 'max:100'],
        ]);

        abort_if($payout->status === Payout::STATUS_PAID, 422, 'This payout has already been paid.');

        $this->workflow->markPayoutPaid($payout, $request->user(), $validated['reference']);

        return PayoutResource::make($payout->refresh()->load(['job', 'maid', 'processor']))
            ->additional(['message' => 'Payout marked as paid.']);
    }

    public function markFailed(Request $request, Payout $payout): PayoutResource
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        abort_if($payout->status === Payout::STATUS_PAID, 422, 'A paid payout cannot be marked as failed.');

        $payout->update([
            'status' => Payout::STATUS_FAILED,
            'notes' => $validated['notes'] ?? null,
            'processed_by' => $request->user()->id,
        ]);

        return PayoutResource::make($payout->refresh()->load(['job', 'maid', 'processor']))
            ->additional(['message' => 'Payout marked as failed.']);
    }
}
