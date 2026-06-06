<?php

namespace App\Services;

use App\Exceptions\CouponUsageLimitReachedException;
use App\Models\Booking;
use App\Models\Coupon;
use App\Models\CouponUse;
use App\Models\Residence;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CouponService
{
    private const CURRENCY_SUFFIX = ' FCFA';

    private const LIMIT_REACHED_MESSAGE = 'Ce coupon a atteint sa limite d\'utilisation.';

    public function generateCouponCode(string $prefix = ''): string
    {
        do {
            $code = strtoupper($prefix).strtoupper(Str::random(8));
        } while (Coupon::where('code', $code)->exists());

        return $code;
    }

    public function createCoupon(array $data): Coupon
    {
        return Coupon::create([
            'user_id' => $data['user_id'],
            'residence_id' => $data['residence_id'] ?? null,
            'code' => $data['code'] ?? $this->generateCouponCode(),
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'discount_type' => $data['discount_type'],
            'discount_value' => $data['discount_value'],
            'min_amount' => $data['min_amount'] ?? null,
            'max_discount' => $data['max_discount'] ?? null,
            'max_uses' => $data['max_uses'] ?? null,
            'max_uses_per_user' => $data['max_uses_per_user'] ?? 1,
            'starts_at' => $data['starts_at'] ?? now(),
            'expires_at' => $data['expires_at'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    /**
     * Valider un coupon sans le contexte complet du calcul de prix.
     *
     * @return array{valid: bool, error?: string, coupon?: Coupon, discount?: float, final_amount?: float}
     */
    public function validateCoupon(string $code, User $user, ?Residence $residence = null, float $amount = 0): array
    {
        $coupon = $this->findCouponByCode($code);
        $error = $this->resolveMarketingCouponValidationError($coupon, $user, $residence, $amount);

        if ($error !== null || ! $coupon) {
            return ['valid' => false, 'error' => $error ?? 'Code coupon invalide'];
        }

        $discount = $this->calculateCouponDiscount($coupon, $amount);

        return [
            'valid' => true,
            'coupon' => $coupon,
            'discount' => $discount,
            'final_amount' => max(0, $amount - $discount),
        ];
    }

    public function calculateCouponDiscount(Coupon $coupon, float $amount): float
    {
        return $coupon->calculateDiscount($amount);
    }

    public function useCoupon(Coupon $coupon, User $user, float $discount): CouponUse
    {
        return DB::transaction(function () use ($coupon, $user, $discount) {
            $locked = Coupon::lockForUpdate()->find($coupon->id);

            $this->ensureCouponUsageCapacity($locked);

            $couponUse = CouponUse::create([
                'coupon_id' => $locked->id,
                'user_id' => $user->id,
                'discount_applied' => $discount,
            ]);

            $locked->increment('uses_count');

            return $couponUse;
        });
    }

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
        $user = User::find($userId);
        $coupon = $this->findCouponByCode($code);
        $residence = Residence::find($residenceId);
        $error = $this->resolveOwnerCouponValidationError($coupon, $user, $residence, $nights, $subtotal);

        if ($error !== null || ! $coupon) {
            return ['valid' => false, 'error' => $error ?? 'Code coupon introuvable.'];
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
        $coupon = $this->findCouponByCode($code);
        $error = $this->resolveAppliedCouponValidationError($coupon, $user, $residence, $nights, $subtotal);

        if ($error !== null || ! $coupon) {
            return ['discount' => 0, 'coupon' => null, 'error' => $error ?? 'Coupon invalide ou expiré'];
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
            $locked = Coupon::lockForUpdate()->find($coupon->id);
            $this->ensureCouponUsageCapacity($locked);

            $use = CouponUse::create([
                'coupon_id' => $locked->id,
                'user_id' => $user->id,
                'booking_id' => $booking->id,
                'discount_applied' => $discount,
            ]);

            $locked->increment('uses_count');

            return $use;
        });
    }

    private function findCouponByCode(string $code): ?Coupon
    {
        return Coupon::where('code', strtoupper(trim($code)))->first();
    }

    private function resolveMarketingCouponValidationError(?Coupon $coupon, User $user, ?Residence $residence, float $amount): ?string
    {
        $error = null;

        if (! $coupon) {
            $error = 'Code coupon invalide';
        } elseif (! $coupon->is_active) {
            $error = 'Ce coupon n\'est plus actif';
        } elseif ($coupon->starts_at && $coupon->starts_at->isFuture()) {
            $error = 'Ce coupon n\'est pas encore valide';
        } elseif ($coupon->expires_at && $coupon->expires_at->isPast()) {
            $error = 'Ce coupon a expiré';
        } elseif ($coupon->max_uses && $coupon->uses_count >= $coupon->max_uses) {
            $error = 'Ce coupon a atteint son nombre maximum d\'utilisations';
        } elseif (! $coupon->canBeUsedBy($user)) {
            $error = 'Vous avez déjà utilisé ce coupon';
        } elseif ($residence && ! $coupon->canBeUsedForResidence($residence)) {
            $error = 'Ce coupon n\'est pas valide pour cette résidence';
        } elseif ($coupon->min_amount && $amount < $coupon->min_amount) {
            $error = 'Le montant minimum requis est de '.$this->formatAmount((float) $coupon->min_amount);
        }

        return $error;
    }

    private function resolveOwnerCouponValidationError(?Coupon $coupon, ?User $user, ?Residence $residence, int $nights, float $subtotal): ?string
    {
        if (! $coupon) {
            return 'Code coupon introuvable.';
        }

        $stateError = $this->resolveOwnerCouponStateError($coupon);

        if ($stateError !== null) {
            return $stateError;
        }

        return $this->resolveOwnerCouponEligibilityError($coupon, $user, $residence, $nights, $subtotal);
    }

    private function resolveAppliedCouponValidationError(?Coupon $coupon, User $user, Residence $residence, int $nights, float $subtotal): ?string
    {
        $error = null;

        if (! $coupon || ! $coupon->isValid()) {
            $error = 'Coupon invalide ou expiré';
        } elseif (! $coupon->canBeUsedBy($user)) {
            $error = 'Vous ne pouvez pas utiliser ce coupon';
        } elseif (! $coupon->canBeUsedForResidence($residence)) {
            $error = 'Ce coupon n\'est pas valide pour cette résidence';
        } elseif ($coupon->min_nights && $nights < $coupon->min_nights) {
            $error = 'Minimum '.$coupon->min_nights.' nuits requis';
        } elseif ($coupon->min_amount && $subtotal < $coupon->min_amount) {
            $error = 'Montant minimum '.$this->formatAmount((float) $coupon->min_amount);
        }

        return $error;
    }

    private function resolveOwnerCouponStateError(Coupon $coupon): ?string
    {
        return match (true) {
            ! $coupon->is_active => 'Ce coupon n\'est plus actif.',
            $coupon->starts_at && $coupon->starts_at->isFuture() => 'Ce coupon n\'est pas encore valide.',
            $coupon->expires_at && $coupon->expires_at->isPast() => 'Ce coupon a expiré.',
            $coupon->max_uses && $coupon->uses_count >= $coupon->max_uses => self::LIMIT_REACHED_MESSAGE,
            default => null,
        };
    }

    private function resolveOwnerCouponEligibilityError(
        Coupon $coupon,
        ?User $user,
        ?Residence $residence,
        int $nights,
        float $subtotal,
    ): ?string {
        return match (true) {
            ! $user || ! $coupon->canBeUsedBy($user) => 'Vous ne pouvez pas utiliser ce coupon.',
            $residence && ! $coupon->canBeUsedForResidence($residence) => 'Ce coupon n\'est pas valide pour cette résidence.',
            $coupon->min_nights && $nights < $coupon->min_nights => 'Minimum '.$coupon->min_nights.' nuits requis pour ce coupon.',
            $coupon->min_amount && $subtotal < $coupon->min_amount => 'Montant minimum '.$this->formatAmount((float) $coupon->min_amount).' requis.',
            default => null,
        };
    }

    private function ensureCouponUsageCapacity(?Coupon $coupon): void
    {
        if (! $coupon || ($coupon->max_uses && $coupon->uses_count >= $coupon->max_uses)) {
            throw new CouponUsageLimitReachedException(self::LIMIT_REACHED_MESSAGE);
        }
    }

    private function formatAmount(float $amount): string
    {
        return number_format($amount, 0, ',', ' ').self::CURRENCY_SUFFIX;
    }
}
