<?php

namespace App\Http\Resources;

use App\Models\MaidProfile;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MaidProfile
 */
class MaidProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'verification_status' => $this->verification_status,
            'is_verified' => $this->isVerified(),
            'national_id_number' => $this->national_id_number,
            'has_national_id_photo' => filled($this->national_id_photo_path),
            'bio' => $this->bio,
            'service_area' => $this->service_area,
            'skills' => $this->skills ?? [],
            'available' => (bool) $this->available,
            'completed_jobs' => (int) $this->completed_jobs,
            'average_rating' => $this->average_rating !== null ? (float) $this->average_rating : null,
            'payout_method' => $this->payout_method,
            'payout_method_label' => $this->payout_method ? (Payment::METHODS[$this->payout_method] ?? $this->payout_method) : null,
            'payout_account_name' => $this->payout_account_name,
            'payout_account_number' => $this->payout_account_number,
            'has_payout_details' => $this->hasPayoutDetails(),
        ];
    }
}
