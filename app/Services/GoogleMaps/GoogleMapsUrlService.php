<?php

namespace App\Services\GoogleMaps;

class GoogleMapsUrlService
{
    protected string $apiKey;
    protected string $baseUrl;

    public function __construct(?string $apiKey = null, ?string $baseUrl = null)
    {
        $this->apiKey = $apiKey ?? config('services.google_maps.key') ?? '';
        $this->baseUrl = $baseUrl ?? 'https://maps.googleapis.com/maps/api';
    }

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
            'markers' => "color:red|label:R|{$lat},{$lng}",
        ];

        $url = "{$this->baseUrl}/staticmap?".http_build_query($params);

        foreach ($markers as $marker) {
            $color = $marker['color'] ?? 'blue';
            $label = $marker['label'] ?? '';
            $url .= "&markers=color:{$color}|label:{$label}|{$marker['lat']},{$marker['lng']}";
        }

        return $url;
    }

    public function getResidenceStaticMapUrl(float $lat, float $lng): string
    {
        return $this->getStaticMapUrl($lat, $lng, 15, '600x250');
    }

    public function getStreetViewUrl(
        float $lat,
        float $lng,
        string $size = '600x400',
        ?int $heading = null,
        int $pitch = 0,
    ): string {
        $params = [
            'location' => "{$lat},{$lng}",
            'size' => $size,
            'pitch' => $pitch,
            'key' => $this->apiKey,
        ];

        if ($heading !== null) {
            $params['heading'] = $heading;
        }

        return "{$this->baseUrl}/streetview?".http_build_query($params);
    }
}

