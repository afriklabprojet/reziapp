<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DailyPrice;
use App\Models\Booking;
use App\Models\Residence;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class YieldManagementService
{
    protected DynamicPricingService $dynamicPricing;

    public function __construct(DynamicPricingService $dynamicPricing)
    {
        $this->dynamicPricing = $dynamicPricing;
    }

    /**
     * Appliquer automatiquement les prix dynamiques pour les résidences activées
     */
    public function applyAutoPricing(int $daysAhead = 60): array
    {
        $residences = Residence::where('auto_pricing_enabled', true)
            ->where('status', 'approved')
            ->where('is_available', true)
            ->get();

        $results = [];

        foreach ($residences as $residence) {
            try {
                $updated = $this->applyForResidence($residence, $daysAhead);
                $results[] = [
                    'residence_id' => $residence->id,
                    'name'         => $residence->name,
                    'updates'      => $updated,
                ];
            } catch (\Throwable $e) {
                Log::error("YieldManagement error for residence {$residence->id}: {$e->getMessage()}");
                $results[] = [
                    'residence_id' => $residence->id,
                    'error'        => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Appliquer le yield management pour une résidence
     */
    public function applyForResidence(Residence $residence, int $daysAhead = 60): int
    {
        $basePrice = $residence->price_per_day ?? $residence->price_per_month / 30;
        $minPrice  = $residence->auto_pricing_min ?? ($basePrice * 0.6);
        $maxPrice  = $residence->auto_pricing_max ?? ($basePrice * 2.0);

        $suggestions = $this->dynamicPricing->generateSuggestions($residence, $daysAhead);
        $updated     = 0;

        foreach ($suggestions as $group) {
            if (!isset($group['dates'])) {
                continue;
            }

            foreach ($group['dates'] as $suggestion) {
                $suggestedPrice = $suggestion['suggested_price'] ?? null;
                $date           = $suggestion['date'] ?? null;

                if (!$suggestedPrice || !$date) {
                    continue;
                }

                // Clamp entre min et max
                $finalPrice = max($minPrice, min($maxPrice, $suggestedPrice));

                DailyPrice::updateOrCreate(
                    ['residence_id' => $residence->id, 'date' => $date],
                    ['price' => $finalPrice, 'note' => 'Auto-yield: ' . ($suggestion['reason'] ?? 'dynamic')]
                );

                $updated++;
            }
        }

        return $updated;
    }

    /**
     * Détecter et combler les nuits orphelines (gap-night pricing)
     */
    public function applyGapNightPricing(): array
    {
        $residences = Residence::where('gap_night_pricing_enabled', true)
            ->where('status', 'approved')
            ->where('is_available', true)
            ->get();

        $results = [];

        foreach ($residences as $residence) {
            $gaps = $this->findGapNights($residence);

            foreach ($gaps as $gap) {
                $basePrice      = $residence->price_per_day ?? 0;
                $discountPct    = $residence->gap_night_discount_percent ?? 20;
                $discountedPrice = (int) round($basePrice * (1 - $discountPct / 100));

                foreach ($gap['dates'] as $date) {
                    DailyPrice::updateOrCreate(
                        ['residence_id' => $residence->id, 'date' => $date],
                        ['price' => $discountedPrice, 'note' => "Gap-night -$discountPct%"]
                    );
                }
            }

            if (!empty($gaps)) {
                $results[] = [
                    'residence_id' => $residence->id,
                    'name'         => $residence->name,
                    'gaps_found'   => count($gaps),
                ];
            }
        }

        return $results;
    }

    /**
     * Trouver les nuits orphelines entre les réservations
     */
    public function findGapNights(Residence $residence, int $daysAhead = 60): array
    {
        $maxGapDays = $residence->gap_night_max_days ?? 2;

        $bookings = Booking::where('residence_id', $residence->id)
            ->whereIn('status', ['confirmed', 'pending'])
            ->where('check_out', '>=', now())
            ->where('check_in', '<=', now()->addDays($daysAhead))
            ->orderBy('check_in')
            ->get();

        $gaps = [];

        for ($i = 0; $i < $bookings->count() - 1; $i++) {
            $currentCheckout = Carbon::parse($bookings[$i]->check_out);
            $nextCheckin     = Carbon::parse($bookings[$i + 1]->check_in);
            $gapDays         = $currentCheckout->diffInDays($nextCheckin);

            if ($gapDays > 0 && $gapDays <= $maxGapDays) {
                $dates = [];
                $date  = $currentCheckout->copy();
                while ($date < $nextCheckin) {
                    $dates[] = $date->format('Y-m-d');
                    $date->addDay();
                }

                $gaps[] = [
                    'after_booking'  => $bookings[$i]->reference,
                    'before_booking' => $bookings[$i + 1]->reference,
                    'dates'          => $dates,
                    'gap_days'       => $gapDays,
                ];
            }
        }

        return $gaps;
    }
}
