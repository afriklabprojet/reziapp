<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests critiques de l'API Auth — Sécurité + Edge cases
 */
#[Group('api')]
#[Group('auth')]
class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    // ─── REGISTER ───────────────────────────────────────────────

    #[Test]
    public function register_creates_user_with_role_user(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.role', 'user')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['user' => ['id', 'name', 'role'], 'token'],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'role' => 'user',
        ]);
    }

    #[Test]
    public function register_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'dupe@example.com']);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Dupe',
            'email' => 'dupe@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    #[Test]
    public function register_ignores_role_in_payload_security(): void
    {
        // Tentative d'escalation de privilège
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Hacker',
            'email' => 'hacker@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'role' => 'admin', // Doit être ignoré
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', [
            'email' => 'hacker@example.com',
            'role' => 'user', // JAMAIS admin
        ]);
    }

    #[Test]
    public function register_rejects_weak_password(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Weak',
            'email' => 'weak@example.com',
            'password' => '12345',
            'password_confirmation' => '12345',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('password');
    }

    // ─── LOGIN ──────────────────────────────────────────────────

    #[Test]
    public function login_returns_token_on_success(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('Password1!'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'Password1!',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => ['user' => ['id', 'name', 'role'], 'token'],
            ]);
    }

    #[Test]
    public function login_fails_with_wrong_password(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('Password1!'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'wrong',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    #[Test]
    public function login_fails_with_nonexistent_email(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'ghost@example.com',
            'password' => 'Password1!',
        ]);

        $response->assertStatus(422);
    }

    // ─── AUTHENTICATED ROUTES ───────────────────────────────────

    #[Test]
    public function user_endpoint_returns_authenticated_user(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/auth/user');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.name', $user->name);
    }

    #[Test]
    public function user_endpoint_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/auth/user');

        $response->assertStatus(401);
    }

    #[Test]
    public function logout_revokes_token(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    // ─── ADMIN ──────────────────────────────────────────────────

    #[Test]
    public function non_admin_cannot_list_users(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/admin/users');

        $response->assertStatus(403);
    }

    #[Test]
    public function admin_can_list_users_with_pagination(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'two_factor_enabled' => true]);
        User::factory()->count(5)->create();
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/admin/users?per_page=3');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data', 'meta']);
    }

    #[Test]
    public function admin_cannot_downgrade_own_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'two_factor_enabled' => true]);
        Sanctum::actingAs($admin);

        $response = $this->patchJson("/api/v1/admin/users/{$admin->id}/role", [
            'role' => 'user',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'role' => 'admin',
        ]);
    }
}
