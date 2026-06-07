<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Residence;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Moteur de scoring risque actuariel pour les assurances résidences.
 *
 * Score 0-100 : plus le score est élevé, plus le risque est élevé,
 * donc plus la prime d'assurance sera ajustée à la hausse.
 *
 * Inspiré des grilles de risque utilisées par NSIA, SUNU et Allianz
 * pour le marché afrique de l'Ouest (zone CIMA).
 */
class RiskScoringService
{
    // ── Pondérations des facteurs (total = 100 points) ──────────────────
    private const WEIGHTS = [
        'commune_risk'       => 20,  // Localisation géographique / taux de sinistralité
        'property_type'      => 10,  // Type de logement
        'property_age'       => 10,  // Ancienneté estimée du bien
        'occupancy_rate'     => 15,  // Taux d'occupation (location courte durée = plus de risque)
        'claim_history'      => 20,  // Historique de sinistres du propriétaire
        'value_declared'     => 10,  // Valeur déclarée du bien
        'security_features'  => 10,  // Équipements de sécurité (gardien, alarme, etc.)
        'floor_level'        => 5,   // Étage (rez-de-chaussée = plus de risque vol)
    ];

    /**
     * Communes d'Abidjan classées par niveau de risque sinistralité.
     * Source : données historiques + données BCEAO + rapports CIMA.
     * Niveau 1 = faible risque, 5 = risque élevé.
     */
    private const COMMUNE_RISK_LEVELS = [
        // Zone très faible risque
        'Cocody'         => 1,
        'Marcory'        => 1,
        'Plateau'        => 1,
        // Zone faible risque
        'Bingerville'    => 2,
        'Riviera'        => 2,
        'Angré'          => 2,
        'Zone 4'         => 2,
        // Zone risque modéré
        'Abidjan'        => 3,
        'Treichville'    => 3,
        'Adjamé'         => 3,
        'Koumassi'       => 3,
        'Port-Bouët'     => 3,
        // Zone risque élevé
        'Abobo'          => 4,
        'Yopougon'       => 4,
        // Zone très élevé
        'Attécoubé'      => 5,
    ];

    /**
     * Calcule le score de risque complet pour une résidence.
     *
     * @return array{score: int, grade: string, label: string, factors: array, premium_multiplier: float}
     */
    public function calculate(Residence $residence, User $owner): array
    {
        $factors = [];
        $totalScore = 0;

        // ── 1. Risque géographique ────────────────────────────────────────
        $communeScore = $this->scoreCommune($residence->commune ?? $residence->city ?? '');
        $factors['commune_risk'] = [
            'label'       => 'Localisation géographique',
            'value'       => $residence->commune ?? $residence->city ?? 'Inconnue',
            'score'       => $communeScore,
            'max'         => self::WEIGHTS['commune_risk'],
            'description' => $this->getCommuneDescription($communeScore),
        ];
        $totalScore += $communeScore;

        // ── 2. Type de logement ───────────────────────────────────────────
        $typeScore = $this->scorePropertyType($residence->type_location ?? $residence->type ?? '');
        $factors['property_type'] = [
            'label'       => 'Type de logement',
            'value'       => $residence->type_location ?? $residence->type ?? 'Inconnu',
            'score'       => $typeScore,
            'max'         => self::WEIGHTS['property_type'],
            'description' => 'Résidences meublées et hôtels présentent plus de rotation = risque accru',
        ];
        $totalScore += $typeScore;

        // ── 3. Ancienneté estimée ─────────────────────────────────────────
        $ageScore = $this->scorePropertyAge($residence->created_at);
        $factors['property_age'] = [
            'label'       => 'Ancienneté annonce',
            'value'       => $residence->created_at ? $residence->created_at->diffForHumans() : 'Inconnue',
            'score'       => $ageScore,
            'max'         => self::WEIGHTS['property_age'],
            'description' => 'Nouvelles annonces sans historique présentent un risque légèrement supérieur',
        ];
        $totalScore += $ageScore;

        // ── 4. Taux d'occupation ──────────────────────────────────────────
        $occupancyScore = $this->scoreOccupancy($residence);
        $occupancyRate = $this->getOccupancyRate($residence);
        $factors['occupancy_rate'] = [
            'label'       => 'Taux d\'occupation',
            'value'       => $occupancyRate.'%',
            'score'       => $occupancyScore,
            'max'         => self::WEIGHTS['occupancy_rate'],
            'description' => 'Forte occupation = usure accélérée + risque incendie/dommages plus élevé',
        ];
        $totalScore += $occupancyScore;

        // ── 5. Historique sinistres du propriétaire ───────────────────────
        $claimScore = $this->scoreClaimHistory($owner);
        $claimCount = $this->getOwnerClaimCount($owner);
        $factors['claim_history'] = [
            'label'       => 'Historique sinistres',
            'value'       => $claimCount.' sinistre(s) déclaré(s)',
            'score'       => $claimScore,
            'max'         => self::WEIGHTS['claim_history'],
            'description' => 'Historique de sinistres = indicateur de risque futur',
        ];
        $totalScore += $claimScore;

        // ── 6. Valeur du bien ─────────────────────────────────────────────
        $valueScore = $this->scorePropertyValue((float)($residence->price_per_day ?? 0));
        $factors['value_declared'] = [
            'label'       => 'Valeur / Prix par jour',
            'value'       => number_format((float)($residence->price_per_day ?? 0), 0, ',', ' ').' FCFA/jour',
            'score'       => $valueScore,
            'max'         => self::WEIGHTS['value_declared'],
            'description' => 'Bien de haute valeur = exposition financière plus élevée pour l\'assureur',
        ];
        $totalScore += $valueScore;

        // ── 7. Équipements de sécurité ────────────────────────────────────
        $securityScore = $this->scoreSecurityFeatures($residence);
        $factors['security_features'] = [
            'label'       => 'Équipements de sécurité',
            'value'       => $this->getSecurityDescription($residence),
            'score'       => $securityScore,
            'max'         => self::WEIGHTS['security_features'],
            'description' => 'Présence d\'équipements réducteurs de risque',
        ];
        $totalScore += $securityScore;

        // ── 8. Niveau d'étage ─────────────────────────────────────────────
        $floorScore = $this->scoreFloor($residence);
        $factors['floor_level'] = [
            'label'       => 'Niveau / Étage',
            'value'       => 'Non spécifié',
            'score'       => $floorScore,
            'max'         => self::WEIGHTS['floor_level'],
            'description' => 'Rez-de-chaussée = risque d\'intrusion plus élevé',
        ];
        $totalScore += $floorScore;

        // ── Calcul final ──────────────────────────────────────────────────
        $totalScore = min(100, max(0, $totalScore));
        $grade = $this->getGrade($totalScore);

        return [
            'score'              => $totalScore,
            'grade'              => $grade['letter'],
            'label'              => $grade['label'],
            'color'              => $grade['color'],
            'factors'            => $factors,
            'premium_multiplier' => $this->getPremiumMultiplier($totalScore),
            'recommendation'     => $this->getRecommendation($totalScore),
        ];
    }

    // ── Méthodes de scoring individuelles ───────────────────────────────

    private function scoreCommune(string $commune): int
    {
        $commune = trim($commune);
        foreach (self::COMMUNE_RISK_LEVELS as $name => $level) {
            if (stripos($commune, $name) !== false) {
                // Niveau 1=4pts, 2=8pts, 3=12pts, 4=16pts, 5=20pts
                return $level * 4;
            }
        }

        return 12; // risque modéré par défaut (inconnu)
    }

    private function scorePropertyType(string $type): int
    {
        return match(true) {
            str_contains($type, 'hotel')      => 8,  // Forte rotation
            str_contains($type, 'meublée')    => 6,  // Rotation modérée
            str_contains($type, 'apartment')  => 4,  // Location longue durée = moins de risque
            default                           => 5,
        };
    }

    private function scorePropertyAge(?\Carbon\Carbon $createdAt): int
    {
        if (!$createdAt) {
            return 7;
        }
        $months = $createdAt->diffInMonths(now());
        if ($months < 3) {
            return 8;
        }  // Nouveau = pas d'historique
        if ($months < 12) {
            return 5;
        }
        if ($months < 36) {
            return 3;
        }

        return 2; // Etabli depuis longtemps = moins de risque
    }

    private function scoreOccupancy(Residence $residence): int
    {
        $rate = $this->getOccupancyRate($residence);
        if ($rate >= 80) {
            return 15;
        }
        if ($rate >= 60) {
            return 10;
        }
        if ($rate >= 40) {
            return 7;
        }
        if ($rate >= 20) {
            return 4;
        }

        return 2;
    }

    private function getOccupancyRate(Residence $residence): int
    {
        // Calcul basé sur les 90 derniers jours
        try {
            $totalDays = 90;
            $bookedDays = $residence->bookings()
                ->whereIn('status', ['confirmed', 'completed'])
                ->where('check_in', '>=', now()->subDays(90))
                ->get()
                ->sum(fn ($b) => $b->check_in->diffInDays($b->check_out ?? $b->check_in->addDay()));

            return min(100, (int)(($bookedDays / $totalDays) * 100));
        } catch (\Throwable $exception) {
            Log::error('RiskScoring: occupancy score calculation failed', [
                'residence_id' => $residence->id ?? null,
                'error' => $exception->getMessage(),
            ]);
            report($exception);

            return 50;
        }
    }

    private function scoreClaimHistory(User $owner): int
    {
        $count = $this->getOwnerClaimCount($owner);
        if ($count === 0) {
            return 0;
        }
        if ($count === 1) {
            return 8;
        }
        if ($count === 2) {
            return 14;
        }

        return 20; // 3+ sinistres = score max
    }

    private function getOwnerClaimCount(User $owner): int
    {
        try {
            return \App\Models\InsuranceClaim::whereHas(
                'bookingInsurance.booking.residence',
                fn ($q) =>
                $q->where('owner_id', $owner->id),
            )->whereIn('status', ['approved', 'paid'])->count()
            +
            \App\Models\InsuranceSubscription::where('owner_id', $owner->id)->sum('claim_count');
        } catch (\Throwable $exception) {
            Log::error('RiskScoring: claim history count failed', [
                'owner_id' => $owner->id ?? null,
                'error' => $exception->getMessage(),
            ]);
            report($exception);

            return 0;
        }
    }

    private function scorePropertyValue(float $pricePerDay): int
    {
        if ($pricePerDay <= 0) {
            return 5;
        }
        if ($pricePerDay < 20000) {
            return 2;
        }   // Faible valeur
        if ($pricePerDay < 50000) {
            return 5;
        }   // Valeur moyenne
        if ($pricePerDay < 100000) {
            return 7;
        }   // Haute valeur

        return 10;                               // Très haute valeur
    }

    private function scoreSecurityFeatures(Residence $residence): int
    {
        // Réduction de risque si équipements de sécurité détectés dans les amenities
        try {
            $amenityNames = $residence->amenities()->pluck('name')->map(fn ($n) => strtolower($n))->toArray();
            $securityKeywords = ['gardien', 'gardiennage', 'alarme', 'interphone', 'digicode', 'caméra', 'vigile', 'sécurité', 'portail', 'badge'];
            $found = 0;
            foreach ($securityKeywords as $kw) {
                foreach ($amenityNames as $amenity) {
                    if (str_contains($amenity, $kw)) {
                        $found++;
                        break;
                    }
                }
            }
            // Plus d'équipements = moins de risque (score inversé)
            if ($found >= 3) {
                return 0;
            }
            if ($found === 2) {
                return 3;
            }
            if ($found === 1) {
                return 6;
            }

            return 10; // aucun équipement de sécurité
        } catch (\Throwable $exception) {
            Log::error('RiskScoring: security features score failed', [
                'residence_id' => $residence->id ?? null,
                'error' => $exception->getMessage(),
            ]);
            report($exception);

            return 7;
        }
    }

    private function getSecurityDescription(Residence $residence): string
    {
        try {
            $amenityNames = $residence->amenities()->pluck('name')->map(fn ($n) => strtolower($n))->toArray();
            $securityKeywords = ['gardien', 'alarme', 'interphone', 'caméra', 'sécurité'];
            $found = [];
            foreach ($securityKeywords as $kw) {
                foreach ($amenityNames as $amenity) {
                    if (str_contains($amenity, $kw)) {
                        $found[] = $kw;
                        break;
                    }
                }
            }

            return empty($found) ? 'Aucun équipement détecté' : implode(', ', $found);
        } catch (\Throwable $exception) {
            Log::error('RiskScoring: security description failed', [
                'residence_id' => $residence->id ?? null,
                'error' => $exception->getMessage(),
            ]);
            report($exception);

            return 'Non renseigné';
        }
    }

    private function scoreFloor(Residence $residence): int
    {
        // Sans données d'étage précises, on utilise un score moyen
        return 3;
    }

    // ── Helpers de classification ────────────────────────────────────────

    private function getGrade(int $score): array
    {
        if ($score <= 20) {
            return ['letter' => 'A', 'label' => 'Risque très faible', 'color' => 'green'];
        }
        if ($score <= 35) {
            return ['letter' => 'B', 'label' => 'Risque faible',      'color' => 'emerald'];
        }
        if ($score <= 50) {
            return ['letter' => 'C', 'label' => 'Risque modéré',      'color' => 'yellow'];
        }
        if ($score <= 65) {
            return ['letter' => 'D', 'label' => 'Risque élevé',       'color' => 'orange'];
        }

        return              ['letter' => 'E', 'label' => 'Risque très élevé',        'color' => 'red'];
    }

    /**
     * Multiplicateur de prime basé sur le score de risque.
     * Score 0  → ×0.7  (prime réduite -30%)
     * Score 50 → ×1.0  (prime de base)
     * Score 100→ ×1.8  (prime majorée +80%)
     */
    public function getPremiumMultiplier(int $score): float
    {
        return round(0.7 + ($score / 100) * 1.1, 2);
    }

    private function getCommuneDescription(int $score): string
    {
        return match(true) {
            $score <= 4  => 'Zone à faible sinistralité historique',
            $score <= 8  => 'Zone à sinistralité modérée',
            $score <= 12 => 'Zone à sinistralité moyenne',
            $score <= 16 => 'Zone à sinistralité élevée',
            default      => 'Zone à forte sinistralité historique',
        };
    }

    private function getRecommendation(int $score): string
    {
        if ($score <= 20) {
            return 'Profil de risque excellent. Vous êtes éligible aux meilleures conditions tarifaires.';
        }
        if ($score <= 35) {
            return 'Bon profil de risque. Nous recommandons une couverture Standard.';
        }
        if ($score <= 50) {
            return 'Profil de risque modéré. Une couverture Standard ou Premium est recommandée.';
        }
        if ($score <= 65) {
            return 'Profil de risque élevé. Couverture Premium fortement recommandée. Des mesures de sécurité réduiraient votre prime.';
        }

        return 'Profil de risque très élevé. Couverture Premium obligatoire. Un audit de sécurité est conseillé avant souscription.';
    }
}
