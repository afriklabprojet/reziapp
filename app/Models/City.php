<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class City extends Model
{
    protected $fillable = [
        'country_id', 'name', 'slug', 'latitude', 'longitude',
        'is_active', 'sort_order',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /* ── Boot ── */

    protected static function boot()
    {
        parent::boot();

        static::creating(function (City $city) {
            if (empty($city->slug)) {
                $city->slug = Str::slug($city->name);
            }
        });
    }

    /* ── Relations ── */

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function communes(): HasMany
    {
        return $this->hasMany(CommuneList::class);
    }

    /* ── Scopes ── */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
