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
}