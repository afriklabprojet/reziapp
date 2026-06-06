<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Booking;
use App\Models\User;

/**
 * Gère le programme de fidélité ReziApp (style Genius / Booking.com).
 *
 * Paliers :
 *  Standard  →  0 réservations,   0 % de remise
 *  Bronze    →  3 réservations,   5 % de remise
 *  Silver    →  8 réservations,  10 % de remise
 *  Gold      → 20 réservations,  15 % de remise
 *  Platinum  → 50 réservations,  20 % de remise
 */
class LoyaltyService
{
    /**
     * Crédite des points et met à jour le compteur après une réservation confirmée.
     * Appeler depuis un Listener sur BookingConfirmed ou BookingCompleted.
     */
    public function recordBooking(User $user, Booking $booking): void
    {
        // Points = 1 pt par 1 000 FCFA dépensés (arrondi à l'entier inférieur)
        $points = (int) floor(($booking->total_amount ?? 0) / 1000);

        $user->loyalty_points          = ($user->loyalty_points ?? 0) + $points;
        $user->loyalty_bookings_count  = ($user->loyalty_bookings_count ?? 0) + 1;
        $user->loyalty_nights_count    = ($user->loyalty_nights_count ?? 0) + ($booking->nights ?? 1);
        $user->loyalty_total_spent     = ($user->loyalty_total_spent ?? 0) + ($booking->total_amount ?? 0);
        $user->save();

        $user->recalculateLoyaltyTier();
    }

    /**
     * Calcule la remise fidélité applicable à un montant donné.
     * Retourne le montant de la remise en FCFA.
     */
    public function calculateDiscount(User $user, float $amount): float
    {
        $percent = $user->loyalty_discount;

        if ($percent <= 0) {
            return 0.0;
        }

        return round($amount * $percent / 100, 2);
    }

    /**
     * Calcule les points et informations d'avancement vers le prochain palier.
     *
     * @return array{tier: string, label: string, icon: string, discount: int,
     *               next_tier: string|null, next_label: string|null,
     *               bookings_to_next: int|null, progress_percent: int}
     */
    public function getProgress(User $user): array
    {
        $tiers    = User::LOYALTY_TIERS;
        $current  = $user->loyalty_tier ?? 'standard';
        $bookings = $user->loyalty_bookings_count ?? 0;

        $tierKeys   = array_keys($tiers);
        $currentIdx = array_search($current, $tierKeys, true);

        $nextTier       = null;
        $nextLabel      = null;
        $bookingsToNext = null;
        $progressPercent = 100;

        if ($currentIdx !== false && $currentIdx < (count($tierKeys) - 1)) {
            $nextKey        = $tierKeys[$currentIdx + 1];
            $nextTier       = $nextKey;
            $nextLabel      = $tiers[$nextKey]['label'];
            $nextMin        = $tiers[$nextKey]['min_bookings'];
            $currentMin     = $tiers[$current]['min_bookings'];
            $bookingsToNext = max(0, $nextMin - $bookings);

            $range           = $nextMin - $currentMin;
            $done            = $bookings - $currentMin;
            $progressPercent = $range > 0 ? min(100, (int) round($done / $range * 100)) : 100;
        }

        return [
            'tier'             => $current,
            'label'            => $tiers[$current]['label'],
            'icon'             => $tiers[$current]['icon'],
            'color'            => $tiers[$current]['color'],
            'discount'         => $tiers[$current]['discount'],
            'next_tier'        => $nextTier,
            'next_label'       => $nextLabel,
            'bookings_to_next' => $bookingsToNext,
            'progress_percent' => $progressPercent,
            'total_bookings'   => $bookings,
            'total_points'     => $user->loyalty_points ?? 0,
        ];
    }
}
