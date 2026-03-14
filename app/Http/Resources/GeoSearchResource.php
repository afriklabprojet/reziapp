<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource pour les résultats de recherche géolocalisée
 *
 * Optimisée pour l'affichage sur carte avec distance calculée
 */
class GeoSearchResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->name,

            // Prix
            'price' => (float) $this->price_per_month,
            'price_formatted' => number_format($this->price_per_month, 0, ',', ' ').' FCFA/mois',

            // Localisation
            'location' => [
                'address' => $this->address,
                'country_code' => $this->country_code,
                'city' => $this->city,
                'commune' => $this->commune,
                'quartier' => $this->quartier,
                'latitude' => (float) $this->latitude,
                'longitude' => (float) $this->longitude,
                'distance_meters' => $this->when(
                    isset($this->distance_meters),
                    fn () => round($this->distance_meters),
                ),
                'distance_label' => $this->when(
                    isset($this->distance_meters),
                    fn () => $this->formatDistance($this->distance_meters),
                ),
            ],

            // Photo principale pour la carte/liste
            'thumbnail' => $this->getMainPhotoUrl(),

            // Status
            'available' => $this->is_available,
            'is_new' => $this->created_at->isAfter(now()->subDays(7)),

            // Relations conditionnelles
            'photos_count' => $this->whenCounted('photos'),
            'amenities' => $this->whenLoaded('amenities', function () {
                return $this->amenities->pluck('name');
            }),

            // URLs utiles
            'urls' => [
                'show' => route('residences.show', $this->id),
                'api' => route('api.residences.show', $this->id),
            ],
        ];
    }

    /**
     * Formate la distance pour l'affichage
     */
    private function formatDistance(?float $meters): string
    {
        if ($meters === null) {
            return '';
        }

        if ($meters < 100) {
            return 'À '.round($meters).' m';
        }

        if ($meters < 1000) {
            return 'À '.round($meters / 10) * 10 .' m';
        }

        return 'À '.number_format($meters / 1000, 1, ',', '').' km';
    }

    /**
     * Get main photo URL or placeholder
     */
    private function getMainPhotoUrl(): string
    {
        if ($this->relationLoaded('photos') && $this->photos->isNotEmpty()) {
            $mainPhoto = $this->photos->first();

            return asset('storage/'.$mainPhoto->path);
        }

        // Placeholder basé sur le type de bien
        return asset('images/residence-placeholder.jpg');
    }

    /**
     * Informations supplémentaires pour la réponse
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'currency' => 'XOF',
                'distance_unit' => 'meters',
            ],
        ];
    }
}
