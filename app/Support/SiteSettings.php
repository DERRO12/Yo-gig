<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;

class SiteSettings
{
    public const CACHE_KEY = 'site_settings';

    /**
     * Saved values keyed by setting key, loaded once per request.
     *
     * @var array<string, string|null>|null
     */
    protected ?array $values = null;

    /**
     * Field definitions flattened from config/site.php.
     *
     * @return array<string, array{label: string, type: string, default: string, group: string}>
     */
    public function definitions(): array
    {
        $definitions = [];

        foreach (config('site.settings', []) as $group => $groupConfig) {
            foreach ($groupConfig['fields'] as $key => $field) {
                $definitions[$key] = $field + ['group' => $group];
            }
        }

        return $definitions;
    }

    /**
     * All saved setting values.
     *
     * @return array<string, string|null>
     */
    public function all(): array
    {
        if ($this->values !== null) {
            return $this->values;
        }

        try {
            return $this->values = Cache::rememberForever(
                self::CACHE_KEY,
                fn (): array => Setting::query()->pluck('value', 'key')->all()
            );
        } catch (QueryException) {
            return $this->values = [];
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->all()[$key] ?? null;

        if ($value !== null && $value !== '') {
            return $value;
        }

        return $default ?? ($this->definitions()[$key]['default'] ?? null);
    }

    public function bool(string $key): bool
    {
        return filter_var($this->get($key), FILTER_VALIDATE_BOOLEAN);
    }

    public function number(string $key): float
    {
        return (float) $this->get($key);
    }

    /**
     * Persist the given values and clear the cache.
     *
     * @param  array<string, string|null>  $values
     */
    public function set(array $values): void
    {
        foreach ($values as $key => $value) {
            Setting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        $this->flush();
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
        $this->values = null;
    }

    public function image(string $key): ?string
    {
        return Media::url($this->get($key));
    }

    public function currency(): string
    {
        return (string) $this->get('currency', 'UGX');
    }

    public function money(float|int|string|null $amount): string
    {
        return $this->currency().' '.number_format((float) $amount);
    }

    public function commissionRate(): float
    {
        return min(100, max(0, $this->number('commission_rate')));
    }
}
