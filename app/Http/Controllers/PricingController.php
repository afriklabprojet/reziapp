<?php

namespace App\Http\Controllers;

use App\Models\Residence;
use App\Services\CouponService;
use App\Services\PricingService;
use App\Services\PromoCodeService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PricingController extends Controller
{
    protected PricingService $pricingService;
    protected PromoCodeService $promoCodeService;
    protected CouponService $couponService;

    public function __construct(PricingService $pricingService, PromoCodeService $promoCodeService, CouponService $couponService)
    {
        $this->pricingService = $pricingService;
        $this->promoCodeService = $promoCodeService;
        $this->couponService = $couponService;
    }

    /**
     * Calculer le prix d'une réservation
     */
    public function calculate(Request $request)
    {
        $request->validate([
            'residence_id' => 'required|exists:residences,id',
            'check_in' => 'required|date|after:today',
            'check_out' => 'required|date|after:check_in',
            'guests' => 'required|integer|min:1',
            'promo_code' => 'nullable|string',
        ]);

        $residence = Residence::findOrFail($request->residence_id);

        try {
            $price = $this->pricingService->calculatePrice(
                $residence,
                Carbon::parse($request->check_in),
                Carbon::parse($request->check_out),
                $request->guests,
                $request->promo_code,
                Auth::user(),
            );

            return response()->json([
                'success' => true,
                'data' => $price,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Valider un code promo
     */
    public function validatePromoCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'residence_id' => 'required|exists:residences,id',
            'subtotal' => 'required|numeric|min:0',
            'nights' => 'required|integer|min:1',
        ]);

        $result = $this->promoCodeService->validate(
            $request->code,
            $request->residence_id,
            $request->subtotal,
            $request->nights,
            Auth::id(),
        );

        return response()->json($result);
    }

    /**
     * Valider un coupon propriétaire
     */
    public function validateCoupon(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'residence_id' => 'required|exists:residences,id',
            'subtotal' => 'required|numeric|min:0',
            'nights' => 'required|integer|min:1',
        ]);

        $result = $this->couponService->validate(
            $request->code,
            $request->residence_id,
            $request->subtotal,
            $request->nights,
            Auth::id(),
        );

        return response()->json($result);
    }

    /**
     * Valider un code unifié (cherche d'abord dans promo_codes, puis coupons)
     * Un seul champ côté frontend → l'admin gère tout
     */
    public function validateCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'residence_id' => 'required|exists:residences,id',
            'subtotal' => 'required|numeric|min:0',
            'nights' => 'required|integer|min:1',
        ]);

        // 1. Tenter d'abord comme code promo (admin)
        $promoResult = $this->promoCodeService->validate(
            $request->code,
            $request->residence_id,
            $request->subtotal,
            $request->nights,
            Auth::id(),
        );

        if (!empty($promoResult['valid'])) {
            return response()->json(array_merge($promoResult, ['type' => 'promo']));
        }

        // 2. Sinon, tenter comme coupon
        $couponResult = $this->couponService->validate(
            $request->code,
            $request->residence_id,
            $request->subtotal,
            $request->nights,
            Auth::id(),
        );

        if (!empty($couponResult['valid'])) {
            return response()->json(array_merge($couponResult, ['type' => 'coupon']));
        }

        // 3. Aucun match
        return response()->json([
            'valid' => false,
            'error' => 'Code invalide ou expiré.',
        ]);
    }

    /**
     * Obtenir les réductions long séjour pour une résidence
     */
    public function getLongStayDiscounts(Residence $residence)
    {
        $discounts = $this->pricingService->getAvailableLongStayDiscounts($residence);

        return response()->json([
            'success' => true,
            'discounts' => $discounts,
        ]);
    }

    /**
     * Obtenir l'explication des frais
     */
    public function getFeeExplanation()
    {
        return response()->json([
            'success' => true,
            'fees' => $this->pricingService->getFeeSummary(),
        ]);
    }

    /**
     * Prévisualisation du prix pour un widget
     */
    public function preview(Request $request, Residence $residence)
    {
        $nights = $request->query('nights', 1);
        $guests = $request->query('guests', 1);

        // Prix pour la période demandée en partant d'aujourd'hui
        $checkIn = today()->addDay();
        $checkOut = $checkIn->copy()->addDays($nights);

        try {
            $price = $this->pricingService->calculatePrice(
                $residence,
                $checkIn,
                $checkOut,
                $guests,
                null,
                Auth::user(),
            );

            return response()->json([
                'success' => true,
                'price_per_night' => $price['avg_price_per_night'],
                'total' => $price['total_amount'],
                'nights' => $nights,
                'has_long_stay_discount' => $price['long_stay_discount'] > 0,
                'long_stay_discount_info' => $price['long_stay_discount_info'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Obtenir les prix spéciaux pour une période
     */
    public function getSpecialPrices(Request $request, Residence $residence)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $specialPrices = \App\Models\SpecialPrice::getPricesForDateRange(
            $residence->id,
            Carbon::parse($request->start_date),
            Carbon::parse($request->end_date),
        );

        return response()->json([
            'success' => true,
            'base_price' => $residence->price_per_night,
            'special_prices' => $specialPrices,
        ]);
    }
}
