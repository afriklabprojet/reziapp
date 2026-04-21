<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CommuneList extends Model
{
    protected $table = 'communes_list';

    protected $fillable = [
        'city_id', 'name', 'slug', 'latitude', 'longitude', 'is_active',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'is_active' => 'boolean',
    ];

    /* ── Boot ── */

    protected static function boot()
    {
        parent::boot();

        static::creating(function (CommuneList $commune) {
            if (empty($commune->slug)) {
                $commune->slug = Str::slug($commune->name);
            }
        });
    }

    /* ── Relations ── */

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /* ── Scopes ── */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
