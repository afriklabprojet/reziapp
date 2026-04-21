<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Promotion extends Model
{
    protected $fillable = [
        'residence_id',
        'user_id',
        'title',
        'description',
        'discount_type',
        'discount_value',
        'free_nights_min',
        'starts_at',
        'ends_at',
        'min_nights',
        'max_uses',
        'uses_count',
        'booking_start',
        'booking_end',
        'is_active',
        'is_featured',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'booking_start' => 'date',
        'booking_end' => 'date',
        'discount_value' => 'decimal:2',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
    ];

    // Relations
    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->where(function ($q) {
                $q->whereNull('max_uses')
                    ->orWhereColumn('uses_count', '<', 'max_uses');
            });
    }

    public function scopeFeatured($query)
    {
        return $query->active()->where('is_featured', true);
    }

    public function scopeForResidence($query, $residenceId)
    {
        return $query->where('residence_id', $residenceId);
    }

    // Helpers
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }
        if ($this->starts_at > now()) {
            return false;
        }
        if ($this->ends_at < now()) {
            return false;
        }
        if ($this->max_uses && $this->uses_count >= $this->max_uses) {
            return false;
        }

        return true;
    }

    public function isValidForDates(?Carbon $checkIn, ?Carbon $checkOut): bool
    {
        if (!$this->isValid()) {
            return false;
        }

        if ($this->booking_start && $checkIn && $checkIn < $this->booking_start) {
            return false;
        }

        if ($this->booking_end && $checkOut && $checkOut > $this->booking_end) {
            return false;
        }

        if ($checkIn && $checkOut) {
            $nights = $checkIn->diffInDays($checkOut);
            if ($nights < $this->min_nights) {
                return false;
            }
        }

        return true;
    }

    public function calculateDiscount(float $originalPrice, int $nights = 1): float
    {
        switch ($this->discount_type) {
            case 'percentage':
                return round($originalPrice * ($this->discount_value / 100), 2);

            case 'fixed':
                return min($this->discount_value, $originalPrice);

            case 'free_nights':
                if ($this->free_nights_min && $nights >= $this->free_nights_min) {
                    // Ex: 3 nuits payées = 1 gratuite
                    $freeNights = floor($nights / $this->free_nights_min);
                    $pricePerNight = $originalPrice / $nights;

                    return $freeNights * $pricePerNight;
                }

                return 0;

            default:
                return 0;
        }
    }

    public function getDiscountLabelAttribute(): string
    {
        switch ($this->discount_type) {
            case 'percentage':
                return '-'.intval($this->discount_value).'%';
            case 'fixed':
                return '-'.number_format($this->discount_value, 0, ',', ' ').' F';
            case 'free_nights':
                return $this->free_nights_min.' nuits = '.($this->free_nights_min + 1).'ème offerte';
            default:
                return '';
        }
    }

    public function getRemainingUsesAttribute(): ?int
    {
        return $this->max_uses ? ($this->max_uses - $this->uses_count) : null;
    }

    public function getTimeRemainingAttribute(): string
    {
        if ($this->ends_at < now()) {
            return 'Expirée';
        }

        return $this->ends_at->diffForHumans(['parts' => 2]);
    }

    public function incrementUsage(): void
    {
        $this->increment('uses_count');
    }
}
