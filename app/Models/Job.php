<?php

namespace App\Models;

use App\Support\Media;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Job extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'open';

    public const STATUS_MATCHED = 'matched';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * @var array<string, string>
     */
    public const STATUSES = [
        self::STATUS_OPEN => 'Open',
        self::STATUS_MATCHED => 'Maid assigned',
        self::STATUS_IN_PROGRESS => 'In progress',
        self::STATUS_COMPLETED => 'Completed',
        self::STATUS_CANCELLED => 'Cancelled',
    ];

    public const PAYMENT_UNPAID = 'unpaid';

    public const PAYMENT_PENDING = 'pending';

    public const PAYMENT_PAID = 'paid';

    public const PAYMENT_RELEASED = 'released';

    public const PAYMENT_REFUND_DUE = 'refund_due';

    public const PAYMENT_REFUNDED = 'refunded';

    /**
     * @var array<string, string>
     */
    public const PAYMENT_STATUSES = [
        self::PAYMENT_UNPAID => 'Awaiting payment',
        self::PAYMENT_PENDING => 'Payment being verified',
        self::PAYMENT_PAID => 'Held in escrow',
        self::PAYMENT_RELEASED => 'Released to maid',
        self::PAYMENT_REFUND_DUE => 'Refund due',
        self::PAYMENT_REFUNDED => 'Refunded',
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'cleaning_jobs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'reference',
        'client_id',
        'maid_id',
        'service_id',
        'title',
        'description',
        'status',
        'payment_status',
        'scheduled_date',
        'scheduled_time',
        'location',
        'budget',
        'quantity',
        'platform_fee',
        'maid_earnings',
        'images',
        'claimed_at',
        'started_at',
        'completed_at',
        'client_approved_at',
        'cancelled_at',
        'client_rating',
        'client_review',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'scheduled_date' => 'date',
        'scheduled_time' => 'datetime:H:i',
        'budget' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'maid_earnings' => 'decimal:2',
        'images' => 'array',
        'claimed_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'client_approved_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Job $job): void {
            $job->reference ??= static::generateReference();
            $job->payment_status ??= self::PAYMENT_UNPAID;
        });
    }

    public static function generateReference(): string
    {
        do {
            $reference = 'YG-'.strtoupper(Str::random(6));
        } while (static::query()->where('reference', $reference)->exists());

        return $reference;
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * The client (user) who posted the job.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    /**
     * The maid (user) assigned to this job.
     */
    public function assignedMaid(): BelongsTo
    {
        return $this->belongsTo(User::class, 'maid_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest();
    }

    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function payout(): HasOne
    {
        return $this->hasOne(Payout::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Open gigs whose payment is secured in escrow, so maids can claim them.
     *
     * @param  Builder<Job>  $query
     */
    public function scopeAvailableForMaids(Builder $query): void
    {
        $query->where('status', self::STATUS_OPEN)
            ->where('payment_status', self::PAYMENT_PAID);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function isMatched(): bool
    {
        return $this->status === self::STATUS_MATCHED;
    }

    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isFunded(): bool
    {
        return $this->payment_status === self::PAYMENT_PAID;
    }

    public function needsPayment(): bool
    {
        return in_array($this->payment_status, [self::PAYMENT_UNPAID], true)
            && ! $this->isCancelled();
    }

    public function isAwaitingClientApproval(): bool
    {
        return $this->isCompleted() && $this->client_approved_at === null;
    }

    public function canBeCancelledByClient(): bool
    {
        return in_array($this->status, [self::STATUS_OPEN, self::STATUS_MATCHED], true);
    }

    public function statusLabel(): string
    {
        if ($this->isAwaitingClientApproval()) {
            return 'Awaiting approval';
        }

        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function paymentStatusLabel(): string
    {
        return self::PAYMENT_STATUSES[$this->payment_status] ?? ucfirst((string) $this->payment_status);
    }

    /**
     * @return list<string>
     */
    public function imageUrls(): array
    {
        return collect($this->images ?? [])
            ->map(fn (string $path): ?string => Media::url($path))
            ->filter()
            ->values()
            ->all();
    }

    public function scheduleLabel(): ?string
    {
        if (! $this->scheduled_date) {
            return null;
        }

        $label = $this->scheduled_date->format('D, M j, Y');

        if ($this->scheduled_time) {
            $label .= ' · '.$this->scheduled_time->format('g:i A');
        }

        return $label;
    }
}
