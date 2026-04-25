<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GeoSearchRequest;
use App\Http\Resources\GeoSearchResource;
use App\Models\Country;
use App\Models\Residence;
use App\Services\GeolocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

/**
 * API Controller pour la recherche géolocalisée optimisée
 *
 * Core feature de REZI : recherche de résidences ≤ 500m
 * avec cache géohash et tri par distance
 */
class GeoSearchController extends Controller
{
    public function __construct(
        private GeolocationService $geoService,
    ) {
    }

    /**
     * Recherche géolocalisée principale avec filtres
     *
     * POST /api/v1/geo/search
     *
     * Corps de requête :
     * - latitude: float (requis) - Latitude du point central
     * - longitude: float (requis) - Longitude du point central
     * - radius: int (100|200|300|400|500) - Rayon en mètres, défaut 300
     * - min_price: int - Prix minimum
     * - max_price: int - Prix maximum
     * - bedrooms: int - Nombre de chambres
     * - commune: string - Filtrer par commune
     * - amenities: array - IDs des équipements requis
     * - sort: string (distance|price_asc|price_desc|newest) - Tri
     * - per_page: int - Résultats par page (5-50)
     */
    public function search(GeoSearchRequest $request): JsonResponse
    {
        $validated = $request->validatedWithDefaults();

        try {
            $result = $this->geoService->search(
                latitude: $validated['latitude'],
                longitude: $validated['longitude'],
                radius: $validated['radius'],
                filters: $this->extractFilters($validated),
                sort: $validated['sort'],
                perPage: $validated['per_page'],
            );

            // Ajouter les statistiques de zone
            $zoneStats = $this->geoService->getZoneStatistics(
                $validated['latitude'],
                $validated['longitude'],
                $validated['radius'],
            );

            return response()->json([
                'success' => true,
                'message' => $this->buildSearchMessage($result['total'], $validated['radius']),
                'data' => GeoSearchResource::collection($result['residences']),
                'pagination' => [
                    'total' => $result['total'],
                    'per_page' => $result['residences']->perPage(),
                    'current_page' => $result['residences']->currentPage(),
                    'last_page' => $result['residences']->lastPage(),
                    'from' => $result['residences']->firstItem(),
                    'to' => $result['residences']->lastItem(),
                ],
                'search' => [
                    'center' => [
                        'latitude' => $validated['latitude'],
                        'longitude' => $validated['longitude'],
                    ],
                    'radius' => $validated['radius'],
                    'radius_label' => $this->formatRadiusLabel($validated['radius']),
                    'filters_applied' => $this->getAppliedFiltersCount($validated),
                    'cached' => $result['cached'],
                ],
                'zone_stats' => $zoneStats,
                'links' => [
                    'first' => $result['residences']->url(1),
                    'last' => $result['residences']->url($result['residences']->lastPage()),
                    'prev' => $result['residences']->previousPageUrl(),
                    'next' => $result['residences']->nextPageUrl(),
                ],
            ]);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la recherche.',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue.',
            ], 500);
        }
    }

    /**
     * Recherche rapide à proximité (sans filtres complexes)
     *
     * GET /api/v1/geo/nearby?latitude=X&longitude=Y&radius=300&limit=10
     */
    public function nearby(Request $request): JsonResponse
    {
        $bounds = Country::globalBounds();

        $validator = Validator::make($request->all(), [
            'latitude' => ['required', 'numeric', 'between:'.$bounds['min_lat'].','.$bounds['max_lat']],
            'longitude' => ['required', 'numeric', 'between:'.$bounds['min_lng'].','.$bounds['max_lng']],
            'radius' => ['sometimes', 'integer', 'min:100', 'max:50000'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ], [
            'latitude.between' => 'La latitude doit être dans la zone couverte (CI / BF).',
            'longitude.between' => 'La longitude doit être dans la zone couverte (CI / BF).',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Paramètres invalides.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $radius = (int) ($validated['radius'] ?? 5000);
        $limit = (int) ($validated['limit'] ?? 15);

        try {
            $residences = $this->geoService->findNearby(
                latitude: (float) $validated['latitude'],
                longitude: (float) $validated['longitude'],
                radius: $radius,
                limit: $limit,
            );

            return response()->json([
                'success' => true,
                'message' => sprintf('%d résidence(s) trouvée(s) à proximité', $residences->count()),
                'data' => GeoSearchResource::collection($residences),
                'meta' => [
                    'center' => [
                        'latitude' => (float) $validated['latitude'],
                        'longitude' => (float) $validated['longitude'],
                    ],
                    'radius' => $radius,
                    'count' => $residences->count(),
                ],
            ]);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la recherche.',
            ], 500);
        }
    }

    /**
     * Autocomplétion intelligente : zones, quartiers, résidences, POIs
     *
     * GET /api/v1/geo/autocomplete?q=coco
     */
    public function autocomplete(Request $request): JsonResponse
    {
        $query = strtolower(trim($request->get('q', '')));

        if (strlen($query) < 2) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        // Cache 5min — query identiques très fréquents
        $cacheKey = 'autocomplete:'.md5($query);
        $results = Cache::remember($cacheKey, 300, function () use ($query) {
            $safeQuery = str_replace(['%', '_'], ['\%', '\_'], $query);

            // 1. Zones connues (communes principales)
            $zones = collect(GeolocationService::getZones())
                ->filter(fn ($zone, $key) => str_contains($key, $query) || str_contains(strtolower($zone['label']), $query))
                ->map(fn ($zone, $key) => [
                    'id' => 'zone:'.$key,
                    'label' => $zone['label'],
                    'subtitle' => 'Commune',
                    'latitude' => $zone['lat'],
                    'longitude' => $zone['lng'],
                    'type' => 'commune',
                    'icon' => '🏙️',
                    'priority' => 10,
                ])
                ->values()
                ->take(4);

            // 2. Quartiers depuis la base
            $quartiers = Residence::approved()
                ->available()
                ->where('quartier', 'like', "%{$safeQuery}%")
                ->select('quartier', 'commune', 'city', 'latitude', 'longitude')
                ->selectRaw('COUNT(*) as nb')
                ->groupBy('quartier', 'commune', 'city', 'latitude', 'longitude')
                ->orderByDesc('nb')
                ->limit(4)
                ->get()
                ->map(fn ($r) => [
                    'id' => 'quartier:'.strtolower($r->quartier),
                    'label' => $r->quartier,
                    'subtitle' => 'Quartier · '.$r->commune.($r->nb > 1 ? ' · '.$r->nb.' biens' : ''),
                    'latitude' => (float) $r->latitude,
                    'longitude' => (float) $r->longitude,
                    'type' => 'quartier',
                    'icon' => '📍',
                    'priority' => 8,
                ]);

            // 3. Résidences par nom (POI direct — accès résidence)
            $residences = Residence::approved()
                ->available()
                ->where('name', 'like', "%{$safeQuery}%")
                ->select('id', 'name', 'commune', 'quartier', 'latitude', 'longitude', 'reviews_count', 'average_rating')
                ->orderByDesc('reviews_count')
                ->limit(4)
                ->get()
                ->map(fn ($r) => [
                    'id' => 'residence:'.$r->id,
                    'label' => $r->name,
                    'subtitle' => 'Résidence · '.($r->quartier ?: $r->commune).($r->average_rating ? ' · ⭐ '.number_format($r->average_rating, 1) : ''),
                    'latitude' => (float) $r->latitude,
                    'longitude' => (float) $r->longitude,
                    'type' => 'residence',
                    'residence_id' => $r->id,
                    'icon' => '🏠',
                    'priority' => 9,
                ]);

            return $zones->concat($quartiers)->concat($residences)
                ->sortByDesc('priority')
                ->take(10)
                ->values()
                ->all();
        });

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }

    /**
     * Zones populaires / tendances
     *
     * GET /api/v1/geo/trending
     */
    public function trending(): JsonResponse
    {
        try {
            $trending = $this->geoService->getTrendingAreas();

            return response()->json([
                'success' => true,
                'data' => $trending,
                'meta' => [
                    'cache_ttl' => 3600,
                    'updated_at' => now()->toIso8601String(),
                ],
            ]);
        } catch (\Exception $e) {
            report($e);

            // Retourner les zones par défaut en cas d'erreur
            return response()->json([
                'success' => true,
                'data' => collect(GeolocationService::getZones())->take(6)->map(fn ($zone, $key) => [
                    'zone' => $key,
                    'label' => $zone['label'],
                    'latitude' => $zone['lat'],
                    'longitude' => $zone['lng'],
                    'count' => 0,
                ])->values(),
            ]);
        }
    }

    /**
     * Statistiques d'une zone
     *
     * GET /api/v1/geo/zones/{zone}/stats
     */
    public function zoneStats(string $zone): JsonResponse
    {
        $zone = strtolower($zone);

        $allZones = GeolocationService::getZones();

        if (!isset($allZones[$zone])) {
            return response()->json([
                'success' => false,
                'message' => 'Zone non reconnue.',
                'valid_zones' => array_keys($allZones),
            ], 404);
        }

        $zoneData = $allZones[$zone];

        // Cache les stats pendant 1 heure
        $cacheKey = "zone_stats_{$zone}";
        $stats = Cache::remember($cacheKey, config('rezi.cache_ttl'), function () use ($zone, $zoneData) {
            $safeLabel = str_replace(['%', '_'], ['\%', '\_'], $zoneData['label']);
            $residences = Residence::approved()
                ->available()
                ->where('commune', 'like', "%{$safeLabel}%")
                ->get();

            $avgPrice = $residences->avg('price_per_month');

            return [
                'zone' => $zone,
                'label' => $zoneData['label'],
                'center' => [
                    'latitude' => $zoneData['lat'],
                    'longitude' => $zoneData['lng'],
                ],
                'statistics' => [
                    'total_residences' => $residences->count(),
                    'available_count' => $residences->where('is_available', true)->count(),
                    'price_min' => $residences->min('price_per_month'),
                    'price_max' => $residences->max('price_per_month'),
                    'price_avg' => $avgPrice !== null ? round($avgPrice) : 0,
                ],
                'price_ranges' => [
                    'budget' => $residences->whereBetween('price_per_month', [0, config('rezi.search.budget_threshold')])->count(),
                    'mid' => $residences->whereBetween('price_per_month', [config('rezi.search.budget_threshold') + 1, 300000])->count(),
                    'premium' => $residences->where('price_per_month', '>', 300000)->count(),
                ],
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Comptage par rayons (pour l'UI de sélection)
     *
     * GET /api/v1/geo/radius-counts?latitude=X&longitude=Y
     */
    public function radiusCounts(Request $request): JsonResponse
    {
        $bounds = Country::globalBounds();

        $validator = Validator::make($request->all(), [
            'latitude' => ['required', 'numeric', 'between:'.$bounds['min_lat'].','.$bounds['max_lat']],
            'longitude' => ['required', 'numeric', 'between:'.$bounds['min_lng'].','.$bounds['max_lng']],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $lat = (float) $validated['latitude'];
        $lng = (float) $validated['longitude'];

        // Utiliser le service caché au lieu de requêtes inline (fix perf)
        $cachedCounts = $this->geoService->getRadiusCounts($lat, $lng);

        $counts = [];
        foreach ($cachedCounts as $radius => $count) {
            $counts[] = [
                'radius' => $radius,
                'label' => $radius < 1000 ? $radius.' m' : ($radius / 1000).' km',
                'count' => $count,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $counts,
            'meta' => [
                'center' => ['latitude' => $lat, 'longitude' => $lng],
            ],
        ]);
    }

    /**
     * Extraire les filtres du tableau validé
     */
    private function extractFilters(array $validated): array
    {
        return array_filter([
            'country_code' => $validated['country_code'] ?? null,
            'city' => $validated['city'] ?? null,
            'min_price' => $validated['min_price'] ?? null,
            'max_price' => $validated['max_price'] ?? null,
            'commune' => $validated['commune'] ?? null,
            'amenities' => $validated['amenities'] ?? null,
        ], fn ($v) => $v !== null && $v !== false && $v !== []);
    }

    /**
     * Construire le message de résultat
     */
    private function buildSearchMessage(int $total, int $radius): string
    {
        if ($total === 0) {
            return "Aucune résidence trouvée dans un rayon de {$radius} m.";
        }

        $plural = $total > 1 ? 's' : '';

        return "{$total} résidence{$plural} trouvée{$plural} dans un rayon de {$radius} m.";
    }

    /**
     * Formater le label du rayon
     */
    private function formatRadiusLabel(int $radius): string
    {
        return $radius < 1000 ? "{$radius} m" : ($radius / 1000).' km';
    }

    /**
     * Compter les filtres appliqués
     */
    private function getAppliedFiltersCount(array $validated): int
    {
        $filterKeys = ['country_code', 'city', 'min_price', 'max_price', 'commune', 'amenities'];
        $count = 0;

        foreach ($filterKeys as $key) {
            if (isset($validated[$key]) && $validated[$key] !== null && $validated[$key] !== false && $validated[$key] !== []) {
                $count++;
            }
        }

        return $count;
    }
}
