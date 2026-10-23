<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Bearer token the mobile app sends on every API request. Only a SHA-256 hash is stored.
 */
class ApiToken extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'token',
        'last_used_at',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Create a token for the user and return the plain-text value, which is never stored.
     */
    public static function issue(User $user, string $name = 'mobile'): string
    {
        $plainText = Str::random(60);

        $user->apiTokens()->create([
            'name' => $name,
            'token' => hash('sha256', $plainText),
        ]);

        return $plainText;
    }

    public static function findByPlainText(?string $plainText): ?self
    {
        if (blank($plainText)) {
            return null;
        }

        return static::query()->with('user')->where('token', hash('sha256', $plainText))->first();
    }
}
