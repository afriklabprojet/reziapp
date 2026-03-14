<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuidebookRecommendation extends Model
{
    const CATEGORY_RESTAURANT  = 'restaurant';
    const CATEGORY_CAFE        = 'cafe';
    const CATEGORY_SHOPPING    = 'shopping';
    const CATEGORY_ACTIVITY    = 'activity';
    const CATEGORY_PHARMACY    = 'pharmacy';
    const CATEGORY_SUPERMARKET = 'supermarket';
    const CATEGORY_TRANSPORT   = 'transport';

    const CATEGORIES = [
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
