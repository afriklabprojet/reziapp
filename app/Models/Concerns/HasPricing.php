<?php

declare(strict_types=1);

namespace App\Models\Concerns;

trait HasPricing
{
    /** Alias for views that use ->price_per_night instead of ->price_per_day */
    public function getPricePerNightAttribute(): ?string
    {
        if ($this->price_per_day && $this->price_per_day > 0) {
            return $this->price_per_day;
        }

        if ($this->price_per_month && $this->price_per_month > 0) {
            return (string) round($this->price_per_month / 30);
        }

        return null;
    }

    /** Display price — always daily rate */
    public function getPriceAttribute(): float
    {
        if (($this->price_per_day ?? 0) > 0) {
            return (float) $this->price_per_day;
        }

        if (($this->price_per_month ?? 0) > 0) {
            return (float) round($this->price_per_month / 30);
        }

        return 0;
    }

    public function getPriceLabelAttribute(): string
    {
        return 'jour';
    }

    public function getDisplayPriceAttribute(): float
    {
        return $this->price;
    }

    public function getBestPrice(): array
    {
        $originalPrice = $this->price_per_day;
        $activePromotion = $this->activePromotions()->first();

        if (! $activePromotion) {
            return [
                'original'         => $originalPrice,
                'final'            => $originalPrice,
                'discount'         => 0,
                'discount_percent' => 0,
                'promotion'        => null,
            ];
        }

        $discount = $activePromotion->discount_type === 'percentage'
            ? ($originalPrice * $activePromotion->discount_value / 100)
            : $activePromotion->discount_value;

        $finalPrice = max(0, $originalPrice - $discount);
        $discountPercent = $originalPrice > 0
            ? round(($discount / $originalPrice) * 100)
            : 0;

        return [
            'original'         => $originalPrice,
            'final'            => $finalPrice,
            'discount'         => $discount,
            'discount_percent' => $discountPercent,
            'promotion'        => $activePromotion,
        ];
    }
}
