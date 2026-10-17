<?php

namespace App\Models;

use App\Support\Media;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class PageSection extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'page',
        'section',
        'badge',
        'title',
        'body',
        'image_path',
        'button_text',
        'button_url',
        'secondary_button_text',
        'secondary_button_url',
        'sort_order',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Active sections of a page grouped by section key.
     *
     * @return \Illuminate\Support\Collection<string, Collection<int, PageSection>>
     */
    public static function forPage(string $page): \Illuminate\Support\Collection
    {
        return static::query()
            ->where('page', $page)
            ->active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->groupBy('section');
    }

    /**
     * @param  Builder<PageSection>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function imageUrl(?string $fallback = null): ?string
    {
        return Media::url($this->image_path, $fallback);
    }

    /**
     * Resolve stored button links: "#book", "/services" or full URLs.
     */
    public static function link(?string $url): string
    {
        if (blank($url)) {
            return '#';
        }

        if (str_starts_with($url, 'http') || str_starts_with($url, '#') || str_starts_with($url, 'mailto:') || str_starts_with($url, 'tel:')) {
            return $url;
        }

        return url($url);
    }
}
