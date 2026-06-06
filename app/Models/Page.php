<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Page extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'content',
        'excerpt',
        'meta_title',
        'meta_description',
        'type',
        'is_published',
        'sort_order',
        'created_by',
        'updated_by',
        'published_at',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($page) {
            if (empty($page->slug)) {
                $page->slug = Str::slug($page->title);
            }
            if (auth()->check()) {
                $page->created_by = auth()->id();
            }
        });

        static::updating(function ($page) {
            if (auth()->check()) {
                $page->updated_by = auth()->id();
            }
        });
    }

    /**
     * Creator
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Last editor
     */
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope for published pages
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get page by slug
     */
    public static function findBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->published()->first();
    }

    /**
     * Get meta title
     */
    public function getMetaTitleAttribute($value): string
    {
        return $value ?? $this->title.' - ReziApp';
    }

    /**
     * Get URL
     */
    public function getUrlAttribute(): string
    {
        return match ($this->type) {
            'faq' => route('faq'),
            'legal' => route('page.show', $this->slug),
            default => route('page.show', $this->slug),
        };
    }
}
