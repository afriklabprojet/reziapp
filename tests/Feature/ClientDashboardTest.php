<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests du tableau de bord client
 * Couvre : dashboard, historiques, statistiques
 */
class ClientDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'user']);
    }

    // ========================================
    // DASHBOARD
    // ========================================

    #[Test]
    public function user_can_access_dashboard(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('client.dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('client.dashboard');
    }

    #[Test]
    public function owner_is_redirected_from_client_dashboard(): void
    {
        $owner = User::factory()->create(['role' => 'owner', 'two_factor_enabled' => true]);

        $response = $this->actingAs($owner)
            ->get(route('client.dashboard'));

        $response->assertRedirect(route('owner.dashboard'));
    }

    #[Test]
    public function unauthenticated_user_cannot_access_dashboard(): void
    {
        $response = $this->get(route('client.dashboard'));

        $response->assertRedirect(route('login'));
    }

    // ========================================
    // HISTORIQUE DE RECHERCHE
    // ========================================

    #[Test]
    public function user_can_view_search_history(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('client.search-history'));

        $response->assertStatus(200);
        $response->assertViewIs('client.search-history');
    }

    #[Test]
    public function user_can_clear_search_history(): void
    {
        $response = $this->actingAs($this->user)
            ->delete(route('client.search-history.clear'));

        $response->assertRedirect();
    }

    // ========================================
    // HISTORIQUE DE CONSULTATION
    // ========================================

    #[Test]
    public function user_can_view_view_history(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('client.view-history'));

        $response->assertStatus(200);
        $response->assertViewIs('client.view-history');
    }

    #[Test]
    public function user_can_clear_view_history(): void
    {
        $response = $this->actingAs($this->user)
            ->delete(route('client.view-history.clear'));

        $response->assertRedirect();
    }

    // ========================================
    // COMPARAISON
    // ========================================

    #[Test]
    public function user_can_access_compare_page(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('client.compare'));

        $response->assertStatus(200);
        $response->assertViewIs('client.compare');
    }

    // ========================================
    // ALERTES
    // ========================================

    #[Test]
    public function user_can_view_alerts(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('client.alerts'));

        $response->assertStatus(200);
        $response->assertViewIs('client.alerts');
    }

    // ========================================
    // CONTACTS
    // ========================================

    #[Test]
    public function user_can_view_contacts(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('client.contacts'));

        $response->assertStatus(200);
        $response->assertViewIs('client.contacts');
    }

    // ========================================
    // AVIS
    // ========================================

    #[Test]
    public function user_can_view_their_reviews(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('client.reviews'));

        $response->assertStatus(200);
        $response->assertViewIs('client.reviews');
    }

    // ========================================
    // STATISTIQUES
    // ========================================

    #[Test]
    public function user_can_view_statistics(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('client.statistics'));

        $response->assertStatus(200);
        $response->assertViewIs('client.statistics');
    }
}
