<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone_number',
        'role',
        'is_active',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Role Helpers
    |--------------------------------------------------------------------------
    */

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isClient(): bool
    {
        return $this->role === 'client';
    }

    public function isMaid(): bool
    {
        return $this->role === 'maid';
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Maid profile (only exists if role === 'maid').
     */
    public function maidProfile()
    {
        return $this->hasOne(MaidProfile::class);
    }

    /**
     * Jobs posted by this user (as a client).
     */
    public function jobsAsClient()
    {
        return $this->hasMany(Job::class, 'client_id');
    }

    /**
     * Jobs assigned to this user (as a maid).
     */
    public function jobsAsMaid()
    {
        return $this->hasMany(Job::class, 'maid_id');
    }

    /**
     * Payments made by this user (as a client).
     */
    public function payments()
    {
        return $this->hasMany(Payment::class, 'client_id');
    }

    /**
     * Payouts sent to this user (as a maid).
     */
    public function payouts()
    {
        return $this->hasMany(Payout::class, 'maid_id');
    }

    /**
     * Bearer tokens issued to the mobile app.
     */
    public function apiTokens(): HasMany
    {
        return $this->hasMany(ApiToken::class);
    }

    public function isVerifiedMaid(): bool
    {
        return $this->isMaid() && $this->maidProfile?->verification_status === 'verified';
    }

    /**
     * The login field a credential refers to: users sign in with an email address or a phone number.
     */
    public static function loginField(string $login): string
    {
        return filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone_number';
    }

    /**
     * Accounts created outside the app (e.g. phpMyAdmin with an online bcrypt tool) may carry
     * "$2a$"/"$2b$" hashes, which Laravel's hasher rejects. Re-hash them on a correct password.
     */
    public static function upgradeLegacyPasswordHash(string $login, string $password): void
    {
        $user = static::query()->where(static::loginField($login), $login)->first();
        $hash = $user?->getAuthPassword();

        if (! $hash || ! preg_match('/^\$2[ab]\$/', $hash) || ! password_verify($password, $hash)) {
            return;
        }

        $user->forceFill(['password' => Hash::make($password)])->save();
    }

    public function dashboardRoute(): string
    {
        return match ($this->role) {
            'admin' => 'admin.dashboard',
            'maid' => 'maid.dashboard',
            default => 'client.dashboard',
        };
    }
}
