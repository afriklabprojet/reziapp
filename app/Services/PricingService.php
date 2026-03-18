<?php

namespace App\Services;

use App\Models\LongStayDiscount;
use App\Models\PlatformSetting;
use App\Models\PromoCode;
use App\Services\CouponService;
use App\Models\Residence;
use App\Models\SpecialPrice;
use App\Models\User;
use Carbon\Carbon;

class PricingService
{
    /**
     * Calculer le prix total d'une réservation
     */
    public function calculatePrice(
        Residence $residence,
        Carbon $checkIn,
        Carbon $checkOut,
        int $guests = 1,
        ?string $promoCode = null,
        ?User $user = null,
        ?string $couponCode = null,
    ): array {
        $nights = (int) $checkIn->diffInDays($checkOut);

        if ($nights <= 0) {
            throw new \InvalidArgumentException('La date de départ doit être après la date d\'arrivée.');
        }

        // Calculer le prix de base par nuit (tarif intelligent)
        $basePricePerNight = $this->resolveBasePrice($residence, $nights);

        // Obtenir les prix spéciaux pour la période
        $specialPrices = SpecialPrice::getPricesForDateRange(
            $residence->id,
            $checkIn,
            $checkOut->copy()->subDay(),
        );

        // Calculer le détail par nuit
        $nightlyBreakdown = [];
        $subtotal = 0;
        $currentDate = $checkIn->copy();

        for ($i = 0; $i < $nights; $i++) {
            $dateStr = $currentDate->format('Y-m-d');
            $priceForNight = $specialPrices[$dateStr] ?? $basePricePerNight;

            $nightlyBreakdown[] = [
                'date' => $dateStr,
                'price' => $priceForNight,
                'is_special' => isset($specialPrices[$dateStr]),
            ];

            $subtotal += $priceForNight;
            $currentDate->addDay();
        }

        // Prix moyen par nuit
        $avgPricePerNight = round($subtotal / $nights, 0);

        // Frais de ménage
        $cleaningFee = $residence->cleaning_fee ?? 0;

        // Réduction long séjour
        $longStayDiscount = $this->calculateLongStayDiscount($residence, $nights, $subtotal);

        // Code promo
        $promoDiscount = 0;
        $appliedPromoCode = null;
        if ($promoCode && $user) {
            $promoResult = $this->applyPromoCode($promoCode, $residence, $subtotal, $nights, $user);
            $promoDiscount = $promoResult['discount'];
            $appliedPromoCode = $promoResult['code'];
        }

        // Coupon propriétaire
        $couponDiscount = 0;
        $appliedCoupon = null;
        if ($couponCode && $user) {
            $couponService = app(CouponService::class);
            $couponResult = $couponService->apply($couponCode, $residence, $subtotal, $nights, $user);
            $couponDiscount = $couponResult['discount'];
            $appliedCoupon = $couponResult['coupon'];
        }

        // Total réductions
        $totalDiscount = $longStayDiscount + $promoDiscount + $couponDiscount;

        // Sous-total après réductions
        $subtotalAfterDiscount = $subtotal - $totalDiscount;

        // Pas de frais de service côté locataire — la commission est prélevée sur le propriétaire
        $serviceFee = 0;

        // Base pour taxes (sous-total + ménage)
        $taxableAmount = $subtotalAfterDiscount + $cleaningFee;

        // Taxes
        $taxes = round($taxableAmount * config('rezi.pricing.tax_rate'), 0);

        // Total final (locataire ne paie pas de commission)
        $totalAmount = $subtotalAfterDiscount + $cleaningFee + $taxes;

        // Construire le détail complet
        return [
            'residence_id' => $residence->id,
            'check_in' => $checkIn->format('Y-m-d'),
            'check_out' => $checkOut->format('Y-m-d'),
            'nights' => $nights,
            'guests' => $guests,

            // Prix
            'base_price_per_night' => $basePricePerNight,
            'avg_price_per_night' => $avgPricePerNight,
            'subtotal' => $subtotal,

            // Frais
            'cleaning_fee' => $cleaningFee,
            'service_fee' => $serviceFee,
            'service_fee_rate' => config('rezi.pricing.service_fee_rate') * 100,

            // Réductions
            'long_stay_discount' => $longStayDiscount,
            'long_stay_discount_info' => $this->getLongStayDiscountInfo($residence, $nights),
            'promo_discount' => $promoDiscount,
            'promo_code' => $appliedPromoCode,
            'coupon_discount' => $couponDiscount,
            'coupon' => $appliedCoupon,
            'total_discount' => $totalDiscount,

            // Taxes
            'taxes' => $taxes,
            'tax_rate' => config('rezi.pricing.tax_rate') * 100,

            // Total
            'total_amount' => $totalAmount,
            'currency' => 'XOF',

            // Détail par nuit
            'nightly_breakdown' => $nightlyBreakdown,

            // Résumé pour affichage
            'summary' => [
                ['label' => $avgPricePerNight.' FCFA x '.$nights.' nuits', 'amount' => $subtotal],
                ['label' => 'Frais de ménage', 'amount' => $cleaningFee],
            ],

            // Validité du calcul
            'calculated_at' => now()->toIso8601String(),
            'valid_until' => now()->addMinutes(30)->toIso8601String(),
        ];
    }

    /**
     * Calculer la réduction long séjour
     */
    public function calculateLongStayDiscount(Residence $residence, int $nights, float $subtotal): float
    {
        $discount = LongStayDiscount::getApplicableDiscount($residence->id, $nights);

        if (!$discount) {
            return 0;
        }

        return $discount->calculateDiscount($subtotal);
    }

    /**
     * Obtenir les infos de la réduction long séjour
     */
    public function getLongStayDiscountInfo(Residence $residence, int $nights): ?array
    {
        $discount = LongStayDiscount::getApplicableDiscount($residence->id, $nights);

        if (!$discount) {
            // Chercher la prochaine réduction disponible
            $nextDiscount = LongStayDiscount::forResidence($residence->id)
                ->active()
                ->where('min_nights', '>', $nights)
                ->orderBy('min_nights')
                ->first();

            if ($nextDiscount) {
                return [
                    'applied' => false,
                    'message' => 'Ajoutez '.($nextDiscount->min_nights - $nights).' nuits pour obtenir -'.$nextDiscount->discount_percent.'%',
                    'next_discount' => [
                        'min_nights' => $nextDiscount->min_nights,
                        'percent' => $nextDiscount->discount_percent,
                    ],
                ];
            }

            return null;
        }

        return [
            'applied' => true,
            'label' => $discount->getLabel(),
            'percent' => $discount->discount_percent,
        ];
    }

    /**
     * Appliquer un code promo
     */
    public function applyPromoCode(
        string $code,
        Residence $residence,
        float $subtotal,
        int $nights,
        User $user,
    ): array {
        $promoCode = PromoCode::byCode($code)->active()->first();

        if (!$promoCode) {
            return ['discount' => 0, 'code' => null, 'error' => 'Code promo invalide ou expiré'];
        }

        if (!$promoCode->canBeUsedBy($user)) {
            return ['discount' => 0, 'code' => null, 'error' => 'Vous ne pouvez pas utiliser ce code'];
        }

        if (!$promoCode->isApplicableToResidence($residence->id)) {
            return ['discount' => 0, 'code' => null, 'error' => 'Ce code n\'est pas valide pour cette résidence'];
        }

        if (!$promoCode->isApplicableToNights($nights)) {
            return ['discount' => 0, 'code' => null, 'error' => 'Minimum '.$promoCode->min_nights.' nuits requis'];
        }

        if (!$promoCode->isApplicableToAmount($subtotal)) {
            return ['discount' => 0, 'code' => null, 'error' => 'Montant minimum '.number_format($promoCode->min_amount, 0, ',', ' ').' FCFA'];
        }

        $discount = $promoCode->calculateDiscount($subtotal);

        return [
            'discount' => $discount,
            'code' => [
                'code' => $promoCode->code,
                'name' => $promoCode->name,
                'type' => $promoCode->type,
                'value' => $promoCode->value,
                'formatted_value' => $promoCode->getFormattedValue(),
            ],
            'error' => null,
        ];
    }

    /**
     * Valider un code promo
     */
    public function validatePromoCode(
        string $code,
        Residence $residence,
        float $subtotal,
        int $nights,
        User $user,
    ): array {
        $result = $this->applyPromoCode($code, $residence, $subtotal, $nights, $user);

        return [
            'valid' => $result['error'] === null,
            'discount' => $result['discount'],
            'code' => $result['code'],
            'error' => $result['error'],
        ];
    }

    /**
     * Obtenir les réductions long séjour disponibles pour une résidence
     */
    public function getAvailableLongStayDiscounts(Residence $residence): array
    {
        return LongStayDiscount::forResidence($residence->id)
            ->active()
            ->orderBy('min_nights')
            ->get()
            ->map(function ($discount) {
                return [
                    'min_nights' => $discount->min_nights,
                    'percent' => $discount->discount_percent,
                    'label' => $discount->getLabel(),
                ];
            })
            ->toArray();
    }

    /**
     * Calculer la part du propriétaire
     */
    public function calculateOwnerEarnings(array $priceBreakdown): array
    {
        // Le propriétaire reçoit: sous-total - réductions + ménage
        $ownerSubtotal = $priceBreakdown['subtotal'] - $priceBreakdown['total_discount'] + $priceBreakdown['cleaning_fee'];

        // Commission REZI sur le sous-total (depuis les paramètres admin)
        $commissionRate = PlatformSetting::getCommissionRate() / 100;
        $reziCommission = round($ownerSubtotal * $commissionRate, 0);

        $ownerEarnings = $ownerSubtotal - $reziCommission;

        return [
            'owner_subtotal' => $ownerSubtotal,
            'rezi_commission' => $reziCommission,
            'rezi_commission_rate' => $commissionRate * 100,
            'owner_earnings' => $ownerEarnings,
        ];
    }

    /**
     * Formater un prix pour l'affichage
     */
    public static function formatPrice(float $amount, string $currency = 'XOF'): string
    {
        return number_format($amount, 0, ',', ' ').' FCFA';
    }

    /**
     * Obtenir le résumé des frais pour l'affichage
     */
    public function getFeeSummary(): array
    {
        return [
            'taxes' => [
                'rate' => config('rezi.pricing.tax_rate') * 100,
                'label' => 'Taxes',
                'description' => 'TVA applicable selon la réglementation ivoirienne.',
            ],
        ];
    }

    /**
     * Résoudre le meilleur tarif nuitée pour une résidence selon la durée du séjour.
     *
     * Logique de tarification intelligente :
     * - Séjour >= 30 nuits → utilise price_per_month / 30 si disponible
     * - Séjour >= 7 nuits  → utilise price_per_week / 7 si disponible
     * - Sinon              → utilise price_per_day
     * - Fallback           → dérive depuis le tarif disponible le plus pertinent
     */
    protected function resolveBasePrice(Residence $residence, int $nights): float
    {
        $pricePerDay = $residence->price_per_day ? (float) $residence->price_per_day : null;
        $pricePerWeek = $residence->price_per_week ? (float) $residence->price_per_week : null;
        $pricePerMonth = $residence->price_per_month ? (float) $residence->price_per_month : null;

        // Séjour long (>= 30 nuits) → tarif mensuel si dispo
        if ($nights >= 30 && $pricePerMonth && $pricePerMonth > 0) {
            return round($pricePerMonth / 30);
        }

        // Séjour moyen (>= 7 nuits) → tarif hebdo si dispo
        if ($nights >= 7 && $pricePerWeek && $pricePerWeek > 0) {
            return round($pricePerWeek / 7);
        }

        // Tarif nuitée direct
        if ($pricePerDay && $pricePerDay > 0) {
            return $pricePerDay;
        }

        // Fallback : dérivation depuis le tarif disponible
        if ($pricePerWeek && $pricePerWeek > 0) {
            return round($pricePerWeek / 7);
        }

        if ($pricePerMonth && $pricePerMonth > 0) {
            return round($pricePerMonth / 30);
        }

        return 0;
    }
}
