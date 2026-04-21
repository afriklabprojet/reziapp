<?php

namespace App\Services;

use App\Models\MarketPriceData;
use App\Models\Residence;
use Illuminate\Support\Collection;

class RealEstateService
{
    // Types de location disponibles en Afrique de l'Ouest
    public const RENTAL_TYPES = [
        'standard' => [
            'label' => 'Location standard',
            'description' => 'Location mensuelle classique avec bail',
            'min_duration' => 1, // mois
            'deposit_months' => 2,
        ],
        'short_term' => [
            'label' => 'Location courte durée',
            'description' => 'Location à la journée ou semaine (meublé)',
            'min_duration' => 1, // jours
            'deposit_months' => 0,
        ],
        'colocation' => [
            'label' => 'Colocation',
            'description' => 'Chambre partagée dans un appartement',
            'min_duration' => 1,
            'deposit_months' => 1,
        ],
        'corporate' => [
            'label' => 'Location entreprise',
            'description' => 'Pour entreprises et expatriés',
            'min_duration' => 3,
            'deposit_months' => 2,
        ],
        'seasonal' => [
            'label' => 'Location saisonnière',
            'description' => 'Location pour événements (CAN, fêtes, etc.)',
            'min_duration' => 1,
            'deposit_months' => 1,
        ],
    ];

    // Types de bail
    public const LEASE_TYPES = [
        'verbal' => 'Accord verbal',
        'written' => 'Bail écrit simple',
        'notarized' => 'Bail notarié',
        'professional' => 'Bail professionnel',
    ];

    // Locataires cibles
    public const TARGET_TENANTS = [
        'individual' => 'Particuliers',
        'family' => 'Familles',
        'student' => 'Étudiants',
        'professional' => 'Professionnels',
        'expat' => 'Expatriés',
        'company' => 'Entreprises',
        'tourist' => 'Touristes',
    ];

    /**
     * Calculer le dépôt de garantie suggéré
     */
    public function calculateSuggestedDeposit(Residence $residence): array
    {
        $rentalType = $residence->rental_type ?? 'standard';
        $config = self::RENTAL_TYPES[$rentalType] ?? self::RENTAL_TYPES['standard'];

        $monthlyPrice = $residence->price;
        $suggestedDeposit = $monthlyPrice * $config['deposit_months'];

        return [
            'suggested_amount' => $suggestedDeposit,
            'months' => $config['deposit_months'],
            'is_negotiable' => $residence->deposit_negotiable ?? true,
            'terms' => $residence->deposit_terms,
            'min_amount' => $suggestedDeposit * 0.5, // 50% minimum négociable
            'max_amount' => $suggestedDeposit * 1.5, // 150% maximum
        ];
    }

    /**
     * Obtenir les résidences similaires (pour comparaison de prix)
     */
    public function getSimilarResidences(Residence $residence, int $limit = 5): Collection
    {
        return Residence::where('id', '!=', $residence->id)
            ->where('commune', $residence->commune)
            ->where('type', $residence->type)
            ->where('status', 'approved')
            ->whereBetween('bedrooms', [
                max(1, $residence->bedrooms - 1),
                $residence->bedrooms + 1,
            ])
            ->whereBetween('price', [
                $residence->price * 0.7,
                $residence->price * 1.3,
            ])
            ->orderByRaw('ABS(price - ?)', [$residence->price])
            ->limit($limit)
            ->get();
    }

    /**
     * Analyser un prix par rapport au marché
     */
    public function analyzePricing(Residence $residence): array
    {
        $marketData = MarketPriceData::getMarketPrice(
            $residence->commune ?? $residence->city,
            $residence->type,
            $residence->bedrooms ?? 1,
            $residence->country_code ?? 'CI',
        );

        if (!$marketData) {
            return [
                'status' => 'insufficient_data',
                'message' => 'Pas assez de données pour cette zone',
                'recommendation' => null,
            ];
        }

        $price = $residence->price;
        $avgPrice = $marketData['avg'];
        $deviation = (($price - $avgPrice) / $avgPrice) * 100;

        // Déterminer le statut
        if ($deviation < -20) {
            $status = 'very_competitive';
            $recommendation = 'Votre prix est très compétitif. Vous pourriez l\'augmenter légèrement.';
        } elseif ($deviation < -10) {
            $status = 'competitive';
            $recommendation = 'Votre prix est bien positionné sur le marché.';
        } elseif ($deviation < 10) {
            $status = 'market_rate';
            $recommendation = 'Votre prix est conforme au marché.';
        } elseif ($deviation < 20) {
            $status = 'above_market';
            $recommendation = 'Votre prix est légèrement au-dessus du marché. Assurez-vous que vos équipements justifient ce prix.';
        } else {
            $status = 'premium';
            $recommendation = 'Votre prix est élevé. Considérez une réduction ou mettez en avant des équipements premium.';
        }

        return [
            'status' => $status,
            'your_price' => $price,
            'market_avg' => $avgPrice,
            'market_min' => $marketData['min'],
            'market_max' => $marketData['max'],
            'deviation_percent' => round($deviation, 1),
            'sample_size' => $marketData['sample_size'],
            'recommendation' => $recommendation,
        ];
    }

    /**
     * Générer des suggestions d'amélioration
     */
    public function getImprovementSuggestions(Residence $residence): array
    {
        $suggestions = [];

        // Vérifier les photos
        $photoCount = $residence->photos()->count();
        if ($photoCount < 5) {
            $suggestions[] = [
                'type' => 'photos',
                'priority' => 'high',
                'title' => 'Ajouter plus de photos',
                'description' => "Vous avez {$photoCount} photo(s). Les annonces avec 10+ photos reçoivent 3x plus de vues.",
                'impact' => '+200% vues estimées',
            ];
        }

        // Vérifier la description
        $descLength = strlen($residence->description ?? '');
        if ($descLength < 200) {
            $suggestions[] = [
                'type' => 'description',
                'priority' => 'medium',
                'title' => 'Enrichir la description',
                'description' => 'Une description détaillée améliore votre référencement et la confiance des locataires.',
                'impact' => '+50% conversions',
            ];
        }

        // Vérifier les équipements
        $amenitiesCount = $residence->amenities()->count();
        if ($amenitiesCount < 5) {
            $suggestions[] = [
                'type' => 'amenities',
                'priority' => 'medium',
                'title' => 'Lister plus d\'équipements',
                'description' => "Vous avez {$amenitiesCount} équipement(s). Ajoutez climatisation, WiFi, parking, etc.",
                'impact' => '+30% intérêt',
            ];
        }

        // Vérifier le propriétaire
        $owner = $residence->user;
        if (!$owner->identity_verified_at) {
            $suggestions[] = [
                'type' => 'verification',
                'priority' => 'high',
                'title' => 'Vérifier votre identité',
                'description' => 'Les annonces de propriétaires vérifiés reçoivent plus de demandes.',
                'impact' => '+80% confiance',
            ];
        }

        // Vérifier la réactivité
        if (($residence->performance_score ?? 0) < 70) {
            $suggestions[] = [
                'type' => 'responsiveness',
                'priority' => 'high',
                'title' => 'Améliorer votre réactivité',
                'description' => 'Répondez aux messages dans l\'heure pour un meilleur classement.',
                'impact' => '+40% réservations',
            ];
        }

        // Vérifier le prix
        $pricing = $this->analyzePricing($residence);
        if ($pricing['status'] === 'premium') {
            $suggestions[] = [
                'type' => 'pricing',
                'priority' => 'medium',
                'title' => 'Revoir votre tarification',
                'description' => "Votre prix est {$pricing['deviation_percent']}% au-dessus du marché.",
                'impact' => 'Compétitivité accrue',
            ];
        }

        // Trier par priorité
        usort($suggestions, function ($a, $b) {
            $priorities = ['high' => 1, 'medium' => 2, 'low' => 3];

            return ($priorities[$a['priority']] ?? 3) <=> ($priorities[$b['priority']] ?? 3);
        });

        return $suggestions;
    }

    /**
     * Calculer le score de qualité d'une annonce
     */
    public function calculateQualityScore(Residence $residence): array
    {
        $score = 0;
        $maxScore = 100;
        $breakdown = [];

        // Photos (max 25 points)
        $photoCount = $residence->photos()->count();
        $photoScore = min(25, $photoCount * 2.5);
        $score += $photoScore;
        $breakdown['photos'] = ['score' => $photoScore, 'max' => 25, 'label' => 'Photos'];

        // Description (max 20 points)
        $descLength = strlen($residence->description ?? '');
        $descScore = min(20, $descLength / 25);
        $score += $descScore;
        $breakdown['description'] = ['score' => $descScore, 'max' => 20, 'label' => 'Description'];

        // Équipements (max 15 points)
        $amenitiesCount = $residence->amenities()->count();
        $amenitiesScore = min(15, $amenitiesCount * 1.5);
        $score += $amenitiesScore;
        $breakdown['amenities'] = ['score' => $amenitiesScore, 'max' => 15, 'label' => 'Équipements'];

        // Localisation (max 10 points)
        $locationScore = ($residence->latitude && $residence->longitude) ? 10 : 0;
        $score += $locationScore;
        $breakdown['location'] = ['score' => $locationScore, 'max' => 10, 'label' => 'Géolocalisation'];

        // Informations complètes (max 15 points)
        $infoScore = 0;
        if ($residence->bedrooms) {
            $infoScore += 3;
        }
        if ($residence->bathrooms) {
            $infoScore += 3;
        }
        if ($residence->surface) {
            $infoScore += 3;
        }
        if ($residence->rental_type) {
            $infoScore += 3;
        }
        if ($residence->lease_type) {
            $infoScore += 3;
        }
        $score += $infoScore;
        $breakdown['info'] = ['score' => $infoScore, 'max' => 15, 'label' => 'Informations'];

        // Propriétaire vérifié (max 15 points)
        $ownerScore = 0;
        if ($residence->user->phone_verified) {
            $ownerScore += 5;
        }
        if ($residence->user->identity_verified_at) {
            $ownerScore += 10;
        }
        $score += $ownerScore;
        $breakdown['owner'] = ['score' => $ownerScore, 'max' => 15, 'label' => 'Propriétaire'];

        return [
            'total_score' => round($score),
            'max_score' => $maxScore,
            'percentage' => round(($score / $maxScore) * 100),
            'grade' => $this->getGrade($score),
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Obtenir la note lettrée
     */
    protected function getGrade(float $score): string
    {
        if ($score >= 90) {
            return 'A+';
        }
        if ($score >= 80) {
            return 'A';
        }
        if ($score >= 70) {
            return 'B+';
        }
        if ($score >= 60) {
            return 'B';
        }
        if ($score >= 50) {
            return 'C';
        }
        if ($score >= 40) {
            return 'D';
        }

        return 'F';
    }

    /**
     * Estimer les revenus potentiels
     */
    public function estimateRevenue(Residence $residence, string $period = 'monthly'): array
    {
        $price = $residence->price;
        $rentalType = $residence->rental_type ?? 'standard';

        // Taux d'occupation estimé selon le type
        $occupancyRates = [
            'standard' => 0.95,      // 95% (bail long terme)
            'short_term' => 0.65,    // 65% (vacances, rotation)
            'colocation' => 0.85,    // 85%
            'corporate' => 0.90,     // 90%
            'seasonal' => 0.50,      // 50% (très saisonnier)
        ];

        $occupancy = $occupancyRates[$rentalType] ?? 0.80;

        // Calcul selon la période
        if ($rentalType === 'short_term') {
            // Prix à la nuit
            $dailyRate = $price;
            $monthlyRevenue = $dailyRate * 30 * $occupancy;
            $yearlyRevenue = $monthlyRevenue * 12;
        } else {
            // Prix mensuel
            $monthlyRevenue = $price * $occupancy;
            $yearlyRevenue = $monthlyRevenue * 12;
        }

        // Déduire les frais estimés (15% pour maintenance, vacance, etc.)
        $expenses = $yearlyRevenue * 0.15;
        $netYearly = $yearlyRevenue - $expenses;

        return [
            'gross_monthly' => round($monthlyRevenue),
            'gross_yearly' => round($yearlyRevenue),
            'estimated_expenses' => round($expenses),
            'net_yearly' => round($netYearly),
            'occupancy_rate' => $occupancy * 100,
            'rental_type' => self::RENTAL_TYPES[$rentalType]['label'] ?? 'Standard',
        ];
    }
}
