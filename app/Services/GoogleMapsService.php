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
        string $language = 'fr'
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
        string $mode = 'driving'
    ): ?array {
        $originsStr = collect($origins)->map(fn ($o) => $this->formatLatLng($o))->implode('|');
        $destsStr = collect($destinations)->map(fn ($d) => $this->formatLatLng($d))->implode('|');

        $cacheKey = "distance_matrix:" . md5("{$originsStr}:{$destsStr}:{$mode}");

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
        string $mode = 'driving'
    ): ?array {
        $results = $this->getDistanceMatrix(
            [['lat' => $userLat, 'lng' => $userLng]],
            [['lat' => $residenceLat, 'lng' => $residenceLng]],
            $mode
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
        string $maptype = 'roadmap'
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

        $url = "{$this->baseUrl}/staticmap?" . http_build_query($params);

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
        string $types = 'geocode'
    ): array {
        $cacheKey = "places_autocomplete:" . md5("{$input}:{$types}:" . implode(',', $countries));

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

        return ($point['lat'] ?? $point['latitude'] ?? 0) . ',' . ($point['lng'] ?? $point['longitude'] ?? 0);
    }
}
