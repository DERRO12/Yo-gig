<?php

namespace App\Http\Resources;

use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A client booking (stored as a cleaning job), shown to clients, maids and admins.
 *
 * @mixin Job
 */
class BookingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'status_label' => $this->statusLabel(),
            'payment_status' => $this->payment_status,
            'payment_status_label' => $this->paymentStatusLabel(),
            'scheduled_date' => $this->scheduled_date?->toDateString(),
            'scheduled_time' => $this->scheduled_time?->format('H:i'),
            'schedule_label' => $this->scheduleLabel(),
            'location' => $this->location,
            'quantity' => (int) ($this->quantity ?? 1),
            'budget' => (float) $this->budget,
            'platform_fee' => $this->platform_fee !== null ? (float) $this->platform_fee : null,
            'maid_earnings' => $this->maid_earnings !== null ? (float) $this->maid_earnings : null,
            'image_urls' => $this->imageUrls(),
            'needs_payment' => $this->needsPayment(),
            'is_funded' => $this->isFunded(),
            'is_awaiting_client_approval' => $this->isAwaitingClientApproval(),
            'can_be_cancelled' => $this->canBeCancelledByClient(),
            'client_rating' => $this->client_rating,
            'client_review' => $this->client_review,
            'claimed_at' => $this->claimed_at?->toIso8601String(),
            'started_at' => $this->started_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'client_approved_at' => $this->client_approved_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'service' => ServiceResource::make($this->whenLoaded('service')),
            'client' => UserResource::make($this->whenLoaded('client')),
            'maid' => UserResource::make($this->whenLoaded('assignedMaid')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'latest_payment' => PaymentResource::make($this->whenLoaded('latestPayment')),
            'payout' => PayoutResource::make($this->whenLoaded('payout')),
        ];
    }
}
