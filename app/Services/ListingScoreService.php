<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Residence;

/**
 * Service de calcul du score qualité d'une annonce (Listing Score).
 *
 * Grille de notation (total = 100 pts) :
 * ─── Photos (25 pts) ────────────────────────────────────
 *   ≥10 photos        → 25pts  |  ≥5 → 15pts  |  ≥1 → 5pts
 *   Photo principale  → +5pts  (sur bonus)
 * ─── Description (20 pts) ──────────────────────────────
 *   Titre ≥ 30 cars   → 5pts
 *   Description ≥300  → 10pts
 *   Description ≥600  → +5pts bonus
 * ─── Informations clés (20 pts) ────────────────────────
 *   Prix défini       → 5pts
 *   Surface renseignée → 5pts
 *   Chambres          → 5pts
 *   Type résidence    → 5pts
 * ─── Localisation (15 pts) ─────────────────────────────
 *   Adresse complète  → 5pts
 *   Coordonnées GPS   → 5pts
 *   Commune renseignée → 5pts
 * ─── Équipements (10 pts) ──────────────────────────────
 *   ≥5 équipements    → 10pts  |  ≥3 → 5pts
 * ─── Réputation (10 pts) ───────────────────────────────
 *   ≥5 avis           → 5pts
 *   Note ≥ 4.0        → 5pts
 */
class ListingScoreService
{
    /**
     * Calculer et sauvegarder le score d'une résidence.
     */
    public function compute(Residence $residence): array
    {
        $residence->load(['photos', 'amenities', 'reviews']);

        $breakdown = [
            'photos'      => $this->scorePhotos($residence),
            'description' => $this->scoreDescription($residence),
            'information' => $this->scoreInformation($residence),
            'location'    => $this->scoreLocation($residence),
            'amenities'   => $this->scoreAmenities($residence),
            'reputation'  => $this->scoreReputation($residence),
        ];

        $total = min(100, array_sum(array_column($breakdown, 'score')));

        $result = [
            'score'      => $total,
            'label'      => $this->getLabel($total),
            'color'      => $this->getColor($total),
            'breakdown'  => $breakdown,
            'tips'       => $this->generateTips($breakdown),
            'computed_at' => now()->toISOString(),
        ];

        // Persister dans la base
        $residence->update([
            'listing_score'              => $total,
            'listing_score_breakdown'    => $result,
            'listing_score_computed_at'  => now(),
        ]);

        return $result;
    }

    // ===== CRITÈRES =====

    private function scorePhotos(Residence $residence): array
    {
        $count = $residence->photos->count();
        $hasPrimary = $residence->photos->where('is_primary', true)->isNotEmpty();

        $score = match (true) {
            $count >= 10 => 25,
            $count >= 5  => 15,
            $count >= 1  => 5,
            default      => 0,
        };

        return [
            'score'   => $score,
            'max'     => 25,
            'label'   => 'Photos',
            'detail'  => "{$count} photo(s)",
            'ok'      => $count >= 5,
        ];
    }

    private function scoreDescription(Residence $residence): array
    {
        $titleLen = mb_strlen($residence->title ?? '');
        $descLen  = mb_strlen($residence->description ?? '');

        $score = 0;
        if ($titleLen >= 30)  { $score += 5; }
        if ($descLen >= 300)  { $score += 10; }
        if ($descLen >= 600)  { $score += 5; }

        return [
            'score'  => min(20, $score),
            'max'    => 20,
            'label'  => 'Description',
            'detail' => "Titre: {$titleLen} car. | Desc: {$descLen} car.",
            'ok'     => $score >= 15,
        ];
    }

    private function scoreInformation(Residence $residence): array
    {
        $score = 0;
        if ($residence->price_per_night > 0) { $score += 5; }
        if ($residence->surface > 0)          { $score += 5; }
        if ($residence->bedrooms > 0)         { $score += 5; }
        if ($residence->type)                  { $score += 5; }

        return [
            'score'  => $score,
            'max'    => 20,
            'label'  => 'Informations',
            'detail' => "Prix, surface, chambres, type",
            'ok'     => $score >= 15,
        ];
    }

    private function scoreLocation(Residence $residence): array
    {
        $score = 0;
        if (! empty($residence->address))   { $score += 5; }
        if ($residence->latitude && $residence->longitude) { $score += 5; }
        if (! empty($residence->commune))   { $score += 5; }

        return [
            'score'  => $score,
            'max'    => 15,
            'label'  => 'Localisation',
            'detail' => $residence->commune . ', Abidjan',
            'ok'     => $score >= 10,
        ];
    }

    private function scoreAmenities(Residence $residence): array
    {
        $count = $residence->amenities->count();

        $score = match (true) {
            $count >= 5 => 10,
            $count >= 3 => 5,
            default     => 0,
        };

        return [
            'score'  => $score,
            'max'    => 10,
            'label'  => 'Équipements',
            'detail' => "{$count} équipement(s)",
            'ok'     => $count >= 3,
        ];
    }

    private function scoreReputation(Residence $residence): array
    {
        $reviewCount = $residence->reviews->count();
        $avgRating   = $residence->reviews->avg('rating') ?? 0;

        $score = 0;
        if ($reviewCount >= 5) { $score += 5; }
        if ($avgRating >= 4.0) { $score += 5; }

        return [
            'score'  => $score,
            'max'    => 10,
            'label'  => 'Réputation',
            'detail' => "{$reviewCount} avis | Note: " . number_format($avgRating, 1),
            'ok'     => $score >= 5,
        ];
    }

    // ===== CONSEILS =====

    private function generateTips(array $breakdown): array
    {
        $tips = [];

        if (! $breakdown['photos']['ok']) {
            $tips[] = [
                'priority' => 'high',
                'icon'     => '📸',
                'message'  => 'Ajoutez au moins 5 photos de qualité (salon, chambre, cuisine, salle de bain, extérieur)',
            ];
        }

        if (! $breakdown['description']['ok']) {
            $tips[] = [
                'priority' => 'high',
                'icon'     => '✍️',
                'message'  => 'Rédigez une description détaillée d\'au moins 300 caractères pour rassurer les locataires',
            ];
        }

        if (! $breakdown['location']['ok']) {
            $tips[] = [
                'priority' => 'high',
                'icon'     => '📍',
                'message'  => 'Renseignez l\'adresse complète et les coordonnées GPS pour apparaître sur la carte',
            ];
        }

        if (! $breakdown['amenities']['ok']) {
            $tips[] = [
                'priority' => 'medium',
                'icon'     => '🛋️',
                'message'  => 'Listez vos équipements (WiFi, climatisation, cuisine équipée...) pour attirer plus de locataires',
            ];
        }

        if (! $breakdown['information']['ok']) {
            $tips[] = [
                'priority' => 'medium',
                'icon'     => '📋',
                'message'  => 'Complétez la surface, le nombre de chambres et le type de logement',
            ];
        }

        if (! $breakdown['reputation']['ok']) {
            $tips[] = [
                'priority' => 'low',
                'icon'     => '⭐',
                'message'  => 'Encouragez vos premiers locataires à laisser un avis après leur séjour',
            ];
        }

        return $tips;
    }

    // ===== LABELS =====

    private function getLabel(int $score): string
    {
        return match (true) {
            $score >= 90 => 'Excellent',
            $score >= 75 => 'Très bien',
            $score >= 60 => 'Bien',
            $score >= 40 => 'À améliorer',
            default      => 'Incomplet',
        };
    }

    private function getColor(int $score): string
    {
        return match (true) {
            $score >= 90 => 'green',
            $score >= 75 => 'teal',
            $score >= 60 => 'blue',
            $score >= 40 => 'yellow',
            default      => 'red',
        };
    }
}
