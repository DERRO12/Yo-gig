<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaidProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'national_id_number',
        'national_id_photo_path',
        'verification_status',
        'bio',
        'service_area',
        'skills',
        'available',
        'completed_jobs',
        'average_rating',
        'payout_method',
        'payout_account_name',
        'payout_account_number',
    ];

    protected $casts = [
        'skills' => 'array',
        'available' => 'boolean',
        'average_rating' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isVerified(): bool
    {
        return $this->verification_status === 'verified';
    }

    public function hasPayoutDetails(): bool
    {
        return filled($this->payout_method) && filled($this->payout_account_number);
    }
}
