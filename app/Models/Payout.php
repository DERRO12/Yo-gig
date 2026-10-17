<?php

namespace App\Models;

use Database\Factories\PayoutFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payout extends Model
{
    /** @use HasFactory<PayoutFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_FAILED = 'failed';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'job_id',
        'maid_id',
        'amount',
        'method',
        'account_name',
        'account_number',
        'status',
        'reference',
        'paid_at',
        'processed_by',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function maid(): BelongsTo
    {
        return $this->belongsTo(User::class, 'maid_id');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function methodLabel(): string
    {
        return Payment::METHODS[$this->method] ?? ($this->method ? ucfirst(str_replace('_', ' ', $this->method)) : 'Not set');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
