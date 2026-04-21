<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Residence;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Moteur de matching IA locataire → résidence.
 *
 * Algorithme de scoring multi-critères basé sur :
 *  - Profil comportemental (vues, favoris, recherches)
 *  - Budget inféré depuis l'historique
 *  - Préférences géographiques (communes, quartiers)
 *  - Type et caractéristiques des logements aimés
 *  - Qualité de la résidence (rating, vérifié, top)
 *  - Fraîcheur du signal (decay temporel)
 *
 * Score final 0–100 par résidence, retour trié par score DESC.
 */
class ResidenceMatchingService
{
    // ── TTL cache profil utilisateur ──────────────────────────────────────
    private const PROFILE_TTL = 900;    // 15 min
    private const RESULTS_TTL = 600;    // 10 min

    // ── Fenêtre d'analyse comportementale ────────────────────────────────
    private const VIEW_WINDOW_DAYS      = 60;
    private const FAVORITE_WINDOW_DAYS  = 180;
    private const SEARCH_WINDOW_DAYS    = 30;

    // ── Poids des composantes du score (total = 100) ──────────────────────
    private const WEIGHTS = [
        'commune'        => 30,   // Correspondance géographique
        'budget'         => 25,   // Adéquation budget
        'type'           => 15,   // Type de logement préféré
        'bedrooms'       => 10,   // Nombre de chambres habituel
        'quality'        => 10,   // Rating, vérifié, top
        'amenities'      => 10,   // Équipements récurrents dans les favoris
    ];

    // ── Boost favoris (signal fort) ───────────────────────────────────────
    private const FAVORITE_BOOST   = 1.25;  // +25 % sur le score si signal favori
    private const VIEW_MULTI_BOOST = 1.10;  // +10 % si vu plusieurs fois

    // ─────────────────────────────────────────────────────────────────────
    // API publique
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Recommandations personnalisées pour un utilisateur.
     *
     * @return Collection<Residence>   Triées par score DESC, avec ->match_score et ->match_reasons
     */
    public function recommend(User $user, int $limit = 12, bool $fresh = false): Collection
    {
        $cacheKey = "matching_v2_{$user->id}_{$limit}";

        if ($fresh) {
            Cache::forget($cacheKey);
            Cache::forget("matching_profile_{$user->id}");
        }

        return Cache::remember($cacheKey, self::RESULTS_TTL, function () use ($user, $limit) {
            $profile = $this->buildUserProfile($user);

            return $this->scoreAndRank($user, $profile, $limit);
        });
    }

    /**
     * Profil inféré de l'utilisateur (export pour debug / API).
     */
    public function getUserProfile(User $user): array
    {
        return $this->buildUserProfile($user);
    }

    /**
     * Invalider le cache (à appeler après une action utilisateur significative).
     */
    public function invalidate(User $user): void
    {
        Cache::forget("matching_v2_{$user->id}_12");
        Cache::forget("matching_v2_{$user->id}_6");
        Cache::forget("matching_profile_{$user->id}");
    }

    // ─────────────────────────────────────────────────────────────────────
    // Construction du profil utilisateur
    // ─────────────────────────────────────────────────────────────────────

    private function buildUserProfile(User $user): array
    {
        return Cache::remember("matching_profile_{$user->id}", self::PROFILE_TTL, function () use ($user) {
            $favorites    = $this->getFavoriteResidences($user);
            $views        = $this->getViewedResidences($user);
            $searches     = $this->getSearchHistory($user);
            $savedSearches = $this->getSavedSearches($user);

            return [
                'communes'        => $this->inferCommunes($favorites, $views, $searches, $savedSearches),
                'budget'          => $this->inferBudget($favorites, $views, $searches, $savedSearches),
                'types'           => $this->inferTypes($favorites, $views),
                'bedrooms'        => $this->inferBedrooms($favorites, $views, $searches),
                'amenity_ids'     => $this->inferAmenities($favorites),
                'has_signal'      => $favorites->count() + $views->count() + $searches->count() > 0,
            ];
        });
    }

    // ─── Signal : favoris ────────────────────────────────────────────────

    private function getFavoriteResidences(User $user): Collection
    {
        return Residence::query()
            ->join('favorites', 'residences.id', '=', 'favorites.residence_id')
            ->where('favorites.user_id', $user->id)
            ->where('favorites.created_at', '>=', now()->subDays(self::FAVORITE_WINDOW_DAYS))
            ->select('residences.*')
            ->with('amenities')
            ->get();
    }

    // ─── Signal : historique de vues ─────────────────────────────────────

    private function getViewedResidences(User $user): Collection
    {
        return Residence::query()
            ->join('view_history', 'residences.id', '=', 'view_history.residence_id')
            ->where('view_history.user_id', $user->id)
            ->where('view_history.last_viewed_at', '>=', now()->subDays(self::VIEW_WINDOW_DAYS))
            ->where('view_history.view_count', '>=', 2)   // vues sérieuses (≥2x)
            ->select('residences.*', 'view_history.view_count')
            ->orderByDesc('view_history.view_count')
            ->take(20)
            ->get();
    }

    // ─── Signal : historique de recherches ───────────────────────────────

    private function getSearchHistory(User $user): \Illuminate\Database\Eloquent\Collection|\Illuminate\Support\Collection
    {
        return $user->searchHistories()
            ->where('created_at', '>=', now()->subDays(self::SEARCH_WINDOW_DAYS))
            ->latest()
            ->take(15)
            ->get();
    }

    private function getSavedSearches(User $user): \Illuminate\Database\Eloquent\Collection|\Illuminate\Support\Collection
    {
        return $user->savedSearches()->latest()->take(5)->get();
    }

    // ─────────────────────────────────────────────────────────────────────
    // Inférence des préférences
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Communes pondérées :
     *  - Favori = poids 3
     *  - Vue sérieuse = poids 2
     *  - Recherche = poids 1
     *  - Recherche sauvegardée = poids 2
     *
     * @return array<string, float>  commune → score normalisé 0-1
     */
    private function inferCommunes(Collection $favorites, Collection $views, $searches, $savedSearches): array
    {
        $scores = [];

        foreach ($favorites as $r) {
            $commune = $r->commune ?? $r->city ?? null;
            if ($commune) {
                $scores[$commune] = ($scores[$commune] ?? 0) + 3;
            }
        }

        foreach ($views as $r) {
            $commune = $r->commune ?? $r->city ?? null;
            if ($commune) {
                $scores[$commune] = ($scores[$commune] ?? 0) + 2;
            }
        }

        foreach ($searches as $s) {
            if ($s->commune) {
                $scores[$s->commune] = ($scores[$s->commune] ?? 0) + 1;
            }
        }

        foreach ($savedSearches as $s) {
            if ($s->location) {
                $scores[$s->location] = ($scores[$s->location] ?? 0) + 2;
            }
        }

        if (empty($scores)) {
            return [];
        }

        $max = max($scores);

        return array_map(fn ($v) => $max > 0 ? round($v / $max, 3) : 0, $scores);
    }

    /**
     * Budget inféré : médiane des prix des résidences consultées / mises en favoris.
     *
     * @return array{min: float, max: float, ideal: float}|null
     */
    private function inferBudget(Collection $favorites, Collection $views, $searches, $savedSearches): ?array
    {
        $prices = collect();

        // Prix des résidences mises en favoris (signal fort → poids double)
        foreach ($favorites as $r) {
            $p = (float) ($r->price_per_day ?? 0);
            if ($p > 0) {
                $prices->push($p);
                $prices->push($p); // double poids
            }
        }

        foreach ($views as $r) {
            $p = (float) ($r->price_per_day ?? 0);
            if ($p > 0) {
                $prices->push($p);
            }
        }

        // Budget explicite depuis les recherches sauvegardées
        foreach ($savedSearches as $s) {
            if ($s->min_price && $s->max_price) {
                $prices->push(($s->min_price + $s->max_price) / 2);
            }
        }

        foreach ($searches as $s) {
            if ($s->min_price && $s->max_price) {
                $prices->push(($s->min_price + $s->max_price) / 2);
            }
        }

        if ($prices->isEmpty()) {
            return null;
        }

        $sorted = $prices->sort()->values();
        $median = $sorted[(int) floor($sorted->count() / 2)];

        return [
            'min'   => round($median * 0.6, 0),   // -40%
            'max'   => round($median * 1.5, 0),   // +50%
            'ideal' => round($median, 0),
        ];
    }

    /**
     * Types de logement préférés pondérés.
     *
     * @return array<string, float>  type → score normalisé 0-1
     */
    private function inferTypes(Collection $favorites, Collection $views): array
    {
        $scores = [];

        foreach ($favorites as $r) {
            if ($r->type) {
                $scores[$r->type] = ($scores[$r->type] ?? 0) + 2;
            }
        }

        foreach ($views as $r) {
            if ($r->type) {
                $scores[$r->type] = ($scores[$r->type] ?? 0) + 1;
            }
        }

        if (empty($scores)) {
            return [];
        }

        $max = max($scores);

        return array_map(fn ($v) => $max > 0 ? round($v / $max, 3) : 0, $scores);
    }

    /**
     * Nombre de chambres préféré (médiane des favoris puis des vues).
     */
    private function inferBedrooms(Collection $favorites, Collection $views, $searches): ?int
    {
        $all = collect();

        foreach ($favorites as $r) {
            if ($r->bedrooms) {
                $all->push($r->bedrooms);
                $all->push($r->bedrooms); // poids double
            }
        }

        foreach ($views as $r) {
            if ($r->bedrooms) {
                $all->push($r->bedrooms);
            }
        }

        foreach ($searches as $s) {
            if ($s->bedrooms) {
                $all->push($s->bedrooms);
            }
        }

        if ($all->isEmpty()) {
            return null;
        }

        return (int) $all->sort()->values()[(int) floor($all->count() / 2)];
    }

    /**
     * Équipements récurrents dans les favoris (fréquence ≥ 30%).
     *
     * @return int[]
     */
    private function inferAmenities(Collection $favorites): array
    {
        if ($favorites->isEmpty()) {
            return [];
        }

        $total = $favorites->count();
        $counts = [];

        foreach ($favorites as $r) {
            foreach ($r->amenities as $amenity) {
                $counts[$amenity->id] = ($counts[$amenity->id] ?? 0) + 1;
            }
        }

        return array_keys(array_filter($counts, fn ($c) => ($c / $total) >= 0.3));
    }

    // ─────────────────────────────────────────────────────────────────────
    // Scoring & ranking
    // ─────────────────────────────────────────────────────────────────────

    private function scoreAndRank(User $user, array $profile, int $limit): Collection
    {
        // IDs déjà vus/favoris (à exclure des résultats)
        $excludeIds = DB::table('favorites')
            ->where('user_id', $user->id)
            ->pluck('residence_id')
            ->merge(
                DB::table('view_history')
                    ->where('user_id', $user->id)
                    ->pluck('residence_id'),
            )
            ->unique()
            ->toArray();

        // Pré-sélection : actives, disponibles, pas les siennes, pas déjà vues
        $query = Residence::query()
            ->where('status', 'active')
            ->where('is_available', true)
            ->where('owner_id', '!=', $user->id)
            ->whereNotIn('id', $excludeIds)
            ->with(['photos', 'amenities']);

        // Si profil géo connu : pré-filtrer par communes (performance)
        if (!empty($profile['communes'])) {
            $topCommunes = array_keys(
                array_filter($profile['communes'], fn ($s) => $s >= 0.3),
            );
            if (!empty($topCommunes)) {
                $query->whereIn('commune', $topCommunes);
            }
        }

        // Récupérer 3× le limit pour avoir assez de candidats à scorer
        $candidates = $query->take($limit * 3)->get();

        // Si pas assez de candidats (profil vide ou nouveau user), fallback populaires
        if ($candidates->count() < $limit) {
            $candidates = Residence::query()
                ->where('status', 'active')
                ->where('is_available', true)
                ->where('owner_id', '!=', $user->id)
                ->whereNotIn('id', $excludeIds)
                ->with(['photos', 'amenities'])
                ->orderByDesc('average_rating')
                ->orderByDesc('is_top_residence')
                ->take($limit * 3)
                ->get();
        }

        // Scorer chaque candidat
        $scored = $candidates->map(function (Residence $residence) use ($profile) {
            [$score, $reasons] = $this->scoreResidence($residence, $profile);
            $residence->match_score   = $score;
            $residence->match_reasons = $reasons;

            return $residence;
        });

        return $scored
            ->sortByDesc('match_score')
            ->take($limit)
            ->values();
    }

    /**
     * Score 0-100 d'une résidence face au profil utilisateur.
     *
     * @return array{0: int, 1: array}  [score, reasons]
     */
    private function scoreResidence(Residence $residence, array $profile): array
    {
        $score   = 0;
        $reasons = [];

        // ── 1. Commune ────────────────────────────────────────────────────
        $communeScore = 0;
        $commune = $residence->commune ?? $residence->city ?? '';
        if (!empty($profile['communes']) && isset($profile['communes'][$commune])) {
            $communeScore = (int) round($profile['communes'][$commune] * self::WEIGHTS['commune']);
            $reasons[] = "Zone {$commune} correspond à vos habitudes";
        } elseif (empty($profile['communes'])) {
            // Pas de préférence → score neutre (50%)
            $communeScore = (int) round(self::WEIGHTS['commune'] * 0.5);
        }
        $score += $communeScore;

        // ── 2. Budget ─────────────────────────────────────────────────────
        $budgetScore = 0;
        if ($profile['budget'] !== null) {
            $price = (float) ($residence->price_per_day ?? 0);
            $ideal = $profile['budget']['ideal'];
            $min   = $profile['budget']['min'];
            $max   = $profile['budget']['max'];

            if ($price >= $min && $price <= $max) {
                // Dans la fourchette → score proportionnel à la proximité de l'idéal
                $distance = abs($price - $ideal) / max($ideal, 1);
                $budgetScore = (int) round(self::WEIGHTS['budget'] * (1 - min($distance, 1)));
                if ($distance <= 0.15) {
                    $reasons[] = 'Prix idéal pour vous';
                } else {
                    $reasons[] = 'Dans votre budget';
                }
            } elseif ($price < $min) {
                // Moins cher que prévu → bon signe mais moins qualifié
                $budgetScore = (int) round(self::WEIGHTS['budget'] * 0.6);
                $reasons[] = 'Moins cher que vos habitudes';
            }
            // Hors budget max → 0 points
        } else {
            // Pas de profil budget → score neutre
            $budgetScore = (int) round(self::WEIGHTS['budget'] * 0.5);
        }
        $score += $budgetScore;

        // ── 3. Type de logement ───────────────────────────────────────────
        $typeScore = 0;
        if (!empty($profile['types']) && $residence->type) {
            $typeAffinity = $profile['types'][$residence->type] ?? 0;
            $typeScore    = (int) round($typeAffinity * self::WEIGHTS['type']);
            if ($typeAffinity >= 0.7) {
                $reasons[] = 'Type de logement que vous préférez';
            }
        } else {
            $typeScore = (int) round(self::WEIGHTS['type'] * 0.5);
        }
        $score += $typeScore;

        // ── 4. Nombre de chambres ─────────────────────────────────────────
        $bedroomsScore = 0;
        if ($profile['bedrooms'] !== null && $residence->bedrooms) {
            $diff = abs($residence->bedrooms - $profile['bedrooms']);
            if ($diff === 0) {
                $bedroomsScore = self::WEIGHTS['bedrooms'];
                $reasons[] = 'Nombre de chambres idéal';
            } elseif ($diff === 1) {
                $bedroomsScore = (int) round(self::WEIGHTS['bedrooms'] * 0.6);
            }
        } else {
            $bedroomsScore = (int) round(self::WEIGHTS['bedrooms'] * 0.5);
        }
        $score += $bedroomsScore;

        // ── 5. Qualité de l'annonce ───────────────────────────────────────
        $qualityScore = 0;
        $rating = (float) ($residence->average_rating ?? 0);
        if ($rating >= 4.5) {
            $qualityScore = self::WEIGHTS['quality'];
            $reasons[] = 'Très bien noté ('.number_format($rating, 1).'★)';
        } elseif ($rating >= 4.0) {
            $qualityScore = (int) round(self::WEIGHTS['quality'] * 0.7);
            $reasons[] = 'Bien noté ('.number_format($rating, 1).'★)';
        } elseif ($rating >= 3.5) {
            $qualityScore = (int) round(self::WEIGHTS['quality'] * 0.5);
        }

        if ($residence->is_verified) {
            $qualityScore = min(self::WEIGHTS['quality'], $qualityScore + 2);
            $reasons[] = 'Résidence vérifiée';
        }

        if ($residence->is_top_residence) {
            $qualityScore = min(self::WEIGHTS['quality'], $qualityScore + 2);
        }
        $score += $qualityScore;

        // ── 6. Équipements ────────────────────────────────────────────────
        $amenitiesScore = 0;
        if (!empty($profile['amenity_ids']) && $residence->amenities->isNotEmpty()) {
            $residenceAmenityIds = $residence->amenities->pluck('id')->toArray();
            $matches = count(array_intersect($profile['amenity_ids'], $residenceAmenityIds));
            $ratio   = $matches / count($profile['amenity_ids']);
            $amenitiesScore = (int) round($ratio * self::WEIGHTS['amenities']);
            if ($ratio >= 0.5) {
                $reasons[] = 'Équipements adaptés à vos préférences';
            }
        } else {
            $amenitiesScore = (int) round(self::WEIGHTS['amenities'] * 0.5);
        }
        $score += $amenitiesScore;

        // ── Boost signal fort ─────────────────────────────────────────────
        // Appliquer un boost si la résidence est dans une commune très aimée
        if (!empty($profile['communes'][$commune]) && $profile['communes'][$commune] >= 0.8) {
            $score = (int) round($score * self::FAVORITE_BOOST);
        }

        $score = min(100, max(0, $score));

        return [$score, array_unique($reasons)];
    }
}
