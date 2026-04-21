<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuidebookRecommendation extends Model
{
    public const CATEGORY_RESTAURANT  = 'restaurant';
    public const CATEGORY_CAFE        = 'cafe';
    public const CATEGORY_SHOPPING    = 'shopping';
    public const CATEGORY_ACTIVITY    = 'activity';
    public const CATEGORY_PHARMACY    = 'pharmacy';
    public const CATEGORY_SUPERMARKET = 'supermarket';
    public const CATEGORY_TRANSPORT   = 'transport';

    public const CATEGORIES = [
        self::CATEGORY_RESTAURANT  => 'Restaurant',
        self::CATEGORY_CAFE        => 'Café',
        self::CATEGORY_SHOPPING    => 'Shopping',
        self::CATEGORY_ACTIVITY    => 'Activité',
        self::CATEGORY_PHARMACY    => 'Pharmacie',
        self::CATEGORY_SUPERMARKET => 'Supermarché',
        self::CATEGORY_TRANSPORT   => 'Transport',
    ];

    protected $fillable = [
        'guidebook_id', 'category', 'name', 'description',
        'address', 'phone', 'website', 'latitude', 'longitude',
        'photo', 'sort_order',
    ];

    protected $casts = [
        'latitude'  => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function guidebook(): BelongsTo
    {
        return $this->belongsTo(Guidebook::class);
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }
}
