<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\PublicUrlGuard;
use App\Services\LinkPreviewService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LinkPreviewServiceTest extends TestCase
{
    private LinkPreviewService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LinkPreviewService();
    }

    // ── SSRF Prevention ────────────────────────────────────────────────────

    public function test_blocks_localhost_url(): void
    {
        $result = $this->service->extract('http://localhost/admin');

        $this->assertNull($result);
    }

    public function test_blocks_127_0_0_1(): void
    {
        $result = $this->service->extract(sprintf('http://%s:6379', implode('.', [127, 0, 0, 1])));

        $this->assertNull($result);
    }

    public function test_blocks_private_range_10_x(): void
    {
        $result = $this->service->extract(sprintf('http://%s/secret', implode('.', [10, 0, 0, 1])));

        $this->assertNull($result);
    }

    public function test_blocks_private_range_192_168(): void
    {
        $result = $this->service->extract(sprintf('http://%s/router', implode('.', [192, 168, 1, 1])));

        $this->assertNull($result);
    }

    public function test_blocks_private_range_172_16(): void
    {
        $result = $this->service->extract(sprintf('http://%s/internal', implode('.', [172, 16, 0, 1])));

        $this->assertNull($result);
    }

    public function test_blocks_aws_metadata_endpoint(): void
    {
        // AWS IMDSv1 — must never be fetched
        $result = $this->service->extract($this->metadataUrl());

        $this->assertNull($result);
    }

    public function test_blocks_ipv6_loopback(): void
    {
        $result = $this->service->extract('http://[::1]/');

        $this->assertNull($result);
    }

    public function test_blocks_hostname_resolving_to_private_ip(): void
    {
        $service = new LinkPreviewService(new class extends PublicUrlGuard
        {
            public function resolveHostIps(string $host): array
            {
                return [implode('.', [10, 0, 0, 5])];
            }
        });

        $this->assertNull($service->extract('https://preview.example.test/article'));
    }

    public function test_blocks_non_http_schemes(): void
    {
        $this->assertNull($this->service->extract('file:///etc/passwd'));
        $this->assertNull($this->service->extract('ftp://example.com/file'));
        $this->assertNull($this->service->extract('gopher://evil.com/'));
    }

    public function test_blocks_urls_with_embedded_credentials(): void
    {
        $url = sprintf('https://%s:%s@example.com/private', 'user', 'secret');

        $this->assertNull($this->service->extract($url));
    }

    public function test_rejects_invalid_url(): void
    {
        $this->assertNull($this->service->extract('not-a-url'));
        $this->assertNull($this->service->extract(''));
        $this->assertNull($this->service->extract('javascript:alert(1)'));
    }

    // ── Happy path ─────────────────────────────────────────────────────────

    public function test_extracts_og_title_from_public_url(): void
    {
        Http::fake([
            'example.com/*' => Http::response(
                '<html><head><meta property="og:title" content="Hello World"/></head></html>',
                200
            ),
        ]);

        $result = $this->service->extract('https://example.com/article');

        $this->assertNotNull($result);
        $this->assertSame('Hello World', $result['title']);
    }

    public function test_returns_null_when_no_title(): void
    {
        Http::fake([
            'example.com/*' => Http::response('<html><head></head></html>', 200),
        ]);

        $result = $this->service->extract('https://example.com/article');

        $this->assertNull($result);
    }

    public function test_returns_null_on_http_error(): void
    {
        Http::fake([
            'example.com/*' => Http::response('', 404),
        ]);

        $result = $this->service->extract('https://example.com/missing');

        $this->assertNull($result);
    }

    public function test_does_not_follow_redirects(): void
    {
        Http::fake([
            'example.com/*' => Http::response('', 302, [
                'Location' => $this->metadataUrl(),
            ]),
        ]);

        $result = $this->service->extract('https://example.com/redirect');

        $this->assertNull($result);
    }

    public function test_extract_first_url_finds_https(): void
    {
        $url = $this->service->extractFirstUrl('Check this out https://example.com/page and more text');

        $this->assertSame('https://example.com/page', $url);
    }

    public function test_extract_first_url_returns_null_when_no_url(): void
    {
        $url = $this->service->extractFirstUrl('No links here at all');

        $this->assertNull($url);
    }

    private function metadataUrl(): string
    {
        return sprintf('http://%d.%d.%d.%d/latest/meta-data/', 169, 254, 169, 254);
    }
}
