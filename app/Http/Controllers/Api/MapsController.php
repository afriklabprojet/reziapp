<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Residence;
use App\Services\GoogleMapsService;
use App\Services\NearbyPlacesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API endpoints pour les fonctionnalités Maps avancées.
 *
 * - Points d'intérêt à proximité
 * - Reverse geocoding
 * - Itinéraire (directions)
 * - Isochrone (zones accessibles)
 * - Street View
 * - Validation d'adresse
 */
class MapsController extends Controller
{
    public function __construct(
        private GoogleMapsService $maps,
    ) {
    }

    // ──────────────────────────────────────────────
    // 1. POINTS D'INTÉRÊT À PROXIMITÉ
    // ──────────────────────────────────────────────

    /**
     * Récupérer les POIs autour d'une résidence.
     * Les POIs sont en cache BDD — si vide, fetch en temps réel.
     */
    public function nearbyPlaces(Residence $residence): JsonResponse
    {
        // Charger depuis la BDD d'abord
        $pois = $residence->pointsOfInterest()
            ->orderBy('distance_meters')
            ->get();

        // Si aucun POI en BDD, tenter un fetch en temps réel
        if ($pois->isEmpty() && $residence->latitude && $residence->longitude) {
            $service = app(NearbyPlacesService::class);
            $service->fetchAndSave($residence);
            $pois = $residence->pointsOfInterest()
                ->orderBy('distance_meters')
                ->get();
        }

        // Regrouper par type
        $grouped = $pois->groupBy('type')->map(function ($items, $type) {
            $typeInfo = \App\Models\PointOfInterest::TYPES[$type] ?? ['icon' => '📍', 'label' => ucfirst($type)];

            return [
                'type'  => $type,
                'icon'  => $typeInfo['icon'],
                'label' => $typeInfo['label'],
                'count' => $items->count(),
                'places' => $items->map(fn ($poi) => [
                    'name'          => $poi->name,
                    'distance'      => $poi->formatted_distance,
                    'walking_time'  => $poi->formatted_walking_time,
                    'distance_meters' => (int) $poi->distance_meters,
                    'latitude'      => (float) $poi->latitude,
                    'longitude'     => (float) $poi->longitude,
                ])->values(),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data'    => $grouped,
            'total'   => $pois->count(),
        ]);
    }

    // ──────────────────────────────────────────────
    // 2. REVERSE GEOCODING
    // ──────────────────────────────────────────────

    /**
     * Coordonnées → adresse structurée (commune, quartier, adresse).
     * Utilisé quand le propriétaire place le marqueur sur la carte.
     */
    public function reverseGeocode(Request $request): JsonResponse
    {
        $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
        ]);

        $result = $this->maps->reverseGeocode(
            (float) $request->lat,
            (float) $request->lng
        );

        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de résoudre cette position',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data'    => $result,
        ]);
    }

    // ──────────────────────────────────────────────
    // 3. ITINÉRAIRE (DIRECTIONS)
    // ──────────────────────────────────────────────

    /**
     * Obtenir l'itinéraire de l'utilisateur vers une résidence.
     */
    public function directions(Request $request, Residence $residence): JsonResponse
    {
        $request->validate([
            'lat'  => 'required|numeric|between:-90,90',
            'lng'  => 'required|numeric|between:-180,180',
            'mode' => 'in:driving,walking,bicycling,transit',
        ]);

        if (!$residence->latitude || !$residence->longitude) {
            return response()->json([
                'success' => false,
                'message' => 'Coordonnées de la résidence non disponibles',
            ], 422);
        }

        $directions = $this->maps->getDirections(
            ['lat' => (float) $request->lat, 'lng' => (float) $request->lng],
            ['lat' => $residence->latitude, 'lng' => $residence->longitude],
            $request->input('mode', 'driving'),
        );

        if (!$directions) {
            return response()->json([
                'success' => false,
                'message' => 'Itinéraire non disponible',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data'    => $directions,
        ]);
    }

    // ──────────────────────────────────────────────
    // 4. ISOCHRONE (ZONES ACCESSIBLES)
    // ──────────────────────────────────────────────

    /**
     * Zones accessibles en X minutes depuis une résidence (Mapbox Isochrone).
     */
    public function isochrone(Request $request, Residence $residence): JsonResponse
    {
        $request->validate([
            'minutes' => 'array|max:3',
            'minutes.*' => 'integer|min:1|max:60',
            'profile' => 'in:walking,cycling,driving',
        ]);

        if (!$residence->latitude || !$residence->longitude) {
            return response()->json([
                'success' => false,
                'message' => 'Coordonnées non disponibles',
            ], 422);
        }

        $minutes = $request->input('minutes', [5, 10, 15]);
        $profile = $request->input('profile', 'walking');

        $isochrone = $this->maps->getIsochrone(
            $residence->latitude,
            $residence->longitude,
            $minutes,
            $profile,
        );

        if (!$isochrone) {
            return response()->json([
                'success' => false,
                'message' => 'Isochrone non disponible',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data'    => $isochrone,
            'profile' => $profile,
            'minutes' => $minutes,
        ]);
    }

    // ──────────────────────────────────────────────
    // 5. STREET VIEW
    // ──────────────────────────────────────────────

    /**
     * Vérifier disponibilité + URL Street View pour une résidence.
     */
    public function streetView(Residence $residence): JsonResponse
    {
        if (!$residence->latitude || !$residence->longitude) {
            return response()->json([
                'success'   => false,
                'available' => false,
            ]);
        }

        $available = $this->maps->hasStreetView($residence->latitude, $residence->longitude);

        $data = [
            'success'   => true,
            'available' => $available,
        ];

        if ($available) {
            $data['image_url'] = $this->maps->getStreetViewUrl(
                $residence->latitude,
                $residence->longitude,
                '800x400',
            );
            // 4 directions pour un panorama
            $data['panorama'] = [
                ['heading' => 0,   'url' => $this->maps->getStreetViewUrl($residence->latitude, $residence->longitude, '400x300', 0)],
                ['heading' => 90,  'url' => $this->maps->getStreetViewUrl($residence->latitude, $residence->longitude, '400x300', 90)],
                ['heading' => 180, 'url' => $this->maps->getStreetViewUrl($residence->latitude, $residence->longitude, '400x300', 180)],
                ['heading' => 270, 'url' => $this->maps->getStreetViewUrl($residence->latitude, $residence->longitude, '400x300', 270)],
            ];
        }

        return response()->json($data);
    }

    // ──────────────────────────────────────────────
    // 6. VALIDATION D'ADRESSE
    // ──────────────────────────────────────────────

    /**
     * Valider que les coordonnées correspondent à une vraie adresse.
     */
    public function validateAddress(Request $request): JsonResponse
    {
        $request->validate([
            'lat'  => 'required|numeric|between:-90,90',
            'lng'  => 'required|numeric|between:-180,180',
            'city' => 'nullable|string|max:100',
        ]);

        $result = $this->maps->validateAddress(
            (float) $request->lat,
            (float) $request->lng,
            $request->input('city'),
        );

        return response()->json([
            'success' => true,
            'data'    => $result,
        ]);
    }
}
