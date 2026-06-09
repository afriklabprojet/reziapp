<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Notifications\LoyaltyTierUpgraded;

trait HasLoyalty
{
    public const LOYALTY_TIERS = [
        'standard' => ['label' => 'Standard', 'color' => 'gray',   'min_bookings' => 0,  'discount' => 0,  'icon' => '⬜'],
        'bronze'   => ['label' => 'Bronze',   'color' => 'amber',  'min_bookings' => 3,  'discount' => 5,  'icon' => '🥉'],
        'silver'   => ['label' => 'Silver',   'color' => 'slate',  'min_bookings' => 8,  'discount' => 10, 'icon' => '🥈'],
        'gold'     => ['label' => 'Gold',     'color' => 'yellow', 'min_bookings' => 20, 'discount' => 15, 'icon' => '🥇'],
        'platinum' => ['label' => 'Platinum', 'color' => 'violet', 'min_bookings' => 50, 'discount' => 20, 'icon' => '💎'],
    ];

    public function getLoyaltyDiscountAttribute(): int
    {
        return self::LOYALTY_TIERS[$this->loyalty_tier ?? 'standard']['discount'] ?? 0;
    }

    public function getLoyaltyTierLabelAttribute(): string
    {
        return self::LOYALTY_TIERS[$this->loyalty_tier ?? 'standard']['label'] ?? 'Standard';
    }

    public function getLoyaltyTierIconAttribute(): string
    {
        return self::LOYALTY_TIERS[$this->loyalty_tier ?? 'standard']['icon'] ?? '⬜';
    }

    public function recalculateLoyaltyTier(): void
    {
        $bookings = $this->loyalty_bookings_count ?? 0;
        $newTier = 'standard';

        foreach (array_reverse(self::LOYALTY_TIERS) as $tier => $config) {
            if ($bookings >= $config['min_bookings']) {
                $newTier = $tier;
                break;
            }
        }

        if ($newTier === $this->loyalty_tier) {
            return;
        }

        $oldTier = $this->loyalty_tier ?? 'standard';
        $this->loyalty_tier = $newTier;
        $this->loyalty_tier_upgraded_at = now();
        $this->save();

        $tierKeys = array_keys(self::LOYALTY_TIERS);
        $oldIdx = array_search($oldTier, $tierKeys, true);
        $newIdx = array_search($newTier, $tierKeys, true);

        if ($newIdx !== false && $oldIdx !== false && $newIdx > $oldIdx) {
            $this->notify(new LoyaltyTierUpgraded($newTier, $oldTier));
        }
    }
}
