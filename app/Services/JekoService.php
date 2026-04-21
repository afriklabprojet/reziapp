<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\PaymentProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Service d'intégration Jeko Pay API
 * Passerelle de paiement africaine pour Mobile Money et Cartes
 * Documentation: https://api.jeko.africa
 */
class JekoService
{
    protected ?PaymentProvider $provider;
    protected string $baseUrl;
    protected ?string $apiKey;
    protected ?string $apiKeyId;
    protected ?string $storeId;
    protected ?string $webhookSecret;
    protected string $currency;
    protected bool $isSandbox;

    public function __construct()
    {
        $this->provider = PaymentProvider::where('code', 'jeko')->first();
        $this->loadConfiguration();
    }

    /**
     * Charger la configuration API
     */
    protected function loadConfiguration(): void
    {
        $this->isSandbox = (bool) config('services.jeko.sandbox', false);

        if ($this->isSandbox) {
            $this->baseUrl   = config('services.jeko.sandbox_url', 'https://sandbox-api.jeko.ci/v1');
            $this->apiKey    = config('services.jeko.sandbox_key');
            $this->storeId   = config('services.jeko.merchant_id');
        } else {
            $this->baseUrl   = config('services.jeko.base_url', 'https://api.jeko.africa');
            $this->apiKey    = config('services.jeko.api_key');
            $this->storeId   = config('services.jeko.store_id');
        }

        $this->apiKeyId      = config('services.jeko.api_key_id');
        $this->webhookSecret = config('services.jeko.webhook_secret');
        $this->currency      = config('services.jeko.currency', 'XOF');
    }

    /**
     * Initier un paiement Mobile Money
     */
    public function initiateMobileMoneyPayment(Payment $payment, string $phoneNumber, string $operator): array
    {
        // Guard: validate payment amount
        if ($payment->total_amount <= 0) {
            Log::channel('payments')->error('initiateMobileMoneyPayment: Invalid amount', [
                'payment_id' => $payment->id,
                'amount' => $payment->total_amount,
            ]);
            return [
                'success' => false,
                'message' => 'Montant invalide.',
                'error_code' => 'INVALID_AMOUNT',
            ];
        }

        // Guard: validate API configuration
        if (! $this->apiKey || ! $this->storeId) {
            Log::channel('critical')->error('initiateMobileMoneyPayment: API not configured');
            return [
                'success' => false,
                'message' => 'Service de paiement non configuré.',
                'error_code' => 'NOT_CONFIGURED',
            ];
        }

        $callbackBaseUrl = config('services.jeko.callback_base_url') ?: config('app.url');

        $payload = [
            'store_id' => $this->storeId,
            'transaction_id' => $payment->reference,
            'amount' => (int) $payment->total_amount,
            'currency' => $this->currency,
            'phone_number' => $this->formatPhoneNumber($phoneNumber),
            'operator' => $this->mapOperator($operator),
            'description' => $this->generateDescription($payment),
            'callback_url' => $callbackBaseUrl ? rtrim($callbackBaseUrl, '/').'/payments/webhook' : route('payments.webhook'),
            'return_url' => $payment->getReturnUrl(),
            'metadata' => [
                'payment_id' => $payment->id,
                'booking_id' => $payment->booking_id,
                'user_id' => $payment->user_id,
            ],
        ];

        // Logger la requête
        $payment->logTransaction('initiate', 'pending', ['request' => $payload]);

        try {
            $response = Http::withHeaders($this->getHeaders())
                ->timeout(30)
                ->connectTimeout(10)
                ->retry(2, 1000, throw: false)
                ->post("{$this->baseUrl}/payments/mobile-money", $payload);

            $data = $response->json();

            if ($response->successful() && ($data['status'] ?? '') === 'pending') {
                $payment->update([
                    'provider_reference' => $data['jeko_reference'] ?? null,
                    'provider_transaction_id' => $data['transaction_id'] ?? null,
                    'phone_number' => $phoneNumber,
                    'status' => Payment::STATUS_PROCESSING,
                    'initiated_at' => now(),
                    'expires_at' => now()->addMinutes(15),
                    'provider_response' => $data,
                ]);

                $payment->logTransaction('otp_sent', 'success', $data);

                return [
                    'success' => true,
                    'message' => $data['message'] ?? 'Un code de confirmation a été envoyé sur votre téléphone',
                    'requires_otp' => $data['requires_otp'] ?? true,
                    'jeko_reference' => $data['jeko_reference'] ?? null,
                    'expires_at' => $data['expires_at'] ?? now()->addMinutes(15)->toISOString(),
                ];
            }

            $errorMessage = $data['message'] ?? $data['error'] ?? 'Erreur lors de l\'initiation du paiement';
            $payment->markAsFailed($errorMessage, $data['error_code'] ?? null);

            return [
                'success' => false,
                'message' => $errorMessage,
                'error_code' => $data['error_code'] ?? 'INIT_FAILED',
            ];

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::channel('critical')->error('Jeko API Connection Error', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            // Don't mark as failed on connection error — it's retryable
            return [
                'success' => false,
                'message' => 'Service temporairement indisponible. Veuillez réessayer.',
                'error_code' => 'CONNECTION_ERROR',
            ];
        } catch (\Exception $e) {
            Log::channel('critical')->error('Jeko API Error', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            $payment->markAsFailed('Erreur de connexion au service de paiement');

            return [
                'success' => false,
                'message' => 'Service temporairement indisponible. Veuillez réessayer.',
                'error_code' => 'CONNECTION_ERROR',
            ];
        }
    }

    /**
     * Vérifier un paiement avec OTP
     */
    public function verifyWithOtp(Payment $payment, string $otp): array
    {
        $payload = [
            'store_id' => $this->storeId,
            'jeko_reference' => $payment->provider_reference,
            'otp' => $otp,
        ];

        $payment->logTransaction('otp_verified', 'pending', ['otp_provided' => true]);

        try {
            $response = Http::withHeaders($this->getHeaders())
                ->timeout(30)
                ->connectTimeout(10)
                ->post("{$this->baseUrl}/payments/verify-otp", $payload);

            $data = $response->json();

            if ($response->successful() && in_array($data['status'] ?? '', ['success', 'completed'])) {
                $payment->markAsCompleted($data);

                return [
                    'success' => true,
                    'message' => 'Paiement effectué avec succès !',
                    'transaction_id' => $data['transaction_id'] ?? $payment->provider_transaction_id,
                ];
            }

            if (($data['status'] ?? '') === 'pending') {
                return [
                    'success' => false,
                    'pending' => true,
                    'message' => 'Paiement en cours de traitement. Vous recevrez une notification.',
                ];
            }

            $errorMessage = $data['message'] ?? 'Code de vérification incorrect';

            if (($data['attempts_remaining'] ?? 0) <= 0) {
                $payment->markAsFailed('Nombre maximum de tentatives atteint');
            }

            return [
                'success' => false,
                'message' => $errorMessage,
                'attempts_remaining' => $data['attempts_remaining'] ?? null,
                'error_code' => $data['error_code'] ?? 'INVALID_OTP',
            ];

        } catch (\Exception $e) {
            Log::channel('critical')->error('Jeko OTP Verification Error', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la vérification. Veuillez réessayer.',
                'error_code' => 'VERIFICATION_ERROR',
            ];
        }
    }

    /**
     * Vérifier le statut d'un paiement
     */
    public function checkPaymentStatus(Payment $payment): array
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->timeout(15)
                ->connectTimeout(5)
                ->get("{$this->baseUrl}/payments/{$payment->provider_reference}/status");

            $data = $response->json();

            if ($response->successful()) {
                $status = $data['status'] ?? 'unknown';

                switch ($status) {
                    case 'success':
                    case 'completed':
                        if (!$payment->isCompleted()) {
                            $payment->markAsCompleted($data);
                        }

                        return ['success' => true, 'status' => 'completed', 'data' => $data];

                    case 'failed':
                    case 'cancelled':
                        if (!$payment->isFailed()) {
                            $payment->markAsFailed($data['message'] ?? 'Paiement échoué');
                        }

                        return ['success' => false, 'status' => 'failed', 'data' => $data];

                    case 'pending':
                    case 'processing':
                        return ['success' => true, 'status' => 'pending', 'data' => $data];

                    default:
                        return ['success' => false, 'status' => 'unknown', 'data' => $data];
                }
            }

            return [
                'success' => false,
                'status' => 'error',
                'message' => $data['message'] ?? 'Impossible de vérifier le statut',
            ];

        } catch (\Exception $e) {
            Log::channel('payments')->error('Jeko Status Check Error', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'status' => 'error',
                'message' => 'Erreur de connexion',
            ];
        }
    }

    /**
     * Traiter un webhook Jeko
     */
    public function handleWebhook(array $payload): array
    {
        // Vérifier la signature
        if (!$this->verifyWebhookSignature($payload)) {
            Log::warning('Invalid Jeko webhook signature', $payload);

            return ['success' => false, 'message' => 'Invalid signature'];
        }

        $jekoReference = $payload['jeko_reference'] ?? null;
        $transactionId = $payload['merchant_transaction_id'] ?? $payload['metadata']['payment_id'] ?? null;

        $payment = Payment::where('provider_reference', $jekoReference)
            ->orWhere('reference', $transactionId)
            ->first();

        if (!$payment) {
            Log::warning('Jeko webhook: Payment not found', $payload);

            return ['success' => false, 'message' => 'Payment not found'];
        }

        $payment->logTransaction('webhook', 'success', $payload);

        $status = $payload['status'] ?? 'unknown';

        switch ($status) {
            case 'success':
            case 'completed':
                $payment->markAsCompleted($payload);

                // Déclencher les événements post-paiement
                event(new \App\Events\PaymentCompleted($payment));

                return ['success' => true, 'message' => 'Payment completed'];

            case 'failed':
            case 'cancelled':
                $payment->markAsFailed($payload['message'] ?? 'Paiement échoué', $payload['error_code'] ?? null);

                return ['success' => true, 'message' => 'Payment failed recorded'];

            default:
                return ['success' => true, 'message' => 'Status recorded'];
        }
    }

    /**
     * Effectuer un remboursement
     */
    public function refund(Payment $payment, float $amount, string $reason = ''): array
    {
        if (!$payment->canBeRefunded()) {
            return [
                'success' => false,
                'message' => 'Ce paiement ne peut pas être remboursé',
            ];
        }

        $payload = [
            'store_id' => $this->storeId,
            'original_transaction_id' => $payment->provider_reference,
            'amount' => (int) $amount,
            'currency' => $this->currency,
            'reason' => $reason,
            'refund_reference' => 'REF-'.$payment->reference.'-'.Str::random(6),
        ];

        try {
            $response = Http::withHeaders($this->getHeaders())
                ->timeout(30)
                ->post("{$this->baseUrl}/refunds", $payload);

            $data = $response->json();

            if ($response->successful() && ($data['status'] ?? '') === 'success') {
                $payment->logTransaction('refund', 'success', $data);

                return [
                    'success' => true,
                    'message' => 'Remboursement initié avec succès',
                    'refund_reference' => $data['refund_reference'] ?? null,
                ];
            }

            return [
                'success' => false,
                'message' => $data['message'] ?? 'Erreur lors du remboursement',
            ];

        } catch (\Exception $e) {
            Log::error('Jeko Refund Error', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors du traitement du remboursement',
            ];
        }
    }

    /**
     * Effectuer un virement vers un propriétaire (payout)
     */
    public function payout(string $phoneNumber, float $amount, string $operator, array $metadata = []): array
    {
        $payload = [
            'store_id' => $this->storeId,
            'phone_number' => $this->formatPhoneNumber($phoneNumber),
            'operator' => $this->mapOperator($operator),
            'amount' => (int) $amount,
            'currency' => $this->currency,
            'payout_reference' => 'PAYOUT-'.Str::uuid(),
            'description' => $metadata['description'] ?? 'Virement REZI',
            'metadata' => $metadata,
        ];

        try {
            $response = Http::withHeaders($this->getHeaders())
                ->timeout(30)
                ->post("{$this->baseUrl}/payouts", $payload);

            $data = $response->json();

            if ($response->successful() && in_array($data['status'] ?? '', ['success', 'pending'])) {
                return [
                    'success' => true,
                    'message' => 'Virement initié avec succès',
                    'payout_reference' => $data['payout_reference'] ?? $payload['payout_reference'],
                    'status' => $data['status'],
                ];
            }

            return [
                'success' => false,
                'message' => $data['message'] ?? 'Erreur lors du virement',
            ];

        } catch (\Exception $e) {
            Log::error('Jeko Payout Error', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Erreur lors du traitement du virement',
            ];
        }
    }

    /**
     * Obtenir les opérateurs disponibles
     */
    public function getAvailableOperators(): array
    {
        return [
            'orange_money' => [
                'code' => 'orange_money',
                'name' => 'Orange Money',
                'logo' => '/images/payment/orange-money.png',
                'prefix' => ['07', '47', '57', '67', '77', '87', '97'],
                'country' => 'CI',
            ],
            'mtn_momo' => [
                'code' => 'mtn_momo',
                'name' => 'MTN Mobile Money',
                'logo' => '/images/payment/mtn-momo.png',
                'prefix' => ['05', '04', '54', '55', '64', '65', '74', '75', '84', '85', '94', '95'],
                'country' => 'CI',
            ],
            'moov_money' => [
                'code' => 'moov_money',
                'name' => 'Moov Money',
                'logo' => '/images/payment/moov-money.png',
                'prefix' => ['01', '02', '03', '40', '41', '42', '43', '51', '52', '53', '61', '62', '63', '71', '72', '73'],
                'country' => 'CI',
            ],
            'wave' => [
                'code' => 'wave',
                'name' => 'Wave',
                'logo' => '/images/payment/wave.png',
                'prefix' => ['01', '05', '07'],
                'country' => 'CI',
            ],
        ];
    }

    /**
     * Détecter l'opérateur à partir du numéro de téléphone
     */
    public function detectOperator(string $phoneNumber): ?string
    {
        $phone = preg_replace('/[^0-9]/', '', $phoneNumber);

        // Retirer le code pays
        if (str_starts_with($phone, '225')) {
            $phone = substr($phone, 3);
        }

        $prefix = substr($phone, 0, 2);

        foreach ($this->getAvailableOperators() as $code => $operator) {
            if (in_array($prefix, $operator['prefix'])) {
                return $code;
            }
        }

        return null;
    }

    // ===== HELPERS =====

    /**
     * Obtenir les headers pour l'API
     */
    protected function getHeaders(): array
    {
        return [
            'Authorization' => 'Bearer '.$this->apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-Api-Key-Id' => $this->apiKeyId,
            'X-Store-Id' => $this->storeId,
        ];
    }

    /**
     * Formater le numéro de téléphone
     */
    protected function formatPhoneNumber(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Ajouter le code pays si nécessaire
        if (!str_starts_with($phone, '225') && strlen($phone) <= 10) {
            $phone = '225'.$phone;
        }

        return $phone;
    }

    /**
     * Mapper le code opérateur pour Jeko
     */
    protected function mapOperator(string $operator): string
    {
        return match ($operator) {
            'orange_money', 'orange' => 'ORANGE_CI',
            'mtn_momo', 'mtn' => 'MTN_CI',
            'moov_money', 'moov' => 'MOOV_CI',
            'wave' => 'WAVE_CI',
            default => strtoupper($operator),
        };
    }

    /**
     * Générer une description pour le paiement
     */
    protected function generateDescription(Payment $payment): string
    {
        if ($payment->booking) {
            return "Réservation REZI #{$payment->booking->reference}";
        }

        return "Paiement REZI #{$payment->reference}";
    }

    /**
     * Vérifier la signature du webhook
     */
    protected function verifyWebhookSignature(array $payload): bool
    {
        $signature = request()->header('X-Jeko-Signature');

        if (!$signature) {
            return false;
        }

        $expectedSignature = hash_hmac(
            'sha256',
            json_encode($payload),
            $this->webhookSecret,
        );

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Vérifier si le service est disponible
     */
    public function isAvailable(): bool
    {
        return $this->provider?->is_active && $this->apiKey && $this->storeId;
    }
}
