<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\CouponUse;
use App\Models\Promotion;
use App\Models\Residence;
use App\Models\User;

class MarketingService
{
    public function __construct(
        private readonly CouponService $couponService,
    ) {
    }

    // ==========================================
    // PROMOTIONS
    // ==========================================

    /**
     * Créer une promotion flash
     */
    public function createPromotion(array $data): Promotion
    {
        return Promotion::create([
            'residence_id' => $data['residence_id'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'discount_type' => $data['discount_type'],
            'discount_value' => $data['discount_value'],
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'],
            'min_nights' => $data['min_nights'] ?? 1,
            'max_uses' => $data['max_uses'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    /**
     * Calculer le prix avec promotion
     */
    public function calculatePromotionalPrice(Residence $residence, ?Promotion $promotion = null): array
    {
        $originalPrice = $residence->price_per_day;

        if (!$promotion) {
            $promotion = $residence->activePromotions()->first();
        }

        if (!$promotion || !$promotion->isValid()) {
            return [
                'original' => $originalPrice,
                'final' => $originalPrice,
                'discount' => 0,
                'discount_percent' => 0,
                'promotion' => null,
            ];
        }

        $discount = $promotion->discount_type === 'percentage'
            ? ($originalPrice * $promotion->discount_value / 100)
            : $promotion->discount_value;

        $finalPrice = max(0, $originalPrice - $discount);
        $discountPercent = $originalPrice > 0
            ? round(($discount / $originalPrice) * 100)
            : 0;

        return [
            'original' => $originalPrice,
            'final' => $finalPrice,
            'discount' => $discount,
            'discount_percent' => $discountPercent,
            'promotion' => $promotion,
        ];
    }

    /**
     * Obtenir les résidences avec promotions actives
     */
    public function getResidencesWithActivePromotions(int $limit = 10)
    {
        return Residence::whereHas('promotions', function ($query) {
            $query->active();
        })
        ->with(['photos', 'promotions' => function ($query) {
            $query->active();
        }])
        ->approved()
        ->available()
        ->limit($limit)
        ->get();
    }

    // ==========================================
    // COUPONS
    // ==========================================

    /**
     * Générer un code coupon unique
     */
    public function generateCouponCode(string $prefix = ''): string
    {
        return $this->couponService->generateCouponCode($prefix);
    }

    /**
     * Créer un coupon
     */
    public function createCoupon(array $data): Coupon
    {
        return $this->couponService->createCoupon($data);
    }

    /**
     * Valider un coupon
     */
    public function validateCoupon(string $code, User $user, ?Residence $residence = null, float $amount = 0): array
    {
        return $this->couponService->validateCoupon($code, $user, $residence, $amount);
    }

    /**
     * Calculer la réduction d'un coupon
     */
    public function calculateCouponDiscount(Coupon $coupon, float $amount): float
    {
        return $this->couponService->calculateCouponDiscount($coupon, $amount);
    }

    /**
     * Utiliser un coupon
     */
    public function useCoupon(Coupon $coupon, User $user, float $discount): CouponUse
    {
        return $this->couponService->useCoupon($coupon, $user, $discount);
    }
}
