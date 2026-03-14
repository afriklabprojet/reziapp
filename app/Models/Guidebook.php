<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Guidebook extends Model
{
    protected $fillable = [
        'residence_id', 'user_id', 'title', 'welcome_message',
        'wifi_name', 'wifi_password', 'house_rules_details',
        'parking_info', 'transport_info', 'emergency_info',
        'checkout_instructions', 'access_token', 'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($guidebook) {
            if (empty($guidebook->access_token)) {
                $guidebook->access_token = Str::random(32);
            }
        });
    }

    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(GuidebookSection::class)->orderBy('sort_order');
    }

    public function recommendations(): HasMany
    {
        return $this->hasMany(GuidebookRecommendation::class);
    }

    public function getPublicUrlAttribute(): string
    {
        return url("/guidebook/{$this->access_token}");
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeForResidence($query, int $residenceId)
    {
        return $query->where('residence_id', $residenceId);
    }
}
