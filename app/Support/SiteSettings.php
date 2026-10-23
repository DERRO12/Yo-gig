<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
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

    /**
     * Validation rules for a settings group's fields.
     *
     * @param  array<string, array{type: string}>  $fields
     * @return array<string, list<string>>
     */
    public function validationRules(array $fields): array
    {
        $rules = [];

        foreach ($fields as $key => $field) {
            $rules[$key] = match ($field['type']) {
                'email' => ['nullable', 'email', 'max:255'],
                'url' => ['nullable', 'url', 'max:255'],
                'number' => ['nullable', 'numeric', 'min:0'],
                'boolean' => ['nullable', 'boolean'],
                'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
                'textarea' => ['nullable', 'string', 'max:5000'],
                default => ['nullable', 'string', 'max:255'],
            };
        }

        return $rules;
    }

    /**
     * Values to save for a settings group, storing uploaded images and keeping the current one otherwise.
     *
     * @param  array<string, array{type: string}>  $fields
     * @return array<string, string|null>
     */
    public function valuesFromRequest(Request $request, array $fields): array
    {
        $values = [];

        foreach ($fields as $key => $field) {
            $values[$key] = match ($field['type']) {
                'boolean' => $request->boolean($key) ? '1' : '0',
                'image' => $request->hasFile($key)
                    ? Media::replace($this->all()[$key] ?? null, $request->file($key), 'branding')
                    : ($this->all()[$key] ?? null),
                default => $request->input($key),
            };
        }

        return $values;
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
