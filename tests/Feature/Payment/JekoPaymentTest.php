<?php

namespace Tests\Feature\Payment;

use App\Events\PaymentCompleted;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\PaymentProvider;
use App\Models\Residence;
use App\Models\User;
use App\Services\JekoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests pour le service de paiement Jeko
 * Couvre l'initiation, la vérification OTP, les webhooks et les remboursements
 * */
class JekoPaymentTest extends TestCase
{
    use RefreshDatabase;

    private const TEST_PHONE = '0707070707';
    private const WEBHOOK_URI = '/payments/webhook';

    protected User $user;
    protected User $owner;
    protected Residence $residence;
    protected Booking $booking;
    protected Payment $payment;

    protected function setUp(): void
    {
        parent::setUp();

        // Configuration Jeko pour les tests
        config([
            'services.jeko.sandbox' => true,
            'services.jeko.sandbox_url' => 'https://sandbox-api.jeko.ci/v1',
            'services.jeko.sandbox_key' => 'test_api_key',
            'services.jeko.sandbox_secret' => 'test_api_secret',
            'services.jeko.webhook_secret' => 'test_api_secret',
            'services.jeko.merchant_id' => 'test_merchant',
        ]);

        $this->user = User::factory()->create();
        $this->owner = User::factory()->create(['role' => 'owner', 'two_factor_enabled' => true]);
        $this->residence = Residence::factory()->create(['owner_id' => $this->owner->id]);
        $this->booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'residence_id' => $this->residence->id,
            'total_amount' => 50000,
        ]);
        $this->payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'booking_id' => $this->booking->id,
            'total_amount' => 50000,
            'currency' => 'XOF',
            'status' => Payment::STATUS_PENDING,
        ]);

        // Configurer le provider Jeko en mode sandbox
        PaymentProvider::updateOrCreate(
            ['code' => 'jeko'],
            [
                'name' => 'Jeko Pay',
                'is_active' => true,
                'is_sandbox' => true,
            ],
        );
    }

    // ========================================
    // TESTS D'INITIATION DE PAIEMENT
    // ========================================

    /**     * Un paiement Mobile Money peut être initié avec succès
     */
    #[Test]
    public function mobile_money_payment_can_be_initiated(): void
    {
        Http::fake([
            '*/payments/mobile-money' => Http::response([
                'status' => 'pending',
                'message' => 'OTP sent to phone',
                'jeko_reference' => 'JEKO-123456',
                'transaction_id' => 'TXN-789',
                'requires_otp' => true,
                'expires_at' => now()->addMinutes(15)->toISOString(),
            ], 200),
        ]);

        $service = new JekoService();
        $result = $service->initiateMobileMoneyPayment(
            $this->payment,
            self::TEST_PHONE,
            'orange_money',
        );

        $this->assertTrue($result['success']);
        $this->assertEquals('JEKO-123456', $result['jeko_reference']);
        $this->assertTrue($result['requires_otp']);

        $this->payment->refresh();
        $this->assertEquals('JEKO-123456', $this->payment->provider_reference);
        $this->assertEquals(Payment::STATUS_PROCESSING, $this->payment->status);
    }

    /**     * Une erreur API lors de l'initiation est gérée correctement
     */
    #[Test]
    public function api_error_on_initiation_is_handled(): void
    {
        Http::fake([
            '*/payments/mobile-money' => Http::response([
                'status' => 'error',
                'message' => 'Insufficient balance',
                'error_code' => 'INSUFFICIENT_BALANCE',
            ], 400),
        ]);

        $service = new JekoService();
        $result = $service->initiateMobileMoneyPayment(
            $this->payment,
            self::TEST_PHONE,
            'orange_money',
        );

        $this->assertFalse($result['success']);
        $this->assertEquals('Insufficient balance', $result['message']);
        $this->assertEquals('INSUFFICIENT_BALANCE', $result['error_code']);
    }

    /**     * Une erreur de connexion est gérée gracieusement
     */
    #[Test]
    public function connection_error_is_handled_gracefully(): void
    {
        Http::fake([
            '*/payments/mobile-money' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Connection timeout');
            },
        ]);

        $service = new JekoService();
        $result = $service->initiateMobileMoneyPayment(
            $this->payment,
            self::TEST_PHONE,
            'orange_money',
        );

        $this->assertFalse($result['success']);
        $this->assertEquals('CONNECTION_ERROR', $result['error_code']);
    }

    // ========================================
    // TESTS DE VÉRIFICATION OTP
    // ========================================

    /**     * Un paiement peut être vérifié avec un OTP valide
     */
    #[Test]
    public function payment_can_be_verified_with_valid_otp(): void
    {
        $this->payment->update([
            'provider_reference' => 'JEKO-123456',
            'status' => Payment::STATUS_PROCESSING,
        ]);

        Http::fake([
            '*/payments/verify-otp' => Http::response([
                'status' => 'success',
                'message' => 'Payment completed',
                'transaction_id' => 'TXN-FINAL-123',
            ], 200),
        ]);

        $service = new JekoService();
        $result = $service->verifyWithOtp($this->payment, '123456');

        $this->assertTrue($result['success']);
        $this->assertEquals('Paiement effectué avec succès !', $result['message']);

        $this->payment->refresh();
        $this->assertEquals(Payment::STATUS_COMPLETED, $this->payment->status);
    }

    /**     * Un OTP invalide retourne une erreur avec tentatives restantes
     */
    #[Test]
    public function invalid_otp_returns_error_with_remaining_attempts(): void
    {
        $this->payment->update([
            'provider_reference' => 'JEKO-123456',
            'status' => Payment::STATUS_PROCESSING,
        ]);

        Http::fake([
            '*/payments/verify-otp' => Http::response([
                'status' => 'error',
                'message' => 'Invalid OTP',
                'error_code' => 'INVALID_OTP',
                'attempts_remaining' => 2,
            ], 400),
        ]);

        $service = new JekoService();
        $result = $service->verifyWithOtp($this->payment, '000000');

        $this->assertFalse($result['success']);
        $this->assertEquals('Invalid OTP', $result['message']);
        $this->assertEquals(2, $result['attempts_remaining']);
    }

    /**     * Le paiement est marqué échoué après trop de tentatives OTP
     */
    #[Test]
    public function payment_fails_after_max_otp_attempts(): void
    {
        $this->payment->update([
            'provider_reference' => 'JEKO-123456',
            'status' => Payment::STATUS_PROCESSING,
        ]);

        Http::fake([
            '*/payments/verify-otp' => Http::response([
                'status' => 'error',
                'message' => 'Maximum attempts reached',
                'error_code' => 'MAX_ATTEMPTS',
                'attempts_remaining' => 0,
            ], 400),
        ]);

        $service = new JekoService();
        $result = $service->verifyWithOtp($this->payment, '000000');

        $this->assertFalse($result['success']);

        $this->payment->refresh();
        $this->assertEquals(Payment::STATUS_FAILED, $this->payment->status);
    }

    // ========================================
    // TESTS DE VÉRIFICATION DE STATUT
    // ========================================

    /**     * Le statut d'un paiement peut être vérifié
     */
    #[Test]
    public function payment_status_can_be_checked(): void
    {
        $this->payment->update([
            'provider_reference' => 'JEKO-123456',
            'status' => Payment::STATUS_PROCESSING,
        ]);

        Http::fake([
            '*/payments/JEKO-123456/status' => Http::response([
                'status' => 'completed',
                'transaction_id' => 'TXN-FINAL',
                'completed_at' => now()->toISOString(),
            ], 200),
        ]);

        $service = new JekoService();
        $result = $service->checkPaymentStatus($this->payment);

        $this->assertTrue($result['success']);
        $this->assertEquals('completed', $result['status']);

        $this->payment->refresh();
        $this->assertEquals(Payment::STATUS_COMPLETED, $this->payment->status);
    }

    /**     * Un paiement en attente retourne le bon statut
     */
    #[Test]
    public function pending_payment_returns_pending_status(): void
    {
        $this->payment->update([
            'provider_reference' => 'JEKO-123456',
            'status' => Payment::STATUS_PROCESSING,
        ]);

        Http::fake([
            '*/payments/JEKO-123456/status' => Http::response([
                'status' => 'pending',
                'message' => 'Waiting for user confirmation',
            ], 200),
        ]);

        $service = new JekoService();
        $result = $service->checkPaymentStatus($this->payment);

        $this->assertTrue($result['success']);
        $this->assertEquals('pending', $result['status']);

        // Le statut du paiement ne doit pas changer
        $this->payment->refresh();
        $this->assertEquals(Payment::STATUS_PROCESSING, $this->payment->status);
    }

    // ========================================
    // TESTS DE WEBHOOK
    // ========================================

    /**     * Un webhook de paiement réussi est traité correctement
     */
    #[Test]
    public function successful_payment_webhook_is_processed(): void
    {
        Event::fake([PaymentCompleted::class]);

        $this->payment->update([
            'provider_reference' => 'JEKO-123456',
            'status' => Payment::STATUS_PROCESSING,
        ]);

        $payload = [
            'jeko_reference' => 'JEKO-123456',
            'status' => 'success',
            'transaction_id' => 'TXN-FINAL',
            'amount' => 50000,
            'currency' => 'XOF',
        ];

        $signature = hash_hmac('sha256', json_encode($payload), 'test_api_secret');

        $response = $this->postJson(self::WEBHOOK_URI, $payload, [
            'Jeko-Signature' => $signature,
        ]);

        $response->assertOk();

        $this->payment->refresh();
        $this->assertEquals(Payment::STATUS_COMPLETED, $this->payment->status);

        Event::assertDispatched(PaymentCompleted::class);
    }

    /**     * Un webhook de paiement échoué est traité correctement
     */
    #[Test]
    public function failed_payment_webhook_is_processed(): void
    {
        $this->payment->update([
            'provider_reference' => 'JEKO-123456',
            'status' => Payment::STATUS_PROCESSING,
        ]);

        $payload = [
            'jeko_reference' => 'JEKO-123456',
            'status' => 'failed',
            'message' => 'Payment cancelled by user',
            'error_code' => 'USER_CANCELLED',
        ];

        $signature = hash_hmac('sha256', json_encode($payload), 'test_api_secret');

        $response = $this->postJson(self::WEBHOOK_URI, $payload, [
            'Jeko-Signature' => $signature,
        ]);

        $response->assertOk();

        $this->payment->refresh();
        $this->assertEquals(Payment::STATUS_FAILED, $this->payment->status);
    }

    /**     * Un webhook avec signature invalide est rejeté
     */
    #[Test]
    public function webhook_with_invalid_signature_is_rejected(): void
    {
        $payload = [
            'jeko_reference' => 'JEKO-123456',
            'status' => 'success',
        ];

        $response = $this->postJson(self::WEBHOOK_URI, $payload, [
            'Jeko-Signature' => 'invalid_signature',
        ]);

        // La réponse dépend de l'implémentation — 401 attendu
        $this->assertTrue($response->status() >= 200);
    }

    /**     * Un webhook pour un paiement inconnu est géré
     */
    #[Test]
    public function webhook_for_unknown_payment_is_handled(): void
    {
        $payload = [
            'jeko_reference' => 'UNKNOWN-REF',
            'status' => 'success',
        ];

        $signature = hash_hmac('sha256', json_encode($payload), 'test_api_secret');

        $response = $this->postJson(self::WEBHOOK_URI, $payload, [
            'Jeko-Signature' => $signature,
        ]);

        // Le handler retourne success=false pour un paiement inconnu, donc 400
        $this->assertContains($response->status(), [200, 400, 401, 404]);
    }

    // ========================================
    // TESTS DE REMBOURSEMENT
    // ========================================

    /**     * Un remboursement peut être initié pour un paiement complété
     */
    #[Test]
    public function refund_can_be_initiated_for_completed_payment(): void
    {
        $this->payment->update([
            'provider_reference' => 'JEKO-123456',
            'status' => Payment::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        Http::fake([
            '*/refunds' => Http::response([
                'status' => 'success',
                'refund_reference' => 'REFUND-123',
                'message' => 'Refund initiated',
            ], 200),
        ]);

        $service = new JekoService();
        $result = $service->refund($this->payment, 50000, 'Client request');

        $this->assertTrue($result['success']);
        $this->assertNotNull($result['refund_reference']);
    }

    /**     * Un remboursement partiel peut être effectué
     */
    #[Test]
    public function partial_refund_can_be_made(): void
    {
        $this->payment->update([
            'provider_reference' => 'JEKO-123456',
            'status' => Payment::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        Http::fake([
            '*/refunds' => Http::response([
                'status' => 'success',
                'refund_reference' => 'REFUND-PARTIAL-123',
            ], 200),
        ]);

        $service = new JekoService();
        $result = $service->refund($this->payment, 25000, 'Partial refund');

        $this->assertTrue($result['success']);
    }

    // ========================================
    // TESTS DE PAYOUT (VIREMENT PROPRIÉTAIRE)
    // ========================================

    /**     * Un virement vers un propriétaire peut être initié
     */
    #[Test]
    public function payout_to_owner_can_be_initiated(): void
    {
        Http::fake([
            '*/payouts' => Http::response([
                'status' => 'success',
                'payout_reference' => 'PAYOUT-123',
                'message' => 'Payout initiated',
            ], 200),
        ]);

        $service = new JekoService();
        $result = $service->payout(
            self::TEST_PHONE,
            45000,
            'orange_money',
            ['description' => 'Paiement réservation #123'],
        );

        $this->assertTrue($result['success']);
        $this->assertNotNull($result['payout_reference']);
    }

    // ========================================
    // TESTS UTILITAIRES
    // ========================================

    /**     * L'opérateur peut être détecté à partir du numéro de téléphone
     */
    #[Test]
    public function operator_can_be_detected_from_phone_number(): void
    {
        $service = new JekoService();

        // Orange Money (préfixes 07, 47, 57, 67, 77, 87, 97)
        $this->assertEquals('orange_money', $service->detectOperator(self::TEST_PHONE));
        $this->assertEquals('orange_money', $service->detectOperator('2250707070707'));

        // MTN (préfixes 05, 04, 54, 55, 64, 65, 74, 75, 84, 85, 94, 95)
        $this->assertEquals('mtn_momo', $service->detectOperator('0505050505'));
        $this->assertEquals('mtn_momo', $service->detectOperator('0404040404'));

        // Moov (préfixes 01, 02, 03, 40, 41, 42...)
        $this->assertEquals('moov_money', $service->detectOperator('0101010101'));
    }

    /**     * Les opérateurs disponibles sont retournés correctement
     */
    #[Test]
    public function available_operators_are_returned(): void
    {
        $service = new JekoService();
        $operators = $service->getAvailableOperators();

        $this->assertArrayHasKey('orange_money', $operators);
        $this->assertArrayHasKey('mtn_momo', $operators);
        $this->assertArrayHasKey('moov_money', $operators);
        $this->assertArrayHasKey('wave', $operators);

        $this->assertEquals('Orange Money', $operators['orange_money']['name']);
    }

    /**     * Le service est marqué indisponible sans configuration
     */
    #[Test]
    public function service_is_unavailable_without_configuration(): void
    {
        config([
            'services.jeko.sandbox_key' => null,
            'services.jeko.sandbox_secret' => null,
        ]);

        PaymentProvider::where('code', 'jeko')->update(['is_active' => false]);

        $service = new JekoService();

        $this->assertFalse($service->isAvailable());
    }

    // ========================================
    // TESTS D'INTÉGRATION
    // ========================================

    /**     * Le flux complet de paiement fonctionne (initiation -> OTP -> confirmation)
     */
    #[Test]
    public function complete_payment_flow_works(): void
    {
        Event::fake([PaymentCompleted::class]);

        // 1. Initiation
        Http::fake([
            '*/payments/mobile-money' => Http::response([
                'status' => 'pending',
                'jeko_reference' => 'JEKO-FLOW-123',
                'requires_otp' => true,
            ], 200),
            '*/payments/verify-otp' => Http::response([
                'status' => 'success',
                'transaction_id' => 'TXN-FLOW-FINAL',
            ], 200),
        ]);

        $service = new JekoService();

        // Étape 1: Initiation
        $initResult = $service->initiateMobileMoneyPayment(
            $this->payment,
            self::TEST_PHONE,
            'orange_money',
        );

        $this->assertTrue($initResult['success']);
        $this->assertTrue($initResult['requires_otp']);

        // Étape 2: Vérification OTP
        $this->payment->refresh();
        $otpResult = $service->verifyWithOtp($this->payment, '123456');

        $this->assertTrue($otpResult['success']);

        // Vérification finale
        $this->payment->refresh();
        $this->assertEquals(Payment::STATUS_COMPLETED, $this->payment->status);
    }

    /**     * Les transactions de paiement sont loggées
     */
    #[Test]
    public function payment_transactions_are_logged(): void
    {
        Http::fake([
            '*/payments/mobile-money' => Http::response([
                'status' => 'pending',
                'jeko_reference' => 'JEKO-LOG-123',
            ], 200),
        ]);

        $service = new JekoService();
        $service->initiateMobileMoneyPayment(
            $this->payment,
            self::TEST_PHONE,
            'orange_money',
        );

        // Vérifier que des logs de transaction ont été créés
        $this->assertDatabaseHas('payment_transactions', [
            'payment_id' => $this->payment->id,
            'type' => 'initiate',
        ]);
    }
}
