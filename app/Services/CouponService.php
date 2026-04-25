<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Coupon;
use App\Models\CouponUse;
use App\Models\Residence;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CouponService
{
    /**
     * Valider un coupon propriétaire.
     *
     * @return array{valid: bool, error?: string, coupon?: Coupon, discount?: float, formatted_discount?: string, formatted_value?: string}
     */
    public function validate(
        string $code,
        int $residenceId,
        float $subtotal,
        int $nights,
        int $userId,
    ): array {
        $coupon = Coupon::where('code', strtoupper(trim($code)))->first();

        if (! $coupon) {
            return ['valid' => false, 'error' => 'Code coupon introuvable.'];
        }

        if (! $coupon->is_active) {
            return ['valid' => false, 'error' => 'Ce coupon n\'est plus actif.'];
        }

        if ($coupon->starts_at && $coupon->starts_at->isFuture()) {
            return ['valid' => false, 'error' => 'Ce coupon n\'est pas encore valide.'];
        }

        if ($coupon->expires_at && $coupon->expires_at->isPast()) {
            return ['valid' => false, 'error' => 'Ce coupon a expiré.'];
        }

        if ($coupon->max_uses && $coupon->uses_count >= $coupon->max_uses) {
            return ['valid' => false, 'error' => 'Ce coupon a atteint sa limite d\'utilisation.'];
        }

        // Vérifier utilisabilité par l'utilisateur
        $user = User::find($userId);
        if (! $user || ! $coupon->canBeUsedBy($user)) {
            return ['valid' => false, 'error' => 'Vous ne pouvez pas utiliser ce coupon.'];
        }

        // Vérifier résidence compatible
        $residence = Residence::find($residenceId);
        if ($residence && ! $coupon->canBeUsedForResidence($residence)) {
            return ['valid' => false, 'error' => 'Ce coupon n\'est pas valide pour cette résidence.'];
        }

        // Vérifier nuits minimum
        if ($coupon->min_nights && $nights < $coupon->min_nights) {
            return [
                'valid' => false,
                'error' => 'Minimum '.$coupon->min_nights.' nuits requis pour ce coupon.',
            ];
        }

        // Vérifier montant minimum
        if ($coupon->min_amount && $subtotal < $coupon->min_amount) {
            return [
                'valid' => false,
                'error' => 'Montant minimum '.number_format((float) $coupon->min_amount, 0, ',', ' ').' FCFA requis.',
            ];
        }

        $discount = $coupon->calculateDiscount($subtotal);

        return [
            'valid' => true,
            'code' => $coupon->code,
            'name' => $coupon->name,
            'type' => $coupon->discount_type,
            'value' => $coupon->discount_value,
            'discount' => $discount,
            'formatted_discount' => number_format($discount, 0, ',', ' ').' FCFA',
            'formatted_value' => $coupon->discount_label,
            'source' => 'coupon', // Pour différencier du promo_code
        ];
    }

    /**
     * Appliquer un coupon lors du calcul de prix (retourne les données nécessaires à PricingService).
     */
    public function apply(
        string $code,
        Residence $residence,
        float $subtotal,
        int $nights,
        User $user,
    ): array {
        $coupon = Coupon::where('code', strtoupper(trim($code)))->first();

        if (! $coupon || ! $coupon->isValid()) {
            return ['discount' => 0, 'coupon' => null, 'error' => 'Coupon invalide ou expiré'];
        }

        if (! $coupon->canBeUsedBy($user)) {
            return ['discount' => 0, 'coupon' => null, 'error' => 'Vous ne pouvez pas utiliser ce coupon'];
        }

        if (! $coupon->canBeUsedForResidence($residence)) {
            return ['discount' => 0, 'coupon' => null, 'error' => 'Ce coupon n\'est pas valide pour cette résidence'];
        }

        if ($coupon->min_nights && $nights < $coupon->min_nights) {
            return ['discount' => 0, 'coupon' => null, 'error' => 'Minimum '.$coupon->min_nights.' nuits requis'];
        }

        if ($coupon->min_amount && $subtotal < $coupon->min_amount) {
            return ['discount' => 0, 'coupon' => null, 'error' => 'Montant minimum '.number_format((float) $coupon->min_amount, 0, ',', ' ').' FCFA'];
        }

        $discount = $coupon->calculateDiscount($subtotal);

        return [
            'discount' => $discount,
            'coupon' => [
                'id' => $coupon->id,
                'code' => $coupon->code,
                'name' => $coupon->name,
                'discount_type' => $coupon->discount_type,
                'discount_value' => $coupon->discount_value,
                'formatted_value' => $coupon->discount_label,
            ],
            'error' => null,
        ];
    }

    /**
     * Enregistrer l'utilisation d'un coupon après réservation.
     */
    public function recordUsage(Coupon $coupon, User $user, Booking $booking, float $discount): CouponUse
    {
        return DB::transaction(function () use ($coupon, $user, $booking, $discount) {
            // Re-check max_uses avec lock pour éviter le dépassement concurrent (TOCTOU)
            $locked = Coupon::lockForUpdate()->find($coupon->id);

            if ($locked->max_uses && $locked->uses_count >= $locked->max_uses) {
                throw new \RuntimeException('Ce coupon a atteint sa limite d\'utilisation.');
            }

            $use = CouponUse::create([
                'coupon_id' => $coupon->id,
                'user_id' => $user->id,
                'booking_id' => $booking->id,
                'discount_applied' => $discount,
            ]);

            $locked->increment('uses_count');

            return $use;
        });
    }
}
