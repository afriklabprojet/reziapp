<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ResidenceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'slug' => $this->slug,

            // Localisation
            'location' => [
                'address' => $this->address,
                'city' => $this->city,
                'country' => $this->country,
                'country_code' => $this->country_code,
                'coordinates' => [
                    'latitude' => (float) $this->latitude,
                    'longitude' => (float) $this->longitude,
                ],
                'distance' => $this->when(isset($this->distance), function () {
                    return round($this->distance, 2).' km';
                }),
            ],

            // Tarification
            'pricing' => [
                'price' => (float) $this->price,
                'currency' => 'XOF', // Franc CFA
                'formatted' => number_format($this->price, 0, ',', ' ').' FCFA',
            ],

            // Caractéristiques
            'features' => [
                'bedrooms' => $this->bedrooms,
                'bathrooms' => $this->bathrooms,
                'area' => (float) $this->area,
                'type' => $this->type,
                'furnished' => (bool) $this->furnished,
            ],

            // Disponibilité
            'availability' => [
                'available' => (bool) $this->available,
                'status' => $this->status,
            ],

            // Relations
            'owner' => $this->whenLoaded('owner', fn () => [
                'id' => $this->owner->id,
                'name' => $this->owner->name,
                'email' => $this->when(
                    $request->user()?->role === 'admin',
                    fn () => $this->owner->email,
                ),
            ]),

            'photos' => PhotoResource::collection($this->whenLoaded('photos')),
            'amenities' => AmenityResource::collection($this->whenLoaded('amenities')),

            // Statistiques (seulement pour owner/admin)
            'stats' => $this->when(
                $request->user() && (
                    $request->user()->id === $this->owner_id ||
                    $request->user()->role === 'admin'
                ),
                [
                    'views_count' => $this->views_count ?? 0,
                    'contacts_count' => $this->contacts_count ?? 0,
                ],
            ),

            // Métadonnées
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'approved_at' => $this->when($this->approved_at, function () {
                return $this->approved_at->toIso8601String();
            }),
        ];
    }
}
