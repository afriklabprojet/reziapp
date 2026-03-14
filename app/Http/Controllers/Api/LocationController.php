<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\CommuneList;
use App\Models\Country;
use App\Services\UserLocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API pour les données de localisation (pays, villes, communes)
 * Utilisé par les formulaires dynamiques (cascading selects)
 */
class LocationController extends Controller
{
    /**
     * Liste des pays actifs
     *
     * GET /api/v1/locations/countries
     */
    public function countries(): JsonResponse
    {
        $countries = Country::active()
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'phone_code']);

        return response()->json([
            'success' => true,
            'data' => $countries,
        ]);
    }

    /**
     * Villes d'un pays
     *
     * GET /api/v1/locations/countries/{code}/cities
     */
    public function cities(string $code): JsonResponse
    {
        $country = Country::where('code', strtoupper($code))->active()->first();

        if (! $country) {
            return response()->json(['success' => false, 'message' => 'Pays non trouvé.'], 404);
        }

        $cities = City::where('country_id', $country->id)
            ->active()
            ->ordered()
            ->get(['id', 'name', 'slug', 'latitude', 'longitude']);

        return response()->json([
            'success' => true,
            'data' => $cities,
        ]);
    }

    /**
     * Communes d'une ville
     *
     * GET /api/v1/locations/cities/{slug}/communes
     */
    public function communes(string $slug): JsonResponse
    {
        $city = City::where('slug', $slug)->active()->first();

        if (! $city) {
            return response()->json(['success' => false, 'message' => 'Ville non trouvée.'], 404);
        }

        $communes = CommuneList::where('city_id', $city->id)
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'latitude', 'longitude']);

        return response()->json([
            'success' => true,
            'data' => $communes,
        ]);
    }

    /**
     * Détecter la localisation depuis des coordonnées GPS
     *
     * POST /api/v1/locations/detect
     * Body: { latitude: float, longitude: float }
     */
    public function detect(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $location = UserLocationService::detectFromCoordinates(
            (float) $validated['latitude'],
            (float) $validated['longitude'],
        );

        return response()->json([
            'success' => true,
            'data' => $location,
        ]);
    }

    /**
     * Définir manuellement la localisation (choix utilisateur)
     *
     * POST /api/v1/locations/set
     * Body: { country_code: string, city: string }
     */
    public function setLocation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'country_code' => ['required', 'string', 'size:2'],
            'city' => ['required', 'string', 'max:100'],
        ]);

        $location = UserLocationService::set(
            $validated['country_code'],
            $validated['city'],
        );

        return response()->json([
            'success' => true,
            'data' => $location,
        ]);
    }
}
