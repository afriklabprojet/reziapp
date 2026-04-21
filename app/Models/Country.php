<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    protected $fillable = [
        'name', 'code', 'phone_code', 'currency',
        'latitude', 'longitude',
        'min_lat', 'max_lat', 'min_lng', 'max_lng',
        'is_active',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'min_lat' => 'float',
        'max_lat' => 'float',
        'min_lng' => 'float',
        'max_lng' => 'float',
        'is_active' => 'boolean',
    ];

    /* ── Relations ── */

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }

    /* ── Scopes ── */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /* ── Helpers ── */

    /**
     * Global bounding box across all active countries
     */
    public static function globalBounds(): array
    {
        $countries = self::active()->get();

        if ($countries->isEmpty()) {
            // Fallback CI + BF
            return [
                'min_lat' => 4.3,
                'max_lat' => 15.1,
                'min_lng' => -8.6,
                'max_lng' => 2.4,
            ];
        }

        return [
            'min_lat' => $countries->min('min_lat'),
            'max_lat' => $countries->max('max_lat'),
            'min_lng' => $countries->min('min_lng'),
            'max_lng' => $countries->max('max_lng'),
        ];
    }

    /**
     * List of active country codes (for Google Places restriction)
     */
    public static function activeCodes(): array
    {
        return self::active()->pluck('code')->map(fn ($c) => strtolower($c))->toArray();
    }
}
