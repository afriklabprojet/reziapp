<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LinkPreviewEndpointTest extends TestCase
{
    use RefreshDatabase;

    private const PUBLIC_URL = 'https://example.com/article';

    public function test_authenticated_user_can_fetch_link_preview_for_public_url(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne();

        Http::fake([
            'example.com/*' => Http::response(
                '<html><head><meta property="og:title" content="Preview Title"/></head></html>',
                200,
            ),
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('link.preview'), [
                'url' => self::PUBLIC_URL,
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('preview.title', 'Preview Title')
            ->assertJsonPath('preview.url', self::PUBLIC_URL);
    }

    public function test_private_url_is_rejected_without_outbound_http_request(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne();

        Http::fake();

        $response = $this->actingAs($user)
            ->postJson(route('link.preview'), [
                'url' => $this->linkLocalMetadataUrl(),
            ]);

        $response
            ->assertOk()
            ->assertJson([
                'success' => false,
                'preview' => null,
            ]);

        Http::assertNothingSent();
    }

    public function test_guest_cannot_access_link_preview_endpoint(): void
    {
        $this->postJson(route('link.preview'), [
            'url' => self::PUBLIC_URL,
        ])->assertUnauthorized();
    }

    private function linkLocalMetadataUrl(): string
    {
        return sprintf('http://%d.%d.%d.%d/latest/meta-data/', 169, 254, 169, 254);
    }
}
