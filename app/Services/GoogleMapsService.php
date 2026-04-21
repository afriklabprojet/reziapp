<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleMapsService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://maps.googleapis.com/maps/api';

    public function __construct()
    {
        $this->apiKey = config('services.google_maps.key', '');
    }

    // ─────────────────────────────────────────────
    // DIRECTIONS API
    // ─────────────────────────────────────────────

    /**
     * Obtenir les directions entre deux points.
     *
     * @param  string|array  $origin       "lat,lng" ou ['lat' => x, 'lng' => y]
     * @param  string|array  $destination  "lat,lng" ou ['lat' => x, 'lng' => y]
     * @param  string        $mode         driving|walking|bicycling|transit
     * @param  string        $language     Langue des instructions
     * @return array|null
     */
    public function getDirections(
        string|array $origin,
        string|array $destination,
        string $mode = 'driving',
        string $language = 'fr',
    ): ?array {
        $origin = $this->formatLatLng($origin);
        $destination = $this->formatLatLng($destination);

        $cacheKey = "directions:{$origin}:{$destination}:{$mode}";

        return Cache::remember($cacheKey, 3600, function () use ($origin, $destination, $mode, $language) {
            try {
                $response = Http::get("{$this->baseUrl}/directions/json", [
                    'origin' => $origin,
                    'destination' => $destination,
                    'mode' => $mode,
                    'language' => $language,
                    'key' => $this->apiKey,
                ]);

                $data = $response->json();

                if ($data['status'] !== 'OK') {
                    Log::warning('Google Directions API error', [
                        'status' => $data['status'],
                        'origin' => $origin,
                        'destination' => $destination,
                    ]);

                    return null;
                }

                $route = $data['routes'][0] ?? null;
                if (! $route) {
                    return null;
                }

                $leg = $route['legs'][0];

                return [
                    'distance' => $leg['distance'], // ['text' => '5.2 km', 'value' => 5200]
                    'duration' => $leg['duration'], // ['text' => '12 min', 'value' => 720]
                    'start_address' => $leg['start_address'],
                    'end_address' => $leg['end_address'],
                    'steps' => collect($leg['steps'])->map(fn ($step) => [
                        'instruction' => strip_tags($step['html_instructions']),
                        'distance' => $step['distance']['text'],
                        'duration' => $step['duration']['text'],
                        'travel_mode' => $step['travel_mode'],
                    ])->toArray(),
                    'polyline' => $route['overview_polyline']['points'] ?? null,
                    'bounds' => $route['bounds'] ?? null,
                ];
            } catch (\Exception $e) {
                Log::error('Google Directions API exception', ['error' => $e->getMessage()]);

                return null;
            }
        });
    }

    // ─────────────────────────────────────────────
    // DISTANCE MATRIX API
    // ─────────────────────────────────────────────

    /**
     * Calculer les distances et durées entre plusieurs origines et destinations.
     *
     * @param  array   $origins       [['lat' => x, 'lng' => y], ...]
     * @param  array   $destinations  [['lat' => x, 'lng' => y], ...]
     * @param  string  $mode          driving|walking|bicycling|transit
     * @return array|null
     */
    public function getDistanceMatrix(
        array $origins,
        array $destinations,
        string $mode = 'driving',
    ): ?array {
        $originsStr = collect($origins)->map(fn ($o) => $this->formatLatLng($o))->implode('|');
        $destsStr = collect($destinations)->map(fn ($d) => $this->formatLatLng($d))->implode('|');

        $cacheKey = 'distance_matrix:'.md5("{$originsStr}:{$destsStr}:{$mode}");

        return Cache::remember($cacheKey, 3600, function () use ($originsStr, $destsStr, $mode) {
            try {
                $response = Http::get("{$this->baseUrl}/distancematrix/json", [
                    'origins' => $originsStr,
                    'destinations' => $destsStr,
                    'mode' => $mode,
                    'language' => 'fr',
                    'key' => $this->apiKey,
                ]);

                $data = $response->json();

                if ($data['status'] !== 'OK') {
                    Log::warning('Google Distance Matrix API error', ['status' => $data['status']]);

                    return null;
                }

                $results = [];
                foreach ($data['rows'] as $rowIndex => $row) {
                    foreach ($row['elements'] as $colIndex => $element) {
                        if ($element['status'] === 'OK') {
                            $results[] = [
                                'origin_index' => $rowIndex,
                                'destination_index' => $colIndex,
                                'origin_address' => $data['origin_addresses'][$rowIndex] ?? null,
                                'destination_address' => $data['destination_addresses'][$colIndex] ?? null,
                                'distance' => $element['distance'], // ['text' => '5 km', 'value' => 5000]
                                'duration' => $element['duration'], // ['text' => '10 min', 'value' => 600]
                                'duration_in_traffic' => $element['duration_in_traffic'] ?? null,
                            ];
                        }
                    }
                }

                return $results;
            } catch (\Exception $e) {
                Log::error('Google Distance Matrix API exception', ['error' => $e->getMessage()]);

                return null;
            }
        });
    }

    /**
     * Raccourci : distance entre un point et une résidence.
     */
    public function getDistanceToResidence(
        float $userLat,
        float $userLng,
        float $residenceLat,
        float $residenceLng,
        string $mode = 'driving',
    ): ?array {
        $results = $this->getDistanceMatrix(
            [['lat' => $userLat, 'lng' => $userLng]],
            [['lat' => $residenceLat, 'lng' => $residenceLng]],
            $mode,
        );

        return $results[0] ?? null;
    }

    // ─────────────────────────────────────────────
    // STATIC MAPS API
    // ─────────────────────────────────────────────

    /**
     * Générer une URL de carte statique Google Maps.
     *
     * @param  float   $lat     Latitude du centre
     * @param  float   $lng     Longitude du centre
     * @param  int     $zoom    Niveau de zoom (1-20)
     * @param  string  $size    Taille "WxH" (ex: "600x300")
     * @param  array   $markers Marqueurs additionnels
     * @param  string  $maptype roadmap|satellite|terrain|hybrid
     * @return string  URL de l'image statique
     */
    public function getStaticMapUrl(
        float $lat,
        float $lng,
        int $zoom = 15,
        string $size = '600x300',
        array $markers = [],
        string $maptype = 'roadmap',
    ): string {
        $params = [
            'center' => "{$lat},{$lng}",
            'zoom' => $zoom,
            'size' => $size,
            'maptype' => $maptype,
            'language' => 'fr',
            'key' => $this->apiKey,
        ];

        // Marqueur principal (résidence)
        $markerStr = "color:red|label:R|{$lat},{$lng}";

        // Marqueurs additionnels
        foreach ($markers as $marker) {
            $color = $marker['color'] ?? 'blue';
            $label = $marker['label'] ?? '';
            $markerStr .= "&markers=color:{$color}|label:{$label}|{$marker['lat']},{$marker['lng']}";
        }

        $params['markers'] = "color:red|label:R|{$lat},{$lng}";

        $url = "{$this->baseUrl}/staticmap?".http_build_query($params);

        // Ajouter les marqueurs additionnels
        foreach ($markers as $marker) {
            $color = $marker['color'] ?? 'blue';
            $label = $marker['label'] ?? '';
            $url .= "&markers=color:{$color}|label:{$label}|{$marker['lat']},{$marker['lng']}";
        }

        return $url;
    }

    /**
     * Générer une URL de carte statique pour un email (résidence).
     */
    public function getResidenceStaticMapUrl(float $lat, float $lng): string
    {
        return $this->getStaticMapUrl($lat, $lng, 15, '600x250');
    }

    // ─────────────────────────────────────────────
    // PLACES API (server-side)
    // ─────────────────────────────────────────────

    /**
     * Autocomplete côté serveur (pour API endpoints).
     *
     * @param  string  $input     Texte de recherche
     * @param  array   $countries Codes pays (CI, BF)
     * @param  string  $types     Types de places
     * @return array
     */
    public function autocomplete(
        string $input,
        array $countries = ['ci', 'bf'],
        string $types = 'geocode',
    ): array {
        $cacheKey = 'places_autocomplete:'.md5("{$input}:{$types}:".implode(',', $countries));

        return Cache::remember($cacheKey, 1800, function () use ($input, $countries, $types) {
            try {
                $response = Http::get("{$this->baseUrl}/place/autocomplete/json", [
                    'input' => $input,
                    'types' => $types,
                    'components' => collect($countries)->map(fn ($c) => "country:{$c}")->implode('|'),
                    'language' => 'fr',
                    'key' => $this->apiKey,
                ]);

                $data = $response->json();

                if ($data['status'] !== 'OK' && $data['status'] !== 'ZERO_RESULTS') {
                    Log::warning('Google Places Autocomplete error', ['status' => $data['status']]);

                    return [];
                }

                return collect($data['predictions'] ?? [])
                    ->map(fn ($prediction) => [
                        'place_id' => $prediction['place_id'],
                        'description' => $prediction['description'],
                        'main_text' => $prediction['structured_formatting']['main_text'] ?? '',
                        'secondary_text' => $prediction['structured_formatting']['secondary_text'] ?? '',
                    ])
                    ->toArray();
            } catch (\Exception $e) {
                Log::error('Google Places Autocomplete exception', ['error' => $e->getMessage()]);

                return [];
            }
        });
    }

    /**
     * Obtenir les détails d'un lieu par son place_id.
     */
    public function getPlaceDetails(string $placeId): ?array
    {
        $cacheKey = "place_details:{$placeId}";

        return Cache::remember($cacheKey, 86400, function () use ($placeId) {
            try {
                $response = Http::get("{$this->baseUrl}/place/details/json", [
                    'place_id' => $placeId,
                    'fields' => 'name,formatted_address,geometry,address_components,types',
                    'language' => 'fr',
                    'key' => $this->apiKey,
                ]);

                $data = $response->json();

                if ($data['status'] !== 'OK') {
                    return null;
                }

                $result = $data['result'];

                return [
                    'name' => $result['name'] ?? '',
                    'address' => $result['formatted_address'] ?? '',
                    'latitude' => $result['geometry']['location']['lat'] ?? null,
                    'longitude' => $result['geometry']['location']['lng'] ?? null,
                    'components' => collect($result['address_components'] ?? [])
                        ->mapWithKeys(fn ($comp) => [
                            $comp['types'][0] => [
                                'long_name' => $comp['long_name'],
                                'short_name' => $comp['short_name'],
                            ],
                        ])->toArray(),
                ];
            } catch (\Exception $e) {
                Log::error('Google Place Details exception', ['error' => $e->getMessage()]);

                return null;
            }
        });
    }

    // ─────────────────────────────────────────────
    // NEARBY SEARCH (Places API)
    // ─────────────────────────────────────────────

    /**
     * Google Places → mapping vers types PointOfInterest.
     */
    private const PLACE_TYPE_MAP = [
        'restaurant'     => 'restaurant',
        'supermarket'    => 'supermarket',
        'grocery_or_supermarket' => 'supermarket',
        'pharmacy'       => 'pharmacy',
        'hospital'       => 'hospital',
        'doctor'         => 'hospital',
        'bank'           => 'bank',
        'atm'            => 'bank',
        'bus_station'    => 'transport',
        'transit_station' => 'transport',
        'taxi_stand'     => 'transport',
        'shopping_mall'  => 'mall',
        'school'         => 'school',
        'university'     => 'school',
        'mosque'         => 'mosque',
        'church'         => 'church',
        'park'           => 'park',
        'gym'            => 'gym',
    ];

    /**
     * Rechercher les points d'intérêt à proximité d'une position.
     *
     * @param  float   $lat       Latitude du centre
     * @param  float   $lng       Longitude du centre
     * @param  int     $radius    Rayon en mètres (max 5000)
     * @param  array   $types     Types Google Places à chercher
     * @return array   Liste de POIs formatés
     */
    public function nearbySearch(
        float $lat,
        float $lng,
        int $radius = 1000,
        array $types = ['restaurant', 'supermarket', 'pharmacy', 'hospital', 'bank', 'bus_station', 'shopping_mall', 'school', 'mosque', 'church', 'park', 'gym'],
    ): array {
        $allResults = [];

        // Circuit breaker : si l'API a renvoyé REQUEST_DENIED récemment, on arrête
        // d'appeler Google pour éviter le spam de logs et les latences inutiles.
        if (Cache::get('google_places_api_disabled')) {
            Log::info('NearbySearch: circuit breaker actif, skip appel Google Places API');

            return [];
        }

        // Google Nearby Search ne supporte qu'un type par requête
        // On regroupe en batches pour limiter les appels API
        foreach ($types as $type) {
            $cacheKey = 'nearby_search:'.md5("{$lat},{$lng}:{$radius}:{$type}");

            $results = Cache::remember($cacheKey, 86400, function () use ($lat, $lng, $radius, $type) {
                try {
                    $response = Http::get("{$this->baseUrl}/place/nearbysearch/json", [
                        'location' => "{$lat},{$lng}",
                        'radius'   => $radius,
                        'type'     => $type,
                        'language' => 'fr',
                        'key'      => $this->apiKey,
                    ]);

                    $data = $response->json();

                    if (!in_array($data['status'], ['OK', 'ZERO_RESULTS'])) {
                        // REQUEST_DENIED = clé API non autorisée → activer circuit breaker 24h
                        // pour éviter 12 appels bloquants × N pages vues
                        if ($data['status'] === 'REQUEST_DENIED') {
                            Cache::put('google_places_api_disabled', true, 86400);
                        }
                        Log::warning('Google Nearby Search error', [
                            'status' => $data['status'],
                            'type'   => $type,
                        ]);

                        return [];
                    }

                    return collect($data['results'] ?? [])
                        ->take(3) // Max 3 résultats par type
                        ->map(function ($place) use ($lat, $lng, $type) {
                            $placeLat = $place['geometry']['location']['lat'];
                            $placeLng = $place['geometry']['location']['lng'];
                            $distance = $this->haversineDistance($lat, $lng, $placeLat, $placeLng);

                            return [
                                'name'             => $place['name'],
                                'type'             => self::PLACE_TYPE_MAP[$type] ?? 'other',
                                'google_type'      => $type,
                                'latitude'         => $placeLat,
                                'longitude'        => $placeLng,
                                'distance_meters'  => round($distance),
                                'walking_time_minutes' => (int) ceil($distance / 83), // ~5 km/h
                                'rating'           => $place['rating'] ?? null,
                                'place_id'         => $place['place_id'] ?? null,
                                'vicinity'         => $place['vicinity'] ?? null,
                                'open_now'         => $place['opening_hours']['open_now'] ?? null,
                            ];
                        })
                        ->toArray();
                } catch (\Exception $e) {
                    Log::error('Google Nearby Search exception', [
                        'error' => $e->getMessage(),
                        'type'  => $type,
                    ]);

                    return [];
                }
            });

            $allResults = array_merge($allResults, $results);
        }

        // Trier par distance
        usort($allResults, fn ($a, $b) => $a['distance_meters'] <=> $b['distance_meters']);

        return $allResults;
    }

    // ─────────────────────────────────────────────
    // REVERSE GEOCODING
    // ─────────────────────────────────────────────

    /**
     * Reverse geocoding : coordonnées → adresse structurée.
     *
     * @param  float  $lat
     * @param  float  $lng
     * @return array|null  ['address', 'commune', 'quartier', 'city', 'country_code']
     */
    public function reverseGeocode(float $lat, float $lng): ?array
    {
        $cacheKey = 'reverse_geocode:'.md5("{$lat},{$lng}");

        return Cache::remember($cacheKey, 86400, function () use ($lat, $lng) {
            try {
                $response = Http::get("{$this->baseUrl}/geocode/json", [
                    'latlng'   => "{$lat},{$lng}",
                    'language' => 'fr',
                    'key'      => $this->apiKey,
                ]);

                $data = $response->json();

                if ($data['status'] !== 'OK' || empty($data['results'])) {
                    return null;
                }

                $result = $data['results'][0];
                $components = collect($result['address_components'] ?? []);

                // Extraire commune et quartier
                $commune = $components->first(fn ($c) => in_array('locality', $c['types']))['long_name']
                    ?? $components->first(fn ($c) => in_array('administrative_area_level_2', $c['types']))['long_name']
                    ?? null;

                $quartier = $components->first(fn ($c) => in_array('sublocality_level_1', $c['types']))['long_name']
                    ?? $components->first(fn ($c) => in_array('sublocality', $c['types']))['long_name']
                    ?? $components->first(fn ($c) => in_array('neighborhood', $c['types']))['long_name']
                    ?? null;

                $city = $components->first(fn ($c) => in_array('administrative_area_level_1', $c['types']))['long_name']
                    ?? $commune;

                $countryCode = $components->first(fn ($c) => in_array('country', $c['types']))['short_name'] ?? 'CI';

                return [
                    'address'      => $result['formatted_address'],
                    'commune'      => $commune,
                    'quartier'     => $quartier,
                    'city'         => $city,
                    'country_code' => strtoupper($countryCode),
                    'place_id'     => $result['place_id'] ?? null,
                ];
            } catch (\Exception $e) {
                Log::error('Google Reverse Geocode exception', ['error' => $e->getMessage()]);

                return null;
            }
        });
    }

    // ─────────────────────────────────────────────
    // STREET VIEW
    // ─────────────────────────────────────────────

    /**
     * Vérifier si une image Street View est disponible à cette position.
     *
     * @return bool
     */
    public function hasStreetView(float $lat, float $lng, int $radius = 100): bool
    {
        $cacheKey = 'streetview_available:'.md5("{$lat},{$lng}:{$radius}");

        return Cache::remember($cacheKey, 604800, function () use ($lat, $lng, $radius) {
            try {
                $response = Http::get("{$this->baseUrl}/streetview/metadata", [
                    'location' => "{$lat},{$lng}",
                    'radius'   => $radius,
                    'key'      => $this->apiKey,
                ]);

                $data = $response->json();

                return ($data['status'] ?? '') === 'OK';
            } catch (\Exception $e) {
                return false;
            }
        });
    }

    /**
     * Générer l'URL d'une image Street View.
     *
     * @param  float   $lat
     * @param  float   $lng
     * @param  string  $size     "WxH"
     * @param  int     $heading  Direction (0-360)
     * @param  int     $pitch    Inclinaison (-90 à 90)
     * @return string  URL de l'image
     */
    public function getStreetViewUrl(
        float $lat,
        float $lng,
        string $size = '600x400',
        ?int $heading = null,
        int $pitch = 0,
    ): string {
        $params = [
            'location' => "{$lat},{$lng}",
            'size'     => $size,
            'pitch'    => $pitch,
            'key'      => $this->apiKey,
        ];

        if ($heading !== null) {
            $params['heading'] = $heading;
        }

        return "{$this->baseUrl}/streetview?".http_build_query($params);
    }

    // ─────────────────────────────────────────────
    // ADDRESS VALIDATION
    // ─────────────────────────────────────────────

    /**
     * Valider qu'une position GPS correspond à une adresse réelle.
     * Utilise le reverse geocoding + vérifie la cohérence.
     *
     * @param  float   $lat
     * @param  float   $lng
     * @param  string  $expectedCity  Ville attendue (ex: 'Abidjan')
     * @return array   ['valid', 'confidence', 'issues', 'suggested_address']
     */
    public function validateAddress(float $lat, float $lng, ?string $expectedCity = null): array
    {
        $result = [
            'valid'       => false,
            'confidence'  => 0,
            'issues'      => [],
            'address'     => null,
            'commune'     => null,
            'quartier'    => null,
        ];

        $geocode = $this->reverseGeocode($lat, $lng);

        if (!$geocode) {
            $result['issues'][] = 'Impossible de résoudre cette position en adresse';

            return $result;
        }

        $result['address']  = $geocode['address'];
        $result['commune']  = $geocode['commune'];
        $result['quartier'] = $geocode['quartier'];

        $confidence = 50; // Base

        // Vérifier que c'est en Côte d'Ivoire ou Burkina Faso
        if (in_array($geocode['country_code'], ['CI', 'BF'])) {
            $confidence += 20;
        } else {
            $result['issues'][] = "Position hors zone couverte (pays: {$geocode['country_code']})";

            return $result;
        }

        // Vérifier la cohérence avec la ville attendue
        if ($expectedCity) {
            $cityMatch = str_contains(
                mb_strtolower($geocode['city'].' '.$geocode['commune'].' '.$geocode['address']),
                mb_strtolower($expectedCity),
            );
            if ($cityMatch) {
                $confidence += 20;
            } else {
                $result['issues'][] = "La position semble être à {$geocode['city']}, pas à {$expectedCity}";
                $confidence -= 20;
            }
        } else {
            $confidence += 10;
        }

        // Vérifier que commune est rempli
        if ($geocode['commune']) {
            $confidence += 10;
        } else {
            $result['issues'][] = 'Commune non détectée — position trop vague';
        }

        $result['confidence'] = max(0, min(100, $confidence));
        $result['valid'] = $result['confidence'] >= 50;

        return $result;
    }

    // ─────────────────────────────────────────────
    // MAPBOX ISOCHRONE
    // ─────────────────────────────────────────────

    /**
     * Obtenir les zones accessibles en X minutes (Mapbox Isochrone API).
     *
     * @param  float   $lat
     * @param  float   $lng
     * @param  array   $minutes   Temps en minutes (ex: [5, 10, 15])
     * @param  string  $profile   walking|cycling|driving
     * @return array|null  GeoJSON FeatureCollection
     */
    public function getIsochrone(
        float $lat,
        float $lng,
        array $minutes = [5, 10, 15],
        string $profile = 'walking',
    ): ?array {
        $mapboxToken = config('services.mapbox.access_token');
        if (!$mapboxToken) {
            return null;
        }

        $contours = implode(',', $minutes);
        $cacheKey = 'isochrone:'.md5("{$lat},{$lng}:{$contours}:{$profile}");

        return Cache::remember($cacheKey, 86400, function () use ($lat, $lng, $contours, $profile, $mapboxToken) {
            try {
                $response = Http::get("https://api.mapbox.com/isochrone/v1/mapbox/{$profile}/{$lng},{$lat}", [
                    'contours_minutes' => $contours,
                    'polygons'         => 'true',
                    'generalize'       => 50,
                    'access_token'     => $mapboxToken,
                ]);

                if (!$response->successful()) {
                    Log::warning('Mapbox Isochrone API error', [
                        'status' => $response->status(),
                        'body'   => $response->body(),
                    ]);

                    return null;
                }

                return $response->json();
            } catch (\Exception $e) {
                Log::error('Mapbox Isochrone exception', ['error' => $e->getMessage()]);

                return null;
            }
        });
    }

    // ─────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────

    /**
     * Formater lat/lng en string.
     */
    protected function formatLatLng(string|array $point): string
    {
        if (is_string($point)) {
            return $point;
        }

        return ($point['lat'] ?? $point['latitude'] ?? 0).','.($point['lng'] ?? $point['longitude'] ?? 0);
    }

    /**
     * Distance Haversine entre deux points (en mètres).
     */
    protected function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000; // mètres

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
