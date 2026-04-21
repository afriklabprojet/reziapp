<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\InsuranceSubscription;
use App\Models\Residence;

/**
 * Moteur de tarification actuarielle pour les assurances résidences.
 *
 * Methodology: Loss Cost Model adapté marché CIMA / Afrique de l'Ouest.
 * Prime Pure + Chargements (frais de gestion, bénéfice, taxes).
 *
 * Taux de référence ajustés pour le marché ivoirien (CIMA Art. 13).
 */
class InsurancePricingService
{
    // ── Paramètres actuariels de base ────────────────────────────────────

    /**
     * Taux de prime pure mensuelle par rapport à la valeur assurée.
     * (sinistralité historique estimée marché CI + marge actuarielle)
     */
    private const BASE_RATES = [
        InsuranceSubscription::TYPE_BASIC    => 0.0025,  // 0.25%/mois de la valeur assurée
        InsuranceSubscription::TYPE_STANDARD => 0.0040,  // 0.40%/mois
        InsuranceSubscription::TYPE_PREMIUM  => 0.0060,  // 0.60%/mois
    ];

    /**
     * Valeur assurée estimée (substitut: prix/jour × jours annuels moyens occupés).
     * Utilisée quand la valeur réelle du bien n'est pas déclarée.
     */
    private const ESTIMATED_ANNUAL_DAYS = [
        InsuranceSubscription::TYPE_BASIC    => 180,  // 50% occupation annuelle estimée
        InsuranceSubscription::TYPE_STANDARD => 240,  // 66%
        InsuranceSubscription::TYPE_PREMIUM  => 300,  // 82%
    ];

    /**
     * Chargements sur la prime pure (%).
     * Frais de gestion + frais d'acquisition + bénéfice + taxe CIMA.
     */
    private const LOADINGS = [
        'administrative'  => 0.20,  // 20% frais de gestion
        'acquisition'     => 0.10,  // 10% commission courtier
        'profit_margin'   => 0.08,  // 8% marge bénéficiaire
        'cima_tax'        => 0.04,  // 4% taxe CIMA art. 422
        'security_fund'   => 0.005, // 0.5% fonds de garantie CIMA
    ];

    /** Prime minimale mensuelle en FCFA (plancher réglementaire estimé) */
    private const MIN_PREMIUM = [
        InsuranceSubscription::TYPE_BASIC    => 2_500,
        InsuranceSubscription::TYPE_STANDARD => 5_000,
        InsuranceSubscription::TYPE_PREMIUM  => 10_000,
    ];

    /** Prime maximale mensuelle en FCFA (plafond commercial) */
    private const MAX_PREMIUM = [
        InsuranceSubscription::TYPE_BASIC    => 50_000,
        InsuranceSubscription::TYPE_STANDARD => 150_000,
        InsuranceSubscription::TYPE_PREMIUM  => 500_000,
    ];

    /**
     * Capitaux maximaux assurés par type de couverture.
     */
    private const MAX_COVERAGE = [
        InsuranceSubscription::TYPE_BASIC    => 5_000_000,
        InsuranceSubscription::TYPE_STANDARD => 20_000_000,
        InsuranceSubscription::TYPE_PREMIUM  => 100_000_000,
    ];

    /**
     * Franchises par défaut (FCFA) - montant à la charge de l'assuré.
     */
    private const DEDUCTIBLES = [
        InsuranceSubscription::TYPE_BASIC    => 50_000,
        InsuranceSubscription::TYPE_STANDARD => 25_000,
        InsuranceSubscription::TYPE_PREMIUM  => 10_000,
    ];

    public function __construct(
        private RiskScoringService $riskScoring,
    ) {
    }

    /**
     * Calcule la prime mensuelle suggérée pour une résidence.
     *
     * @return array{
     *   coverage_type: string,
     *   pure_premium: float,
     *   loadings_amount: float,
     *   suggested_premium: float,
     *   min_premium: float,
     *   max_coverage: int,
     *   deductible: int,
     *   risk_score: int,
     *   risk_multiplier: float,
     *   loading_breakdown: array,
     *   annual_cost: float,
     *   estimated_value: float
     * }
     */
    public function calculate(Residence $residence, \App\Models\User $owner, string $coverageType = InsuranceSubscription::TYPE_STANDARD): array
    {
        // 1. Calcul du score de risque
        $riskResult = $this->riskScoring->calculate($residence, $owner);
        $riskMultiplier = $riskResult['premium_multiplier'];

        // 2. Valeur assurée estimée
        $pricePerDay = (float)($residence->price_per_day ?? 0);
        $annualDays = self::ESTIMATED_ANNUAL_DAYS[$coverageType] ?? 200;
        $estimatedAnnualRevenue = $pricePerDay * $annualDays;
        $insuredValue = max($estimatedAnnualRevenue, 500_000); // minimum 500K FCFA

        // 3. Prime pure (avant chargements)
        $baseRate = self::BASE_RATES[$coverageType] ?? self::BASE_RATES[InsuranceSubscription::TYPE_STANDARD];
        $purePremium = ($insuredValue / 12) * $baseRate;

        // 4. Application du multiplicateur de risque
        $adjustedPurePremium = $purePremium * $riskMultiplier;

        // 5. Chargements
        $totalLoadingRate = array_sum(self::LOADINGS);
        $loadingsAmount = $adjustedPurePremium * $totalLoadingRate;
        $grossPremium = $adjustedPurePremium + $loadingsAmount;

        // 6. Arrondi à 500 FCFA supérieur (pratique du marché)
        $roundedPremium = ceil($grossPremium / 500) * 500;

        // 7. Application des planchers et plafonds
        $minPremium = self::MIN_PREMIUM[$coverageType] ?? 5_000;
        $maxPremium = self::MAX_PREMIUM[$coverageType] ?? 150_000;
        $finalPremium = min($maxPremium, max($minPremium, $roundedPremium));

        // 8. Détail des chargements
        $loadingBreakdown = [];
        foreach (self::LOADINGS as $key => $rate) {
            $loadingBreakdown[$key] = [
                'label'  => $this->getLoadingLabel($key),
                'rate'   => $rate * 100 .'%',
                'amount' => round($adjustedPurePremium * $rate),
            ];
        }

        return [
            'coverage_type'      => $coverageType,
            'coverage_label'     => InsuranceSubscription::COVERAGE_TYPES[$coverageType] ?? $coverageType,
            'pure_premium'       => round($purePremium),
            'risk_adjustment'    => round($adjustedPurePremium - $purePremium),
            'loadings_amount'    => round($loadingsAmount),
            'suggested_premium'  => $finalPremium,
            'min_premium'        => $minPremium,
            'max_premium'        => $maxPremium,
            'max_coverage'       => self::MAX_COVERAGE[$coverageType] ?? 20_000_000,
            'deductible'         => self::DEDUCTIBLES[$coverageType] ?? 25_000,
            'risk_score'         => $riskResult['score'],
            'risk_grade'         => $riskResult['grade'],
            'risk_label'         => $riskResult['label'],
            'risk_multiplier'    => $riskMultiplier,
            'loading_breakdown'  => $loadingBreakdown,
            'total_loading_rate' => round($totalLoadingRate * 100, 1).'%',
            'annual_cost'        => $finalPremium * 12,
            'estimated_value'    => round($insuredValue),
            'risk_factors'       => $riskResult['factors'],
            'recommendation'     => $riskResult['recommendation'],
        ];
    }

    /**
     * Calcule les primes pour tous les types de couverture en une seule fois.
     *
     * @return array<string, array> Keyed by coverage type
     */
    public function calculateAll(Residence $residence, \App\Models\User $owner): array
    {
        $types = [
            InsuranceSubscription::TYPE_BASIC,
            InsuranceSubscription::TYPE_STANDARD,
            InsuranceSubscription::TYPE_PREMIUM,
        ];

        $results = [];
        foreach ($types as $type) {
            $results[$type] = $this->calculate($residence, $owner, $type);
        }

        return $results;
    }

    /**
     * Calcule la prime d'assurance voyage pour une réservation.
     * (optionnelle, souscrite par le voyageur)
     *
     * @param float $bookingAmount Montant total de la réservation
     * @param int $nights Nombre de nuits
     * @return array
     */
    public function calculateBookingPremium(float $bookingAmount, int $nights): array
    {
        // Taux de base : 3.5% du montant de réservation (marché standard CI)
        $baseRate = 0.035;
        // Ajustement durée (séjours longs = légèrement moins cher relatif)
        $durationFactor = match(true) {
            $nights <= 3  => 1.20,  // Courte durée = coût relatif plus élevé (frais fixes)
            $nights <= 7  => 1.00,
            $nights <= 30 => 0.85,
            default       => 0.75,
        };

        $purePremium   = $bookingAmount * $baseRate * $durationFactor;
        $grossPremium  = $purePremium * (1 + array_sum(self::LOADINGS));
        $finalPremium  = max(1_500, ceil($grossPremium / 100) * 100); // min 1500 FCFA

        return [
            'premium'           => $finalPremium,
            'coverage_amount'   => min($bookingAmount * 3, 2_000_000), // jusqu'à 3x le montant, max 2M FCFA
            'duration_nights'   => $nights,
            'rate_applied'      => round($baseRate * $durationFactor * 100, 2).'%',
            'covers'            => ['annulation', 'bagages', 'dommages_locatifs', 'responsabilite_civile'],
        ];
    }

    private function getLoadingLabel(string $key): string
    {
        return match($key) {
            'administrative' => 'Frais de gestion',
            'acquisition'    => 'Commission de distribution',
            'profit_margin'  => 'Marge bénéficiaire',
            'cima_tax'       => 'Taxe CIMA (art. 422)',
            'security_fund'  => 'Fonds de garantie CIMA',
            default          => $key,
        };
    }
}
