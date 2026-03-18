<?php

declare(strict_types=1);

namespace App\Observers;

use App\Jobs\ComputeListingScore;
use App\Jobs\FetchNearbyPlaces;
use App\Models\Residence;
use Illuminate\Support\Facades\Cache;

/**
 * Observer pour invalider les caches homepage
 * quand une résidence est créée, modifiée ou supprimée.
 *
 * Les caches sont scopés par localisation (ex: featured_residences_ci_abidjan)
 * depuis la Phase 5 (détection Airbnb-style). L'invalidation cible
 * la localisation de la résidence concernée + le suffixe "all".
 */
class ResidenceObserver
{
    /**
     * Préfixes de cache utilisés par la page d'accueil (scopés par localisation).
     * Clé finale = "{prefix}_{country_code}_{city}" ex: featured_residences_ci_abidjan
     */
    private array $locationScopedPrefixes = [
        'featured_residences',
        'popular_zones',
        'home_stats',
        'filter_communes',
        'filter_cities',
    ];

    /**
     * Clés de cache globales (non scopées)
     */
    private array $globalCacheKeys = [
        'home_testimonials',
        'home_categories',
        'sitemap_xml',
        'available_locations',
    ];

    /**
     * Handle the Residence "created" event.
     */
    public function created(Residence $residence): void
    {
        $this->invalidateHomepageCache($residence);
        $this->invalidateSimilarCache($residence);

        // Calculer le score qualité en arrière-plan
        ComputeListingScore::dispatch($residence)->onQueue('default')->delay(now()->addSeconds(5));

        // Récupérer automatiquement les POIs à proximité
        if ($residence->latitude && $residence->longitude) {
            FetchNearbyPlaces::dispatch($residence)->onQueue('default')->delay(now()->addSeconds(30));
        }
    }

    /**
     * Handle the Residence "updating" event.
     * Empêche le retour au statut 'pending' pour les résidences déjà approuvées/actives.
     * Une fois qu'une annonce est validée par l'admin, les modifications du propriétaire
     * ne doivent PAS nécessiter une nouvelle approbation.
     */
    public function updating(Residence $residence): void
    {
        if ($residence->isDirty('status')) {
            $originalStatus = $residence->getOriginal('status');
            $newStatus = $residence->status;

            // Si la résidence était déjà active/approved et qu'on essaie de la remettre en pending,
            // on empêche ce changement (sauf si c'est l'admin via Filament)
            if (in_array($originalStatus, ['active', 'approved']) && $newStatus === 'pending') {
                // Vérifier si c'est un admin qui fait le changement via Filament
                $isAdminAction = auth()->check() && auth()->user()->role === 'admin';

                if (!$isAdminAction) {
                    // Restaurer le statut actif — pas de re-approbation nécessaire
                    $residence->status = $originalStatus;
                    \Illuminate\Support\Facades\Log::info('Prevented status regression to pending for approved residence', [
                        'residence_id' => $residence->id,
                        'original_status' => $originalStatus,
                    ]);
                }
            }
        }
    }

    /**
     * Handle the Residence "updated" event.
     * Invalidation ciblée : uniquement si des champs visibles sur la homepage changent.
     */
    public function updated(Residence $residence): void
    {
        // Champs qui impactent le score qualité de l'annonce
        $scoreFields = ['title', 'description', 'address', 'commune', 'latitude', 'longitude', 'surface', 'bedrooms', 'type', 'price_per_night', 'price_per_month'];
        if ($residence->isDirty($scoreFields)) {
            ComputeListingScore::dispatch($residence)->onQueue('default')->delay(now()->addSeconds(10));
        }

        // Si les coordonnées changent, rafraîchir les POIs
        if ($residence->isDirty(['latitude', 'longitude'])) {
            FetchNearbyPlaces::dispatch($residence, force: true)->onQueue('default')->delay(now()->addSeconds(30));
        }

        $relevantFields = [
            'status',
            'is_available',
            'price_per_month',
            'price_per_day',
            'commune',
            'city',
            'country_code',
            'quartier',
            'name',
            'category_id',
        ];

        if ($residence->isDirty($relevantFields)) {
            $this->invalidateHomepageCache($residence);
        }

        $this->invalidateSimilarCache($residence);
    }

    /**
     * Handle the Residence "deleted" event.
     */
    public function deleted(Residence $residence): void
    {
        $this->invalidateHomepageCache($residence);
        $this->invalidateSimilarCache($residence);
    }

    /**
     * Handle the Residence "restored" event.
     */
    public function restored(Residence $residence): void
    {
        $this->invalidateHomepageCache($residence);
    }

    /**
     * Invalider les caches homepage pour la localisation de la résidence.
     * Cible : clés globales + clés scopées pour le pays/ville de la résidence.
     */
    private function invalidateHomepageCache(Residence $residence): void
    {
        // 1. Clés globales (non scopées par localisation)
        foreach ($this->globalCacheKeys as $key) {
            Cache::forget($key);
        }

        // 2. Clés scopées — cibler la localisation de la résidence
        $locationKeys = $this->buildLocationKeys($residence);

        foreach ($this->locationScopedPrefixes as $prefix) {
            foreach ($locationKeys as $suffix) {
                Cache::forget("{$prefix}_{$suffix}");
            }
        }
    }

    /**
     * Construire les suffixes de localisation à invalider pour une résidence.
     * Inclut : la localisation actuelle, la variante "all", et les anciennes
     * valeurs si country_code/city ont changé.
     */
    private function buildLocationKeys(Residence $residence): array
    {
        $cc = strtolower($residence->country_code ?? 'ci');
        $city = strtolower($residence->city ?? 'abidjan');

        $keys = [
            "{$cc}_{$city}",  // Localisation exacte
            "{$cc}_all",      // Vue "toutes les villes" du pays
        ];

        // Si la ville a changé → invalider aussi l'ancienne ville
        if ($residence->isDirty('city') && $residence->getOriginal('city')) {
            $keys[] = "{$cc}_" . strtolower($residence->getOriginal('city'));
        }

        // Si le pays a changé → invalider aussi l'ancien pays
        if ($residence->isDirty('country_code') && $residence->getOriginal('country_code')) {
            $oldCc = strtolower($residence->getOriginal('country_code'));
            $keys[] = "{$oldCc}_{$city}";
            $keys[] = "{$oldCc}_all";
            if ($residence->getOriginal('city')) {
                $keys[] = "{$oldCc}_" . strtolower($residence->getOriginal('city'));
            }
        }

        return array_unique($keys);
    }

    /**
     * Invalider le cache des résidences similaires pour cette résidence.
     */
    private function invalidateSimilarCache(Residence $residence): void
    {
        Cache::forget("residence:{$residence->id}:similar");
    }
}
