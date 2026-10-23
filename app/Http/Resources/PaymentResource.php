<?php

namespace App\Http\Resources;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Payment
 */
class PaymentResource extends JsonResource
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
            'payer_phone' => $this->payer_phone,
            'transaction_reference' => $this->transaction_reference,
            'status' => $this->status,
            'notes' => $this->notes,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'booking' => BookingResource::make($this->whenLoaded('job')),
            'client' => UserResource::make($this->whenLoaded('client')),
            'verifier' => UserResource::make($this->whenLoaded('verifier')),
        ];
    }
}
