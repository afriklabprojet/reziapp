<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PointOfInterest;
use App\Models\Residence;
use Illuminate\Support\Facades\Log;

/**
 * Service qui récupère automatiquement les points d'intérêt à proximité
 * d'une résidence via Google Places Nearby Search, puis les sauvegarde en BDD.
 */
class NearbyPlacesService
{
    public function __construct(
        private GoogleMapsService $maps,
    ) {
    }

    /**
     * Types Google Places à chercher pour chaque résidence.
     */
    private const SEARCH_TYPES = [
        'supermarket',
        'pharmacy',
        'hospital',
        'restaurant',
        'bank',
        'bus_station',
        'school',
        'mosque',
        'church',
        'park',
        'gym',
        'shopping_mall',
    ];

    /**
     * Récupérer et sauvegarder les POIs pour une résidence.
     * Si la résidence a déjà des POIs récents (< 30 jours), on skip.
     *
     * @param  Residence  $residence
     * @param  bool       $force  Forcer le rafraîchissement
     * @return int  Nombre de POIs créés
     */
    public function fetchAndSave(Residence $residence, bool $force = false): int
    {
        if (!$residence->latitude || !$residence->longitude) {
            Log::info("NearbyPlaces: Résidence #{$residence->id} sans coordonnées, skip");

            return 0;
        }

        // Vérifier si les POIs existent déjà (et sont récents)
        if (!$force) {
            $existingCount = $residence->pointsOfInterest()->count();
            $lastUpdate = $residence->pointsOfInterest()->max('updated_at');

            if ($existingCount > 0 && $lastUpdate && now()->diffInDays($lastUpdate) < 30) {
                Log::info("NearbyPlaces: Résidence #{$residence->id} a déjà {$existingCount} POIs récents, skip");

                return 0;
            }
        }

        try {
            $places = $this->maps->nearbySearch(
                $residence->latitude,
                $residence->longitude,
                1500, // Rayon 1.5 km
                self::SEARCH_TYPES,
            );

            if (empty($places)) {
                Log::info("NearbyPlaces: Aucun POI trouvé pour résidence #{$residence->id}");

                return 0;
            }

            // Supprimer les anciens POIs auto-générés (garder les manuels)
            if ($force) {
                $residence->pointsOfInterest()->delete();
            }

            $created = 0;

            foreach ($places as $place) {
                // Éviter les doublons par nom + type
                $exists = $residence->pointsOfInterest()
                    ->where('name', $place['name'])
                    ->where('type', $place['type'])
                    ->exists();

                if ($exists) {
                    continue;
                }

                PointOfInterest::create([
                    'residence_id'         => $residence->id,
                    'name'                 => $place['name'],
                    'type'                 => $place['type'],
                    'distance_meters'      => $place['distance_meters'],
                    'walking_time_minutes' => $place['walking_time_minutes'],
                    'latitude'             => $place['latitude'],
                    'longitude'            => $place['longitude'],
                ]);

                $created++;
            }

            Log::info("NearbyPlaces: {$created} POIs créés pour résidence #{$residence->id}");

            return $created;
        } catch (\Exception $e) {
            Log::error("NearbyPlaces: Erreur pour résidence #{$residence->id}", [
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * Rafraîchir les POIs pour toutes les résidences actives
     * sans POIs ou avec des POIs obsolètes.
     *
     * @param  int  $limit  Nombre max de résidences à traiter
     * @return int  Nombre total de POIs créés
     */
    public function refreshAll(int $limit = 50): int
    {
        $residences = Residence::where('status', 'active')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereDoesntHave('pointsOfInterest')
            ->take($limit)
            ->get();

        $totalCreated = 0;

        foreach ($residences as $residence) {
            $totalCreated += $this->fetchAndSave($residence);
        }

        Log::info("NearbyPlaces: Refresh terminé — {$totalCreated} POIs créés pour {$residences->count()} résidences");

        return $totalCreated;
    }
}
