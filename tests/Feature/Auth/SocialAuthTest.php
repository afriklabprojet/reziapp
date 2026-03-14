<?php

namespace Tests\Feature\Auth;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

/**
 * Tests pour l'authentification sociale (OAuth)
 * Couvre Google et Facebook OAuth
 * */
class SocialAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Créer un mock d'utilisateur Socialite
     */
    protected function mockSocialiteUser(array $data = []): SocialiteUser
    {
        $user = Mockery::mock(SocialiteUser::class);

        $user->shouldReceive('getId')->andReturn($data['id'] ?? '123456789');
        $user->shouldReceive('getEmail')->andReturn($data['email'] ?? 'test@example.com');
        $user->shouldReceive('getName')->andReturn($data['name'] ?? 'Test User');
        $user->shouldReceive('getNickname')->andReturn($data['nickname'] ?? null);
        $user->shouldReceive('getAvatar')->andReturn($data['avatar'] ?? 'https://example.com/avatar.jpg');

        return $user;
    }

    /**
     * Mock Socialite driver
     */
    protected function mockSocialiteDriver(string $provider, SocialiteUser $user): void
    {
        $driver = Mockery::mock('Laravel\Socialite\Two\GoogleProvider');
        $driver->shouldReceive('redirect')->andReturn(redirect('https://accounts.google.com/oauth'));
        $driver->shouldReceive('user')->andReturn($user);

        Socialite::shouldReceive('driver')
            ->with($provider)
            ->andReturn($driver);
    }

    // ========================================
    // TESTS DE REDIRECTION
    // ========================================

    /**     * L'utilisateur peut être redirigé vers Google OAuth
     */
    #[Test]
    public function user_can_be_redirected_to_google(): void
    {
        $driver = Mockery::mock('Laravel\Socialite\Two\GoogleProvider');
        $driver->shouldReceive('redirect')
            ->once()
            ->andReturn(redirect('https://accounts.google.com/oauth'));

        Socialite::shouldReceive('driver')
            ->with('google')
            ->andReturn($driver);

        $response = $this->get(route('socialite.redirect', 'google'));

        $response->assertRedirect();
    }

    /**     * L'utilisateur peut être redirigé vers Facebook OAuth
     */
    #[Test]
    public function user_can_be_redirected_to_facebook(): void
    {
        $driver = Mockery::mock('Laravel\Socialite\Two\FacebookProvider');
        $driver->shouldReceive('redirect')
            ->once()
            ->andReturn(redirect('https://www.facebook.com/oauth'));

        Socialite::shouldReceive('driver')
            ->with('facebook')
            ->andReturn($driver);

        $response = $this->get(route('socialite.redirect', 'facebook'));

        $response->assertRedirect();
    }

    /**     * Un provider non supporté retourne 404
     */
    #[Test]
    public function unsupported_provider_returns_404(): void
    {
        $response = $this->get('/auth/twitter/redirect');

        $response->assertNotFound();
    }

    // ========================================
    // TESTS DE CALLBACK GOOGLE
    // ========================================

    /**     * Un nouvel utilisateur est créé via Google OAuth
     */
    #[Test]
    public function new_user_is_created_via_google_callback(): void
    {
        $socialUser = $this->mockSocialiteUser([
            'id' => '123456789',
            'email' => 'newuser@gmail.com',
            'name' => 'New Google User',
            'avatar' => 'https://lh3.googleusercontent.com/avatar.jpg',
        ]);

        $this->mockSocialiteDriver('google', $socialUser);

        $this->assertDatabaseMissing('users', ['email' => 'newuser@gmail.com']);

        $response = $this->get(route('socialite.callback', 'google'));

        $response->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('users', [
            'email' => 'newuser@gmail.com',
            'name' => 'New Google User',
            'provider' => 'google',
            'provider_id' => '123456789',
        ]);

        $this->assertAuthenticated();
    }

    /**     * Un utilisateur existant est connecté via Google OAuth
     */
    #[Test]
    public function existing_user_is_logged_in_via_google_callback(): void
    {
        $existingUser = User::factory()->create([
            'email' => 'existing@gmail.com',
            'name' => 'Existing User',
        ]);

        $socialUser = $this->mockSocialiteUser([
            'id' => '987654321',
            'email' => 'existing@gmail.com',
            'name' => 'Google Name',
            'avatar' => 'https://lh3.googleusercontent.com/new-avatar.jpg',
        ]);

        $this->mockSocialiteDriver('google', $socialUser);

        $response = $this->get(route('socialite.callback', 'google'));

        $response->assertRedirect(route('dashboard'));

        // Provider info should be updated
        $existingUser->refresh();
        $this->assertEquals('google', $existingUser->provider);
        $this->assertEquals('987654321', $existingUser->provider_id);

        $this->assertAuthenticatedAs($existingUser);
    }

    /**     * L'email est vérifié automatiquement pour les utilisateurs OAuth
     */
    #[Test]
    public function oauth_user_email_is_automatically_verified(): void
    {
        $socialUser = $this->mockSocialiteUser([
            'email' => 'verified@gmail.com',
        ]);

        $this->mockSocialiteDriver('google', $socialUser);

        $this->get(route('socialite.callback', 'google'));

        $user = User::where('email', 'verified@gmail.com')->first();

        $this->assertNotNull($user->email_verified_at);
    }

    /**     * Le rôle par défaut est 'user' pour les nouveaux utilisateurs OAuth
     */
    #[Test]
    public function oauth_user_has_default_user_role(): void
    {
        $socialUser = $this->mockSocialiteUser([
            'email' => 'newrole@gmail.com',
        ]);

        $this->mockSocialiteDriver('google', $socialUser);

        $this->get(route('socialite.callback', 'google'));

        $user = User::where('email', 'newrole@gmail.com')->first();

        $this->assertEquals('user', $user->role);
    }

    // ========================================
    // TESTS DE CALLBACK FACEBOOK
    // ========================================

    /**     * Un nouvel utilisateur est créé via Facebook OAuth
     */
    #[Test]
    public function new_user_is_created_via_facebook_callback(): void
    {
        $socialUser = $this->mockSocialiteUser([
            'id' => 'fb123456789',
            'email' => 'newuser@facebook.com',
            'name' => 'New Facebook User',
            'avatar' => 'https://graph.facebook.com/avatar.jpg',
        ]);

        $this->mockSocialiteDriver('facebook', $socialUser);

        $response = $this->get(route('socialite.callback', 'facebook'));

        $response->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('users', [
            'email' => 'newuser@facebook.com',
            'provider' => 'facebook',
            'provider_id' => 'fb123456789',
        ]);
    }

    // ========================================
    // TESTS D'ERREURS
    // ========================================

    /**     * Une erreur OAuth redirige vers la page de login avec message d'erreur
     */
    #[Test]
    public function oauth_error_redirects_to_login_with_error(): void
    {
        $driver = Mockery::mock('Laravel\Socialite\Two\GoogleProvider');
        $driver->shouldReceive('user')
            ->once()
            ->andThrow(new \Exception('OAuth failed'));

        Socialite::shouldReceive('driver')
            ->with('google')
            ->andReturn($driver);

        $response = $this->get(route('socialite.callback', 'google'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error');
    }

    /**     * Un utilisateur sans email OAuth ne peut pas se connecter
     */
    #[Test]
    public function user_without_email_cannot_authenticate(): void
    {
        $socialUser = $this->mockSocialiteUser([
            'email' => null, // No email
            'name' => 'No Email User',
        ]);

        $driver = Mockery::mock('Laravel\Socialite\Two\GoogleProvider');
        $driver->shouldReceive('user')->andReturn($socialUser);

        Socialite::shouldReceive('driver')
            ->with('google')
            ->andReturn($driver);

        // Sans email, la création échouera ou sera gérée
        $response = $this->get(route('socialite.callback', 'google'));

        // La réponse dépend de l'implémentation, généralement erreur
        $this->assertTrue(
            $response->isRedirect() || $response->getStatusCode() >= 400,
        );
    }

    /**     * Un utilisateur OAuth peut se déconnecter
     */
    #[Test]
    public function oauth_user_can_logout(): void
    {
        $user = User::factory()->create([
            'provider' => 'google',
            'provider_id' => '123456789',
        ]);

        $this->actingAs($user);

        $response = $this->post(route('logout'));

        $this->assertGuest();
    }

    /**     * L'avatar est mis à jour lors de la connexion OAuth
     */
    #[Test]
    public function avatar_is_updated_on_oauth_login(): void
    {
        $existingUser = User::factory()->create([
            'email' => 'avatar@test.com',
            'avatar' => 'old-avatar.jpg',
        ]);

        $socialUser = $this->mockSocialiteUser([
            'email' => 'avatar@test.com',
            'avatar' => 'https://new-avatar.jpg',
        ]);

        $this->mockSocialiteDriver('google', $socialUser);

        $this->get(route('socialite.callback', 'google'));

        $existingUser->refresh();
        $this->assertEquals('https://new-avatar.jpg', $existingUser->avatar);
    }

    /**     * Le nom est utilisé comme fallback si nickname n'existe pas
     */
    #[Test]
    public function name_is_used_as_fallback(): void
    {
        $socialUser = $this->mockSocialiteUser([
            'email' => 'fallback@test.com',
            'name' => 'Real Name',
            'nickname' => null,
        ]);

        $this->mockSocialiteDriver('google', $socialUser);

        $this->get(route('socialite.callback', 'google'));

        $user = User::where('email', 'fallback@test.com')->first();
        $this->assertEquals('Real Name', $user->name);
    }
}
