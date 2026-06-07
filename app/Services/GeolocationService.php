<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\City;
use App\Models\Residence;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Service de géolocalisation pour Rezi App
 *
 * Gère les calculs de distance et recherches géographiques optimisées
 * avec support du cache par zone géographique (geohash)
 *
 * Zones couvertes : Côte d'Ivoire + Burkina Faso
 */
class GeolocationService
{
    private const EARTH_RADIUS_METERS = 6371000;
    private const CACHE_TTL = 1800; // 30 minutes

    /**
     * Obtenir les rayons autorisés depuis la config
     */
    public static function getAllowedRadii(): array
    {
        return config('rezi.search.allowed_radii', [2000, 5000, 10000, 25000, 50000]);
    }

    /**
     * Obtenir le rayon par défaut depuis la config
     */
    public static function getDefaultRadius(): int
    {
        return config('rezi.search.default_radius', 2000);
    }

    /**
     * Obtenir les zones depuis la config (legacy) + villes actives en BDD
     * Transforme le format config (latitude/longitude) en format interne (lat/lng/label)
     */
    public static function getZones(): array
    {
        return Cache::remember('geo:all_zones', 3600, function () {
            $result = [];

            // Legacy : zones du config/rezi.php
            $zones = config('rezi.zones', []);
            foreach ($zones as $key => $zone) {
                $result[$key] = [
                    'lat' => $zone['latitude'],
                    'lng' => $zone['longitude'],
                    'label' => $zone['name'],
                ];
            }

            // Ajouter les villes actives de la BDD (hors communes déjà dans config)
            $cities = City::active()->ordered()->with('country')->get();

            foreach ($cities as $city) {
                $key = Str::slug($city->name);
                if (! isset($result[$key])) {
                    $result[$key] = [
                        'lat' => $city->latitude,
                        'lng' => $city->longitude,
                        'label' => $city->name,
                        'country' => $city->country?->code,
                    ];
                }
            }

            return $result;
        });
    }

    /**
     * Alias de compatibilité (ancien nom, avant expansion multi-pays)
     * @deprecated Utiliser getZones()
     */
    public static function getAbidjanZones(): array
    {
        return self::getZones();
    }

    /**
     * Recherche principale avec filtres, tri et pagination
     *
     * @param float $latitude Latitude du centre de recherche
     * @param float $longitude Longitude du centre de recherche
        * @param int $radius Rayon en mètres, normalisé selon config('rezi.search.allowed_radii')
     * @param array $filters Filtres (min_price, max_price, bedrooms, commune, amenities, etc.)
     * @param string $sort Tri (distance, price_asc, price_desc, newest, area)
     * @param int $perPage Résultats par page
     * @return array{residences: LengthAwarePaginator, total: int, cached: bool}
     */
    public function search(
        float $latitude,
        float $longitude,
        ?int $radius = null,
        array $filters = [],
        string $sort = 'distance',
        int $perPage = 15,
    ): array {
        $radius = $radius ?? self::getDefaultRadius();
        $this->validateCoordinates($latitude, $longitude);
        $radius = $this->normalizeRadius($radius);

        $geohash = $this->getGeohash($latitude, $longitude);
        $cacheKey = $this->buildCacheKey($latitude, $longitude, $radius, $filters, $perPage, $sort);
        $page = request()->get('page', 1);
        $fullCacheKey = "{$cacheKey}:page:{$page}";

        $this->registerCacheKey($geohash, $fullCacheKey);

        $cached = Cache::has($fullCacheKey);

        $residences = Cache::remember($fullCacheKey, self::CACHE_TTL, function () use ($latitude, $longitude, $radius, $filters, $sort, $perPage) {
            // Only sort by distance in withinRadius if sort is 'distance'
            $sortByDistance = ($sort === 'distance');

            $query = Residence::approved()
                ->available()
                ->withinRadius($latitude, $longitude, $radius, $sortByDistance)
                ->with(['photos', 'amenities', 'owner:id,name']);

            // Appliquer les filtres
            $this->applyFilters($query, $filters);

            // Appliquer le tri (si pas déjà fait par withinRadius)
            if (!$sortByDistance) {
                $this->applySort($query, $sort);
            }

            return $query->paginate($perPage);
        });

        return [
            'residences' => $residences,
            'total' => $residences->total(),
            'cached' => $cached,
        ];
    }

    /**
     * Recherche les résidences à proximité avec pagination (legacy)
     */
    public function searchNearby(
        float $latitude,
        float $longitude,
        ?int $radius = null,
        array $filters = [],
        int $perPage = 15,
    ): LengthAwarePaginator {
        $result = $this->search($latitude, $longitude, $radius ?? self::getDefaultRadius(), $filters, 'distance', $perPage);

        return $result['residences'];
    }

    /**
     * Recherche les résidences sans pagination (pour API/carte)
     */
    public function findNearby(
        float $latitude,
        float $longitude,
        ?int $radius = null,
        int $limit = 50,
    ): Collection {
        $radius = $radius ?? self::getDefaultRadius();
        $this->validateCoordinates($latitude, $longitude);
        $radius = $this->normalizeRadius($radius);

        $geohash = $this->getGeohash($latitude, $longitude);
        $cacheKey = "geo:nearby:{$geohash}:r:{$radius}:l:{$limit}";

        $this->registerCacheKey($geohash, $cacheKey);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($latitude, $longitude, $radius, $limit) {
            return Residence::approved()
                ->available()
                ->withinRadius($latitude, $longitude, $radius)
                ->with(['photos', 'amenities', 'owner:id,name'])
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Recherche les N résidences les plus proches
     */
    public function findNearest(float $latitude, float $longitude, int $limit = 10): Collection
    {
        $this->validateCoordinates($latitude, $longitude);

        $geohash = $this->getGeohash($latitude, $longitude);
        $cacheKey = "geo:nearest:{$geohash}:limit:{$limit}";

        $this->registerCacheKey($geohash, $cacheKey);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($latitude, $longitude, $limit) {
            return Residence::approved()
                ->available()
                ->nearestTo($latitude, $longitude, $limit)
                ->with(['photos', 'amenities', 'owner:id,name'])
                ->get();
        });
    }

    /**
     * Retourne les zones populaires / tendances
     * Basé sur le nombre de résidences disponibles par zone
     */
    public function getTrendingAreas(): Collection
    {
        $cacheKey = 'geo:trending_areas';

        return Cache::remember($cacheKey, 3600, function () {
            return collect(self::getZones())->map(function ($zone, $key) {
                $count = Residence::approved()
                    ->available()
                    ->where('commune', 'like', "%{$zone['label']}%")
                    ->count();

                return [
                    'zone' => $key,
                    'label' => $zone['label'],
                    'latitude' => $zone['lat'],
                    'longitude' => $zone['lng'],
                    'count' => $count,
                ];
            })
            ->sortByDesc('count')
            ->take(6)
            ->values();
        });
    }

    /**
     * Retourne les statistiques d'une zone
     */
    public function getZoneStatistics(float $latitude, float $longitude, int $radius): array
    {
        $geohash = $this->getGeohash($latitude, $longitude);
        $cacheKey = "geo:zone_stats:{$geohash}:r:{$radius}";

        $this->registerCacheKey($geohash, $cacheKey);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($latitude, $longitude, $radius) {
            $residences = Residence::approved()
                ->available()
                ->withinRadius($latitude, $longitude, $radius)
                ->get();

            if ($residences->isEmpty()) {
                return [
                    'total' => 0,
                    'price_range' => null,
                    'avg_price' => null,
                    'available_count' => 0,
                ];
            }

            return [
                'total' => $residences->count(),
                'price_range' => [
                    'min' => $residences->min('price_per_month'),
                    'max' => $residences->max('price_per_month'),
                ],
                'avg_price' => round($residences->avg('price_per_month')),
                'available_count' => $residences->where('is_available', true)->count(),
            ];
        });
    }

    /**
     * Compte les résidences dans un rayon (pour affichage rapide)
     */
    public function countNearby(float $latitude, float $longitude, ?int $radius = null): int
    {
        $radius = $radius ?? self::getDefaultRadius();
        $this->validateCoordinates($latitude, $longitude);
        $radius = $this->normalizeRadius($radius);

        $geohash = $this->getGeohash($latitude, $longitude);
        $cacheKey = "geo:count:{$geohash}:r:{$radius}";

        $this->registerCacheKey($geohash, $cacheKey);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($latitude, $longitude, $radius) {
            return Residence::approved()
                ->available()
                ->withinRadius($latitude, $longitude, $radius)
                ->count();
        });
    }

    /**
     * Retourne les comptages pour tous les rayons
     */
    public function getRadiusCounts(float $latitude, float $longitude): array
    {
        $this->validateCoordinates($latitude, $longitude);

        $geohash = $this->getGeohash($latitude, $longitude);
        $cacheKey = "geo:radius_counts:{$geohash}";

        $this->registerCacheKey($geohash, $cacheKey);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($latitude, $longitude) {
            $counts = [];

            foreach (self::getAllowedRadii() as $radius) {
                $counts[$radius] = Residence::approved()
                    ->available()
                    ->withinRadius($latitude, $longitude, $radius)
                    ->count();
            }

            return $counts;
        });
    }

    /**
     * Calcule la distance entre deux points avec la formule de Haversine
     */
    public function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $latFrom = deg2rad($lat1);
        $lngFrom = deg2rad($lng1);
        $latTo = deg2rad($lat2);
        $lngTo = deg2rad($lng2);

        $latDelta = $latTo - $latFrom;
        $lngDelta = $lngTo - $lngFrom;

        $a = sin($latDelta / 2) ** 2 +
             cos($latFrom) * cos($latTo) * sin($lngDelta / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return self::EARTH_RADIUS_METERS * $c;
    }

    /**
     * Formate une distance pour l'affichage
     */
    public function formatDistance(float $meters): string
    {
        if ($meters < 1000) {
            return round($meters).' m';
        }

        return number_format($meters / 1000, 1, ',', ' ').' km';
    }

    /**
     * Invalide le cache pour une zone géographique.
     *
     * Utilise un registry de clés (geo:keys:{geohash}) pour éviter les wildcards
     * qui ne fonctionnent pas avec le driver database ou file.
     * Compatible avec tous les drivers (array, database, redis).
     */
    public function invalidateCache(float $latitude, float $longitude): void
    {
        $geohash = $this->getGeohash($latitude, $longitude);
        $registryKey = "geo:keys:{$geohash}";

        $keys = Cache::get($registryKey, []);

        foreach ($keys as $key) {
            Cache::forget($key);
        }

        Cache::forget($registryKey);
        Cache::forget('geo:trending_areas');

        Log::info('Geolocation cache invalidated', [
            'geohash' => $geohash,
            'keys_cleared' => count($keys),
        ]);
    }

    /**
     * Enregistre une clé de cache dans le registry du geohash.
     *
     * Permet l'invalidation groupée sans wildcard, compatible tous drivers.
     * TTL du registry (7 jours) intentionnellement supérieur aux TTL de cache
     * individuels (30 min) pour garantir que la liste survit aux entrées.
     */
    private function registerCacheKey(string $geohash, string $key): void
    {
        $registryKey = "geo:keys:{$geohash}";
        $registryTtl = 3600 * 24 * 7; // 7 days

        $keys = Cache::get($registryKey, []);

        if (! in_array($key, $keys, true)) {
            $keys[] = $key;
            Cache::put($registryKey, $keys, $registryTtl);
        }
    }

    /**
     * Applique les filtres à la requête
     */
    private function applyFilters($query, array $filters): void
    {
        if (!empty($filters['country_code'])) {
            $query->where('country_code', $filters['country_code']);
        }

        if (!empty($filters['city'])) {
            $query->where('city', $filters['city']);
        }

        if (!empty($filters['min_price'])) {
            $query->where('price_per_month', '>=', $filters['min_price']);
        }

        if (!empty($filters['max_price'])) {
            $query->where('price_per_month', '<=', $filters['max_price']);
        }

        if (!empty($filters['commune'])) {
            $query->where('commune', 'like', "%{$filters['commune']}%");
        }

        if (!empty($filters['amenities']) && is_array($filters['amenities'])) {
            $query->whereHas('amenities', function ($q) use ($filters) {
                $q->whereIn('amenities.id', $filters['amenities']);
            }, '>=', count($filters['amenities']));
        }
    }

    /**
     * Applique le tri à la requête (pour les tris non-distance)
     */
    private function applySort($query, string $sort): void
    {
        switch ($sort) {
            case 'price_asc':
                $query->reorder()->orderBy('price_per_month', 'asc');
                break;
            case 'price_desc':
                $query->reorder()->orderBy('price_per_month', 'desc');
                break;
            case 'newest':
                $query->reorder()->orderBy('created_at', 'desc');
                break;
            case 'distance':
            default:
                // Le tri par distance est déjà appliqué via withinRadius
                break;
        }
    }

    /**
     * Génère un geohash simplifié pour le cache (~1.1 km precision à l'équateur).
     *
     * round(..., 2) donne une cellule d'environ 1.1 km × 1.1 km, ce qui est
     * suffisant pour la recherche de résidences et maximise le cache hit rate
     * par rapport à la précision ~111 m de round(..., 3).
     */
    private function getGeohash(float $lat, float $lng): string
    {
        return round($lat, 2).'_'.round($lng, 2);
    }

    /**
     * Construit la clé de cache
     */
    private function buildCacheKey(float $lat, float $lng, int $radius, array $filters, int $limit, string $sort = 'distance'): string
    {
        $geohash = $this->getGeohash($lat, $lng);
        $filterHash = md5(serialize($filters));

        return "geo:search:{$geohash}:r:{$radius}:s:{$sort}:f:{$filterHash}:l:{$limit}";
    }

    /**
     * Normalise le rayon vers une valeur autorisée
     */
    private function normalizeRadius(int $radius): int
    {
        // Trouve le rayon autorisé le plus proche
        $allowedRadii = self::getAllowedRadii();
        $closest = $allowedRadii[0];
        foreach ($allowedRadii as $allowed) {
            if (abs($radius - $allowed) < abs($radius - $closest)) {
                $closest = $allowed;
            }
        }

        return $closest;
    }

    /**
     * Valide les coordonnées
     */
    private function validateCoordinates(float $latitude, float $longitude): void
    {
        if ($latitude < -90 || $latitude > 90) {
            throw new \InvalidArgumentException('Latitude must be between -90 and 90');
        }

        if ($longitude < -180 || $longitude > 180) {
            throw new \InvalidArgumentException('Longitude must be between -180 and 180');
        }
    }
}
