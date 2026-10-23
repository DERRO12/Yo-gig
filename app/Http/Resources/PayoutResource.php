<?php

namespace App\Http\Resources;

use App\Models\Payout;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Payout
 */
class PayoutResource extends JsonResource
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
            'job_id' => $this->job_id,
            'amount' => (float) $this->amount,
            'method' => $this->method,
            'method_label' => $this->methodLabel(),
            'account_name' => $this->account_name,
            'account_number' => $this->account_number,
            'status' => $this->status,
            'reference' => $this->reference,
            'notes' => $this->notes,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'booking' => BookingResource::make($this->whenLoaded('job')),
            'maid' => UserResource::make($this->whenLoaded('maid')),
            'processor' => UserResource::make($this->whenLoaded('processor')),
        ];
    }
}
