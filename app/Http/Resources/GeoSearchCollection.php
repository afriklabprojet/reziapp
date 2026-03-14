<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Collection pour les résultats de recherche géolocalisée
 *
 * Inclut les métadonnées de recherche (rayon, centre, statistiques)
 */
class GeoSearchCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = GeoSearchResource::class;

    /**
     * Paramètres de recherche pour les métadonnées
     */
    protected ?array $searchParams = null;

    /**
     * Statistiques de la zone
     */
    protected ?array $zoneStats = null;

    /**
     * Définir les paramètres de recherche
     */
    public function withSearchParams(array $params): self
    {
        $this->searchParams = $params;

        return $this;
    }

    /**
     * Définir les statistiques de zone
     */
    public function withZoneStats(array $stats): self
    {
        $this->zoneStats = $stats;

        return $this;
    }

    /**
     * Transform the resource collection into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'search' => $this->getSearchInfo(),
            'zone_stats' => $this->zoneStats,
        ];
    }

    /**
     * Informations sur la recherche effectuée
     */
    protected function getSearchInfo(): array
    {
        if (!$this->searchParams) {
            return [];
        }

        return [
            'center' => [
                'latitude' => $this->searchParams['latitude'] ?? null,
                'longitude' => $this->searchParams['longitude'] ?? null,
            ],
            'radius' => $this->searchParams['radius'] ?? null,
            'radius_label' => $this->formatRadiusLabel($this->searchParams['radius'] ?? null),
            'filters' => array_filter([
                'min_price' => $this->searchParams['min_price'] ?? null,
                'max_price' => $this->searchParams['max_price'] ?? null,
                'bedrooms' => $this->searchParams['bedrooms'] ?? null,
                'commune' => $this->searchParams['commune'] ?? null,
                'furnished_only' => $this->searchParams['furnished_only'] ?? null,
                'available_only' => $this->searchParams['available_only'] ?? true,
            ]),
            'sort' => $this->searchParams['sort'] ?? 'distance',
        ];
    }

    /**
     * Formate le libellé du rayon
     */
    protected function formatRadiusLabel(?int $radius): string
    {
        if (!$radius) {
            return '';
        }

        if ($radius < 1000) {
            return "dans un rayon de {$radius} m";
        }

        return 'dans un rayon de '.($radius / 1000).' km';
    }

    /**
     * Informations supplémentaires pour la réponse paginée
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'currency' => 'XOF',
                'distance_unit' => 'meters',
                'allowed_radii' => [100, 200, 300, 400, 500],
                'max_radius' => 500,
                'api_version' => 'v1',
            ],
            'links' => [
                'documentation' => '/api/v1/geo/docs',
            ],
        ];
    }
}
