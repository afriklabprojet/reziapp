<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SeoData extends Model
{
    protected $table = 'seo_data';

    protected $fillable = [
        'seoable_type',
        'seoable_id',
        'route_name',
        'url_pattern',
        'page_type',
        'locale',
        'meta_title',
        'meta_description',
        'keywords',
        'og_data',
        'structured_data',
        'canonical_url',
        'is_noindex',
        'is_nofollow',
        'priority',
        // virtual (handled via og_data / structured_data)
        'og_title',
        'og_description',
        'og_image',
        'og_type',
        'schema_json',
    ];

    protected $casts = [
        'keywords'         => 'array',
        'og_data'          => 'array',
        'structured_data'  => 'array',
        'is_noindex'       => 'boolean',
        'is_nofollow'      => 'boolean',
        'priority'         => 'decimal:1',
    ];

    // ===== RELATIONSHIPS =====

    public function seoable(): MorphTo
    {
        return $this->morphTo();
    }

    // ===== SCOPES =====

    public function scopeForLocale($query, string $locale)
    {
        return $query->where('locale', $locale);
    }

    public function scopeForRoute($query, string $routeName)
    {
        return $query->where('route_name', $routeName);
    }

    // ===== VIRTUAL ATTRIBUTES (og_data sub-fields) =====

    protected function ogTitle(): Attribute
    {
        return Attribute::make(
            get: fn () => ($this->og_data ?? [])['title'] ?? ($this->og_data ?? [])['og:title'] ?? null,
            set: function (?string $value) {
                $data = $this->og_data ?? [];
                $data['title'] = $value;

                return ['og_data' => $data];
            },
        );
    }

    protected function ogDescription(): Attribute
    {
        return Attribute::make(
            get: fn () => ($this->og_data ?? [])['description'] ?? ($this->og_data ?? [])['og:description'] ?? null,
            set: function (?string $value) {
                $data = $this->og_data ?? [];
                $data['description'] = $value;

                return ['og_data' => $data];
            },
        );
    }

    protected function ogImage(): Attribute
    {
        return Attribute::make(
            get: fn () => ($this->og_data ?? [])['image'] ?? ($this->og_data ?? [])['og:image'] ?? null,
            set: function (?string $value) {
                $data = $this->og_data ?? [];
                $data['image'] = $value;

                return ['og_data' => $data];
            },
        );
    }

    protected function ogType(): Attribute
    {
        return Attribute::make(
            get: fn () => ($this->og_data ?? [])['og:type'] ?? ($this->og_data ?? [])['type'] ?? 'website',
            set: function (?string $value) {
                $data = $this->og_data ?? [];
                $data['og:type'] = $value ?? 'website';

                return ['og_data' => $data];
            },
        );
    }

    protected function schemaJson(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->structured_data
                ? json_encode($this->structured_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                : null,
            set: function (?string $value) {
                if (blank($value)) {
                    return ['structured_data' => null];
                }
                $decoded = json_decode($value, true);

                return ['structured_data' => $decoded ?: null];
            },
        );
    }

    // ===== STATIC HELPERS =====

    public static function forModel(Model $model, string $locale = 'fr'): ?self
    {
        return self::where('seoable_type', get_class($model))
            ->where('seoable_id', $model->id)
            ->where('locale', $locale)
            ->first();
    }

    public static function forPage(string $routeName, string $locale = 'fr'): ?self
    {
        return self::where('route_name', $routeName)
            ->where('locale', $locale)
            ->first();
    }

    // ===== OUTPUT HELPERS =====

    public function toMetaTags(): string
    {
        $tags = [];

        if ($this->meta_title) {
            $tags[] = '<title>'.e($this->meta_title).'</title>';
            $tags[] = '<meta name="title" content="'.e($this->meta_title).'">';
        }

        if ($this->meta_description) {
            $tags[] = '<meta name="description" content="'.e($this->meta_description).'">';
        }

        if ($this->keywords && count($this->keywords) > 0) {
            $tags[] = '<meta name="keywords" content="'.e(implode(', ', $this->keywords)).'">';
        }

        // Robots
        $robots = [];
        if ($this->is_noindex) {
            $robots[] = 'noindex';
        }
        if ($this->is_nofollow) {
            $robots[] = 'nofollow';
        }
        if ($robots) {
            $tags[] = '<meta name="robots" content="'.e(implode(', ', $robots)).'">';
        }

        if ($this->canonical_url) {
            $tags[] = '<link rel="canonical" href="'.e($this->canonical_url).'">';
        }

        // Open Graph
        if ($this->og_title) {
            $tags[] = '<meta property="og:title" content="'.e($this->og_title).'">';
        }
        if ($this->og_description) {
            $tags[] = '<meta property="og:description" content="'.e($this->og_description).'">';
        }
        if ($this->og_image) {
            $tags[] = '<meta property="og:image" content="'.e($this->og_image).'">';
        }
        $tags[] = '<meta property="og:type" content="'.e($this->og_type).'">';
        $tags[] = '<meta property="og:locale" content="'.e($this->locale ?? 'fr_CI').'">';

        // Twitter Card
        $tags[] = '<meta name="twitter:card" content="summary_large_image">';
        if ($this->og_title) {
            $tags[] = '<meta name="twitter:title" content="'.e($this->og_title).'">';
        }
        if ($this->og_description) {
            $tags[] = '<meta name="twitter:description" content="'.e($this->og_description).'">';
        }
        if ($this->og_image) {
            $tags[] = '<meta name="twitter:image" content="'.e($this->og_image).'">';
        }

        return implode("\n    ", $tags);
    }

    public function toJsonLd(): string
    {
        if (! $this->structured_data) {
            return '';
        }

        return '<script type="application/ld+json">'.json_encode(
            $this->structured_data,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        ).'</script>';
    }
}
