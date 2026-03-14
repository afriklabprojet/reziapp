<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Residence;
use App\Models\User;

class ListingQualityService
{
    /**
     * Calculer le score de qualité d'une annonce et retourner les recommandations
     */
    public function calculateScore(Residence $residence): array
    {
        $score = 0;
        $maxScore = 100;
        $recommendations = [];

        // Photos (max 20 pts)
        $photoCount = $residence->photos()->count();
        if ($photoCount >= 10) {
            $score += 20;
        } elseif ($photoCount >= 5) {
            $score += 15;
        } elseif ($photoCount >= 3) {
            $score += 10;
        } else {
            $recommendations[] = ['text' => 'Ajoutez au moins 5 photos de qualité', 'impact' => 'high'];
        }

        // Description (max 15 pts)
        $descLength = mb_strlen($residence->description ?? '');
        if ($descLength >= 300) {
            $score += 15;
        } elseif ($descLength >= 150) {
            $score += 10;
        } else {
            $recommendations[] = ['text' => 'Rédigez une description d\'au moins 300 caractères', 'impact' => 'high'];
        }

        // Amenities (max 10 pts)
        $amenityCount = $residence->amenities()->count();
        if ($amenityCount >= 10) {
            $score += 10;
        } elseif ($amenityCount >= 5) {
            $score += 7;
        } else {
            $recommendations[] = ['text' => 'Ajoutez plus d\'équipements (WiFi, TV, cuisine, etc.)', 'impact' => 'medium'];
        }

        // Réservation instantanée (5 pts)
        if ($residence->instant_book) {
            $score += 5;
        } else {
            $recommendations[] = ['text' => 'Activez la réservation instantanée pour plus de réservations', 'impact' => 'high'];
        }

        // Politique d'annulation (5 pts)
        if ($residence->cancellation_policy_id) {
            $score += 5;
        } else {
            $recommendations[] = ['text' => 'Configurez une politique d\'annulation', 'impact' => 'medium'];
        }

        // Prix journalier (5 pts)
        if ($residence->price_per_day && $residence->price_per_day > 0) {
            $score += 5;
        }

        // Horaires check-in/out (5 pts)
        if ($residence->check_in_time && $residence->check_out_time) {
            $score += 5;
        } else {
            $recommendations[] = ['text' => 'Définissez les horaires d\'arrivée et de départ', 'impact' => 'low'];
        }

        // Règles de la maison (5 pts)
        if ($residence->house_rules) {
            $score += 5;
        } else {
            $recommendations[] = ['text' => 'Ajoutez le règlement intérieur', 'impact' => 'low'];
        }

        // Avis (max 10 pts)
        $reviewCount = $residence->reviews_count ?? 0;
        $avgRating   = $residence->average_rating ?? 0;
        if ($reviewCount >= 5 && $avgRating >= 4.0) {
            $score += 10;
        } elseif ($reviewCount >= 2) {
            $score += 5;
        } else {
            $recommendations[] = ['text' => 'Encouragez vos voyageurs à laisser des avis', 'impact' => 'medium'];
        }

        // Temps de réponse (10 pts) — basé sur les analytics si disponibles
        if ($residence->owner && method_exists($residence->owner, 'analytics')) {
            $avgResponse = $residence->owner->analytics?->avg_response_time_hours ?? null;
            if ($avgResponse && $avgResponse < 1) {
                $score += 10;
            } elseif ($avgResponse && $avgResponse < 4) {
                $score += 5;
            } else {
                $recommendations[] = ['text' => 'Répondez aux demandes en moins d\'1 heure', 'impact' => 'high'];
            }
        } else {
            $score += 5; // neutre
        }

        // Guidebook (5 pts)
        $hasGuidebook = \App\Models\Guidebook::where('residence_id', $residence->id)
            ->where('is_published', true)
            ->exists();
        if ($hasGuidebook) {
            $score += 5;
        } else {
            $recommendations[] = ['text' => 'Créez un guide de bienvenue pour vos voyageurs', 'impact' => 'medium'];
        }

        // Visite virtuelle (5 pts bonus)
        if ($residence->virtual_tour_url) {
            $score += 5;
        }

        $score = min($maxScore, $score);

        // Sauvegarder le score
        $residence->update(['listing_quality_score' => $score]);

        return [
            'score'           => $score,
            'max_score'       => $maxScore,
            'percentage'      => round(($score / $maxScore) * 100),
            'level'           => $this->getLevel($score),
            'recommendations' => $recommendations,
        ];
    }

    private function getLevel(int $score): string
    {
        return match (true) {
            $score >= 85 => 'excellent',
            $score >= 70 => 'good',
            $score >= 50 => 'average',
            default      => 'needs_improvement',
        };
    }

    public function getLevelLabel(string $level): string
    {
        return match ($level) {
            'excellent'         => 'Excellent',
            'good'              => 'Bon',
            'average'           => 'Moyen',
            'needs_improvement' => 'À améliorer',
            default             => $level,
        };
    }
}
