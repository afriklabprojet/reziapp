<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\City;
use App\Models\Country;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

/**
 * Service de localisation utilisateur (style Airbnb)
 *
 * Détermine le pays et la ville de l'utilisateur à partir de :
 * 1. Session (choix explicite)
 * 2. Coordonnées GPS (reverse geocoding simplifié)
 * 3. Fallback → CI / Abidjan
 *
 * Stocke le résultat en session pour les requêtes suivantes.
 */
class UserLocationService
{
    /**
     * Clé de session pour la localisation
     */
    public const SESSION_KEY = 'user_location';

    /**
     * Récupérer la localisation courante depuis la session
     */
    public static function current(): array
    {
        return Session::get(self::SESSION_KEY, self::defaultLocation());
    }

    /**
     * Localisation par défaut (Abidjan, CI)
     */
    public static function defaultLocation(): array
    {
        return [
            'country_code' => 'CI',
            'country_name' => "Côte d'Ivoire",
            'city' => 'Abidjan',
            'latitude' => config('rezi.default_latitude', 5.3600),
            'longitude' => config('rezi.default_longitude', -4.0083),
            'detected' => false,
        ];
    }

    /**
     * Définir manuellement la localisation (choix utilisateur)
     */
    public static function set(string $countryCode, string $city): array
    {
        $country = Country::where('code', strtoupper($countryCode))->active()->first();
        $cityModel = $country
            ? City::where('country_id', $country->id)->where('name', $city)->active()->first()
            : null;

        $location = [
            'country_code' => $country?->code ?? 'CI',
            'country_name' => $country?->name ?? "Côte d'Ivoire",
            'city' => $cityModel?->name ?? $city,
            'latitude' => $cityModel?->latitude ?? $country?->latitude ?? config('rezi.default_latitude'),
            'longitude' => $cityModel?->longitude ?? $country?->longitude ?? config('rezi.default_longitude'),
            'detected' => false,
        ];

        Session::put(self::SESSION_KEY, $location);

        return $location;
    }

    /**
     * Détecter la localisation à partir de coordonnées GPS
     * Trouve la ville la plus proche dans la BDD
     */
    public static function detectFromCoordinates(float $latitude, float $longitude): array
    {
        $cacheKey = 'location_detect_' . round($latitude, 2) . '_' . round($longitude, 2);

        $location = Cache::remember($cacheKey, 3600, function () use ($latitude, $longitude) {
            // Trouver la ville la plus proche (formule Haversine simplifiée)
            $city = City::active()
                ->selectRaw('*, (
                    6371 * acos(
                        cos(radians(?)) * cos(radians(latitude)) *
                        cos(radians(longitude) - radians(?)) +
                        sin(radians(?)) * sin(radians(latitude))
                    )
                ) AS distance', [$latitude, $longitude, $latitude])
                ->having('distance', '<', 100) // max 100km
                ->orderBy('distance')
                ->with('country')
                ->first();

            if ($city && $city->country) {
                return [
                    'country_code' => $city->country->code,
                    'country_name' => $city->country->name,
                    'city' => $city->name,
                    'latitude' => $city->latitude,
                    'longitude' => $city->longitude,
                    'detected' => true,
                ];
            }

            // Si aucune ville proche, vérifier dans quel pays on est
            $country = Country::active()
                ->whereRaw('? BETWEEN min_lat AND max_lat', [$latitude])
                ->whereRaw('? BETWEEN min_lng AND max_lng', [$longitude])
                ->first();

            if ($country) {
                // Prendre la première ville du pays
                $firstCity = City::where('country_id', $country->id)->active()->ordered()->first();

                return [
                    'country_code' => $country->code,
                    'country_name' => $country->name,
                    'city' => $firstCity?->name ?? '',
                    'latitude' => $firstCity?->latitude ?? $country->latitude,
                    'longitude' => $firstCity?->longitude ?? $country->longitude,
                    'detected' => true,
                ];
            }

            // Hors zone → fallback
            return self::defaultLocation();
        });

        Session::put(self::SESSION_KEY, $location);

        return $location;
    }

    /**
     * Vérifier si une localisation a été définie
     */
    public static function isSet(): bool
    {
        return Session::has(self::SESSION_KEY);
    }

    /**
     * Effacer la localisation (reset)
     */
    public static function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    /**
     * Obtenir toutes les villes groupées par pays (pour le picker)
     */
    public static function availableLocations(): array
    {
        return Cache::remember('available_locations', config('rezi.cache_ttl', 3600), function () {
            $countries = Country::active()
                ->with(['cities' => fn ($q) => $q->active()->ordered()])
                ->orderBy('name')
                ->get();

            return $countries->map(fn ($country) => [
                'code' => $country->code,
                'name' => $country->name,
                'flag' => self::countryFlag($country->code),
                'cities' => $country->cities->map(fn ($city) => [
                    'name' => $city->name,
                    'slug' => $city->slug,
                    'latitude' => $city->latitude,
                    'longitude' => $city->longitude,
                ])->values()->toArray(),
            ])->values()->toArray();
        });
    }

    /**
     * Emoji drapeau pour un code pays
     */
    public static function countryFlag(string $code): string
    {
        $code = strtoupper($code);

        return match ($code) {
            'CI' => '🇨🇮',
            'BF' => '🇧🇫',
            'SN' => '🇸🇳',
            'ML' => '🇲🇱',
            'GN' => '🇬🇳',
            default => '🌍',
        };
    }
}
