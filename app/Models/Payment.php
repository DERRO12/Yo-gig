<?php

namespace App\Models;

use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_SUCCESSFUL = 'successful';

    public const STATUS_FAILED = 'failed';

    public const STATUS_REFUNDED = 'refunded';

    /**
     * Payment methods clients can choose, keyed by stored value.
     *
     * @var array<string, string>
     */
    public const METHODS = [
        'mtn_momo' => 'MTN Mobile Money',
        'airtel_money' => 'Airtel Money',
        'bank_transfer' => 'Bank Transfer',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'job_id',
        'client_id',
        'amount',
        'method',
        'provider',
        'payer_phone',
        'transaction_reference',
        'status',
        'paid_at',
        'verified_by',
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

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function methodLabel(): string
    {
        return self::METHODS[$this->method] ?? ucfirst(str_replace('_', ' ', $this->method));
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
