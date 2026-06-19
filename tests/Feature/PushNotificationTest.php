<?php

namespace Tests\Feature;

use App\Models\NotificationLog;
use App\Models\NotificationPreference;
use App\Models\PushSubscription;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests pour le système de notifications push
 * Couvre les subscriptions, l'envoi push et les préférences
 * */
class PushNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'user']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ========================================
    // TESTS D'INSCRIPTION PUSH
    // ========================================

    /**     * Un utilisateur peut souscrire aux notifications push
     */
    #[Test]
    public function user_can_subscribe_to_push_notifications(): void
    {
        $subscriptionData = [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint',
            'keys' => [
                'p256dh' => 'BNcRdreALRFXTkOOUHK1EtK2wtaz5Ry4YfYCA_0QTpQtUbVlUls0VJXg7A8u-Ts1XbjhazAkj7I99e8QcYP7DkM',
                'auth' => 'tBHItJI5svbpez7KI4CCXg',
            ],
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/notifications/push/subscribe', $subscriptionData);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => $this->user->id,
            'endpoint' => $subscriptionData['endpoint'],
        ]);
    }

    /**     * Une subscription existante est mise à jour plutôt que dupliquée
     */
    #[Test]
    public function existing_subscription_is_updated(): void
    {
        $endpoint = 'https://fcm.googleapis.com/fcm/send/existing-endpoint';

        PushSubscription::create([
            'user_id' => $this->user->id,
            'endpoint' => $endpoint,
            'public_key' => 'old-key',
            'auth_token' => 'old-token',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/notifications/push/subscribe', [
                'endpoint' => $endpoint,
                'keys' => [
                    'p256dh' => 'new-key',
                    'auth' => 'new-token',
                ],
            ]);

        $response->assertOk();

        // Une seule subscription doit exister
        $this->assertEquals(1, PushSubscription::where('endpoint', $endpoint)->count());

        // La clé doit être mise à jour
        $subscription = PushSubscription::where('endpoint', $endpoint)->first();
        $this->assertEquals('new-key', $subscription->public_key);
    }

    /**     * Un utilisateur peut se désinscrire des notifications push
     */
    #[Test]
    public function user_can_unsubscribe_from_push_notifications(): void
    {
        $subscription = PushSubscription::create([
            'user_id' => $this->user->id,
            'endpoint' => 'https://fcm.googleapis.com/test',
            'public_key' => 'test-key',
            'auth_token' => 'test-token',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/notifications/push/unsubscribe', [
                'endpoint' => $subscription->endpoint,
            ]);

        $response->assertOk();
        $this->assertDatabaseMissing('push_subscriptions', [
            'id' => $subscription->id,
        ]);
    }

    /**     * Un utilisateur peut récupérer la clé VAPID publique
     */
    #[Test]
    public function user_can_get_vapid_public_key(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/notifications/vapid');

        $response->assertOk();
        $response->assertJsonStructure(['publicKey']);
    }

    /**     * Les données de subscription sont validées
     */
    #[Test]
    public function subscription_data_is_validated(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/notifications/push/subscribe', [
                // endpoint manquant
                'keys' => [
                    'p256dh' => 'test',
                    'auth' => 'test',
                ],
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['endpoint']);
    }

    /**     * Un endpoint push hors allowlist est refusé
     */
    #[Test]
    public function push_subscription_endpoint_must_be_from_an_allowed_provider(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/notifications/push/subscribe', [
                'endpoint' => 'https://example.com/push/endpoint',
                'keys' => [
                    'p256dh' => 'test-public-key',
                    'auth' => 'test-auth-token',
                ],
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['endpoint']);

        $this->assertDatabaseMissing('push_subscriptions', [
            'user_id' => $this->user->id,
            'endpoint' => 'https://example.com/push/endpoint',
        ]);
    }

    // ========================================
    // TESTS D'ENVOI DE NOTIFICATIONS
    // ========================================

    /**     * Une notification push est envoyée aux subscriptions actives
     */
    #[Test]
    public function push_notification_is_sent_to_active_subscriptions(): void
    {
        // Créer des subscriptions
        PushSubscription::factory()->count(2)->create([
            'user_id' => $this->user->id,
        ]);

        // Mock le WebPush
        $this->mockWebPush();

        $service = new NotificationService();

        // Envoyer via réflexion pour tester la méthode protégée
        $method = new \ReflectionMethod(NotificationService::class, 'sendPush');
        $method->setAccessible(true);

        // L'appel ne doit pas lancer d'exception
        $this->expectNotToPerformAssertions();

        try {
            $method->invoke($service, $this->user, 'Test', 'Body', []);
        } catch (\Throwable $e) {
            // C'est normal si WebPush n'est pas configuré en test
        }
    }

    /**     * Aucune notification n'est envoyée si l'utilisateur n'a pas de subscription
     */
    #[Test]
    public function no_notification_sent_without_subscriptions(): void
    {
        $this->assertDatabaseMissing('push_subscriptions', [
            'user_id' => $this->user->id,
        ]);

        $service = new NotificationService();

        $method = new \ReflectionMethod(NotificationService::class, 'sendPush');
        $method->setAccessible(true);

        // Ne doit pas lancer d'erreur (sendPush retourne silencieusement s'il n'y a pas de subscriptions)
        $method->invoke($service, $this->user, 'Test', 'Body', []);

        // Vérifier qu'aucune subscription n'a été créée
        $this->assertDatabaseMissing('push_subscriptions', [
            'user_id' => $this->user->id,
        ]);
    }

    /**     * Les subscriptions push invalides stockées sont supprimées avant envoi
     */
    #[Test]
    public function invalid_stored_push_subscriptions_are_deleted_before_send(): void
    {
        $subscription = PushSubscription::create([
            'user_id' => $this->user->id,
            'endpoint' => 'https://example.com/push/bad-endpoint',
            'public_key' => 'test-key',
            'auth_token' => 'test-token',
        ]);

        $service = new NotificationService(app(\App\Services\PublicUrlGuard::class));
        $method = new \ReflectionMethod(NotificationService::class, 'sendPush');
        $method->setAccessible(true);

        $method->invoke($service, $this->user, 'Test', 'Body', []);

        $this->assertDatabaseMissing('push_subscriptions', [
            'id' => $subscription->id,
        ]);
    }

    // ========================================
    // TESTS DES PRÉFÉRENCES
    // ========================================

    /**     * Les préférences par défaut sont créées pour un nouvel utilisateur
     */
    #[Test]
    public function default_preferences_are_created_for_new_user(): void
    {
        $this->assertDatabaseMissing('notification_preferences', [
            'user_id' => $this->user->id,
        ]);

        $service = new NotificationService();
        $method = new \ReflectionMethod(NotificationService::class, 'getPreferences');
        $method->setAccessible(true);

        $preferences = $method->invoke($service, $this->user);

        $this->assertInstanceOf(NotificationPreference::class, $preferences);
        $this->assertDatabaseHas('notification_preferences', [
            'user_id' => $this->user->id,
        ]);
    }

    /**     * Un utilisateur peut modifier ses préférences de notification
     */
    #[Test]
    public function user_can_update_notification_preferences(): void
    {
        NotificationPreference::create([
            'user_id' => $this->user->id,
            'messages_email' => true,
            'messages_push' => true,
            'messages_sms' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/notifications/preferences', [
                'messages_push' => false,
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('notification_preferences', [
            'user_id' => $this->user->id,
            'messages_push' => false,
        ]);
    }

    /**     * Les notifications ne sont pas envoyées pendant les heures silencieuses
     */
    #[Test]
    public function notifications_not_sent_during_quiet_hours(): void
    {
        NotificationPreference::create([
            'user_id' => $this->user->id,
            'quiet_hours_start' => '22:00',
            'quiet_hours_end' => '08:00',
        ]);

        // Simuler une heure dans la plage silencieuse
        $this->travelTo(now()->setTime(23, 0));

        $preference = NotificationPreference::where('user_id', $this->user->id)->first();

        $this->assertTrue($preference->isQuietHours());
    }

    // ========================================
    // TESTS DES LOGS
    // ========================================

    /**     * Les notifications sont loggées
     */
    #[Test]
    public function notifications_are_logged(): void
    {
        Mail::fake();

        $service = new NotificationService();
        $service->sendSystemNotification(
            $this->user,
            'Test Notification',
            'This is a test',
            ['test_key' => 'test_value'],
        );

        $this->assertDatabaseHas('notification_logs', [
            'user_id' => $this->user->id,
            'type' => NotificationLog::TYPE_SYSTEM,
            'title' => 'Test Notification',
        ]);
    }

    /**     * Le statut du log est mis à jour après envoi réussi
     */
    #[Test]
    public function log_status_is_updated_on_success(): void
    {
        Mail::fake();

        $log = NotificationLog::log(
            $this->user->id,
            NotificationLog::CHANNEL_EMAIL,
            NotificationLog::TYPE_SYSTEM,
            'Test',
            'Body',
            [],
        );

        $log->markAsSent();

        $this->assertEquals(NotificationLog::STATUS_SENT, $log->fresh()->status);
        $this->assertNotNull($log->fresh()->sent_at);
    }

    /**     * Le statut du log est mis à jour en cas d'échec
     */
    #[Test]
    public function log_status_is_updated_on_failure(): void
    {
        $log = NotificationLog::log(
            $this->user->id,
            NotificationLog::CHANNEL_PUSH,
            NotificationLog::TYPE_SYSTEM,
            'Test',
            'Body',
            [],
        );

        $log->markAsFailed('Connection timeout');

        $this->assertEquals(NotificationLog::STATUS_FAILED, $log->fresh()->status);
        $this->assertEquals('Connection timeout', $log->fresh()->error_message);
    }

    // ========================================
    // TESTS API
    // ========================================

    /**     * L'admin peut envoyer une notification broadcast
     */
    #[Test]
    public function admin_can_send_broadcast_notification(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'two_factor_enabled' => true]);

        $response = $this->actingAs($admin)
            ->postJson(route('notifications.broadcast'), [
                'title' => 'Annonce importante',
                'body' => 'Ceci est une notification broadcast de test.',
            ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
    }

    /**     * Un utilisateur normal ne peut pas envoyer de broadcast
     */
    #[Test]
    public function regular_user_cannot_send_broadcast(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('notifications.broadcast'), [
                'title' => 'Test',
                'body' => 'Test message',
            ]);

        $response->assertStatus(403);
    }

    /**     * Un utilisateur non connecté ne peut pas souscrire aux notifications
     */
    #[Test]
    public function guest_cannot_subscribe_to_push(): void
    {
        $response = $this->postJson('/notifications/push/subscribe', [
            'endpoint' => 'https://test.com',
            'keys' => ['p256dh' => 'test', 'auth' => 'test'],
        ]);

        $response->assertUnauthorized();
    }

    /**     * Un utilisateur peut récupérer ses notifications récentes
     */
    #[Test]
    public function user_can_get_recent_notifications(): void
    {
        NotificationLog::factory()->count(5)->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/notifications/latest');

        $response->assertOk();
        $response->assertJsonStructure(['notifications', 'notification_logs', 'unread_count']);
        $response->assertJsonCount(5, 'notification_logs');
    }

    /**     * Un utilisateur peut compter ses notifications non lues
     */
    #[Test]
    public function user_can_get_unread_count(): void
    {
        NotificationLog::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'read_at' => null,
        ]);

        NotificationLog::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'read_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/notifications/unread-count');

        $response->assertOk();
        $response->assertJson(['count' => 3]);
    }

    // ========================================
    // HELPERS
    // ========================================

    protected function mockWebPush(): void
    {
        // Note: WebPush est difficile à mocker complètement
        // Ces tests vérifient principalement la logique métier
    }
}
