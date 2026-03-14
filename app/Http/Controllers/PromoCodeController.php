<?php

namespace App\Http\Controllers;

use App\Services\PromoCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PromoCodeController extends Controller
{
    protected PromoCodeService $promoCodeService;

    public function __construct(PromoCodeService $promoCodeService)
    {
        $this->promoCodeService = $promoCodeService;
    }

    /**
     * Obtenir les codes promo actifs publics
     */
    public function getPublicCodes()
    {
        $codes = $this->promoCodeService->getActiveCodes();

        return response()->json([
            'success' => true,
            'codes' => $codes->map(function ($code) {
                return [
                    'code' => $code->code,
                    'name' => $code->name,
                    'description' => $code->description,
                    'value' => $code->getFormattedValue(),
                    'valid_until' => $code->valid_until?->format('d/m/Y'),
                ];
            }),
        ]);
    }
}
