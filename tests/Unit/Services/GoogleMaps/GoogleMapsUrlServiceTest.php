<?php

namespace Tests\Unit\Services\GoogleMaps;

use App\Services\GoogleMaps\GoogleMapsUrlService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GoogleMapsUrlServiceTest extends TestCase
{
    private const MAPS_BASE_URL = 'https://maps.googleapis.com/maps/api';

    #[Test]
    public function it_builds_static_map_urls_with_primary_and_extra_markers(): void
    {
        $service = new GoogleMapsUrlService('maps-test-key', self::MAPS_BASE_URL);

        $url = $service->getStaticMapUrl(5.345, -4.025, 14, '800x400', [
            ['lat' => 5.35, 'lng' => -4.02, 'color' => 'blue', 'label' => 'A'],
            ['lat' => 5.36, 'lng' => -4.01, 'color' => 'green', 'label' => 'B'],
        ], 'terrain');

        $this->assertStringStartsWith('https://maps.googleapis.com/maps/api/staticmap?', $url);
        $this->assertStringContainsString('center=5.345%2C-4.025', $url);
        $this->assertStringContainsString('zoom=14', $url);
        $this->assertStringContainsString('size=800x400', $url);
        $this->assertStringContainsString('maptype=terrain', $url);
        $this->assertStringContainsString('language=fr', $url);
        $this->assertStringContainsString('key=maps-test-key', $url);
        $this->assertStringContainsString('markers=color%3Ared%7Clabel%3AR%7C5.345%2C-4.025', $url);
        $this->assertStringContainsString('&markers=color:blue|label:A|5.35,-4.02', $url);
        $this->assertStringContainsString('&markers=color:green|label:B|5.36,-4.01', $url);
    }

    #[Test]
    public function it_builds_residence_static_map_urls_with_the_expected_default_size(): void
    {
        $service = new GoogleMapsUrlService('maps-test-key', self::MAPS_BASE_URL);

        $url = $service->getResidenceStaticMapUrl(5.345, -4.025);

        $this->assertStringContainsString('size=600x250', $url);
        $this->assertStringContainsString('zoom=15', $url);
    }

    #[Test]
    public function it_builds_street_view_urls_and_includes_heading_only_when_provided(): void
    {
        $service = new GoogleMapsUrlService('maps-test-key', self::MAPS_BASE_URL);

        $withHeading = $service->getStreetViewUrl(5.345, -4.025, '700x500', 120, -10);
        $withoutHeading = $service->getStreetViewUrl(5.345, -4.025);

        $this->assertStringStartsWith('https://maps.googleapis.com/maps/api/streetview?', $withHeading);
        $this->assertStringContainsString('location=5.345%2C-4.025', $withHeading);
        $this->assertStringContainsString('size=700x500', $withHeading);
        $this->assertStringContainsString('pitch=-10', $withHeading);
        $this->assertStringContainsString('heading=120', $withHeading);
        $this->assertStringContainsString('key=maps-test-key', $withHeading);

        $this->assertStringContainsString('size=600x400', $withoutHeading);
        $this->assertStringNotContainsString('heading=', $withoutHeading);
    }
}
