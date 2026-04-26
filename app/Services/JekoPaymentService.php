<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payout;
use App\Models\SponsoredListing;
use App\Models\User;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class JekoPaymentService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $apiKeyId;
    protected string $storeId;
    protected string $currency;
    protected string $webhookSecret;
    protected string $callbackBaseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.jeko.base_url') ?? 'https://api.jeko.africa';
        $this->apiKey = config('services.jeko.api_key') ?? '';
        $this->apiKeyId = config('services.jeko.api_key_id') ?? '';
        $this->storeId = config('services.jeko.store_id') ?? '';
        $this->currency = config('services.jeko.currency') ?? 'XOF';
        $this->webhookSecret = config('services.jeko.webhook_secret') ?? '';
        $this->callbackBaseUrl = config('services.jeko.callback_base_url') ?? config('app.url') ?? '';
    }

    /**
     * Check if Jeko payment is enabled and properly configured.
     */
    public function isEnabled(): bool
    {
        return config('services.jeko.enabled', false)
            && $this->apiKey
            && $this->apiKeyId
            && $this->storeId;
    }

    /**
     * Create a payment request for a sponsored listing via Jeko redirect flow.
     *
     * @param  SponsoredListing  $sponsored  The sponsored listing to pay for
     * @param  string  $paymentMethod  One of: wave, orange, mtn, moov, djamo
     * @return array{success: bool, redirect_url?: string, payment_id?: string, error?: string}
     */
    public function createPaymentRequest(SponsoredListing $sponsored, string $paymentMethod): array
    {
        if (! $this->isEnabled()) {
            return [
                'success' => false,
                'error' => 'Le service de paiement Jeko n\'est pas activé.',
            ];
        }

        // Generate a unique reference (1–100 chars)
        $reference = 'REZI-SP-'.$sponsored->id.'-'.Str::random(8);

        // Pour XOF, amountCents = montant en XOF directement (pas × 100)
        // Jeko docs: amountCents:10000 → amount:10000 XOF (mapping 1:1), minimum 100 XOF
        $amountCents = (int) round($sponsored->total_budget);

        if ($amountCents < 100) {
            return [
                'success' => false,
                'error' => 'Le montant minimum est de 100 FCFA.',
            ];
        }

        $successUrl = $this->callbackBaseUrl.'/payment/jeko/success?sponsored_id='.$sponsored->id;
        $errorUrl = $this->callbackBaseUrl.'/payment/jeko/error?sponsored_id='.$sponsored->id;

        $payload = [
            'storeId' => $this->storeId,
            'amountCents' => $amountCents,
            'currency' => $this->currency,
            'reference' => $reference,
            'paymentDetails' => [
                'type' => 'redirect',
                'data' => [
                    'paymentMethod' => $paymentMethod,
                    'successUrl' => $successUrl,
                    'errorUrl' => $errorUrl,
                ],
            ],
        ];

        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::withHeaders([
                'X-API-KEY' => $this->apiKey,
                'X-API-KEY-ID' => $this->apiKeyId,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl.'/partner_api/payment_requests', $payload);

            if ($response->successful()) {
                $data = $response->json();

                Log::info('Jeko payment request created', [
                    'sponsored_id' => $sponsored->id,
                    'jeko_payment_id' => $data['id'] ?? null,
                    'reference' => $reference,
                    'amount_cents' => $amountCents,
                    'payment_method' => $paymentMethod,
                ]);

                return [
                    'success' => true,
                    'redirect_url' => $data['redirectUrl'] ?? null,
                    'payment_id' => $data['id'] ?? null,
                    'reference' => $reference,
                ];
            }

            Log::error('Jeko payment request failed', [
                'sponsored_id' => $sponsored->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => 'Erreur lors de la création du paiement : '.($response->json('message') ?? 'Erreur inconnue'),
            ];
        } catch (RequestException $e) {
            Log::error('Jeko payment request exception', [
                'sponsored_id' => $sponsored->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Service de paiement temporairement indisponible. Veuillez réessayer.',
            ];
        } catch (\Throwable $e) {
            Log::error('Jeko payment unexpected error', [
                'sponsored_id' => $sponsored->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Une erreur inattendue est survenue. Veuillez réessayer.',
            ];
        }
    }

    /**
     * Create a payment request for a booking via Jeko redirect flow.
     *
     * @param  Booking  $booking  The booking to pay for
     * @param  string  $paymentMethod  One of: wave, orange, mtn, moov, djamo
     * @return array{success: bool, redirect_url?: string, payment_id?: string, reference?: string, error?: string}
     */
    public function createBookingPaymentRequest(Booking $booking, string $paymentMethod): array
    {
        if (! $this->isEnabled()) {
            return [
                'success' => false,
                'error' => 'Le service de paiement Jeko n\'est pas activé.',
            ];
        }

        $reference = 'REZI-BK-'.$booking->id.'-'.Str::random(8);
        // Pour XOF, amountCents = montant en XOF directement (pas × 100)
        // Jeko docs: amountCents:10000 → amount:10000 XOF (mapping 1:1), minimum 100 XOF
        $amountCents = (int) round($booking->total_amount);

        if ($amountCents < 100) {
            return [
                'success' => false,
                'error' => 'Le montant minimum est de 100 FCFA.',
            ];
        }

        $successUrl = $this->callbackBaseUrl.'/bookings/payment/success?booking='.$booking->uuid;
        $errorUrl = $this->callbackBaseUrl.'/bookings/payment/error?booking='.$booking->uuid;

        $payload = [
            'storeId' => $this->storeId,
            'amountCents' => $amountCents,
            'currency' => $this->currency,
            'reference' => $reference,
            'paymentDetails' => [
                'type' => 'redirect',
                'data' => [
                    'paymentMethod' => $paymentMethod,
                    'successUrl' => $successUrl,
                    'errorUrl' => $errorUrl,
                ],
            ],
        ];

        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::withHeaders([
                'X-API-KEY' => $this->apiKey,
                'X-API-KEY-ID' => $this->apiKeyId,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl.'/partner_api/payment_requests', $payload);

            if ($response->successful()) {
                $data = $response->json();

                Log::info('Jeko booking payment request created', [
                    'booking_id' => $booking->id,
                    'jeko_payment_id' => $data['id'] ?? null,
                    'reference' => $reference,
                    'amount_cents' => $amountCents,
                    'payment_method' => $paymentMethod,
                ]);

                // Store payment reference on booking
                $booking->update([
                    'payment_reference' => $reference,
                    'payment_method' => $paymentMethod,
                ]);

                return [
                    'success' => true,
                    'redirect_url' => $data['redirectUrl'] ?? null,
                    'payment_id' => $data['id'] ?? null,
                    'reference' => $reference,
                ];
            }

            Log::error('Jeko booking payment request failed', [
                'booking_id' => $booking->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => 'Erreur lors de la création du paiement : '.($response->json('message') ?? 'Erreur inconnue'),
            ];
        } catch (RequestException $e) {
            Log::error('Jeko booking payment request exception', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Service de paiement temporairement indisponible. Veuillez réessayer.',
            ];
        } catch (\Throwable $e) {
            Log::error('Jeko booking payment unexpected error', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Une erreur inattendue est survenue. Veuillez réessayer.',
            ];
        }
    }

    /**
     * Check the status of a payment request.
     *
     * @param  string  $paymentRequestId  The Jeko payment request ID
     * @return array{success: bool, status?: string, data?: array, error?: string}
     */
    public function getPaymentStatus(string $paymentRequestId): array
    {
        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::withHeaders([
                'X-API-KEY' => $this->apiKey,
                'X-API-KEY-ID' => $this->apiKeyId,
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl.'/partner_api/payment_requests/'.$paymentRequestId);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'status' => $data['status'] ?? 'unknown',
                    'data' => $data,
                ];
            }

            return [
                'success' => false,
                'error' => 'Impossible de vérifier le statut du paiement.',
            ];
        } catch (\Throwable $e) {
            Log::error('Jeko payment status check failed', [
                'payment_id' => $paymentRequestId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Erreur de vérification du paiement.',
            ];
        }
    }

    /**
     * Verify the webhook signature from Jeko.
     *
     * @param  string  $rawBody  The raw request body
     * @param  string  $signature  The Jeko-Signature header value
     * @return bool
     */
    public function verifyWebhookSignature(string $rawBody, string $signature): bool
    {
        if (! $this->webhookSecret) {
            Log::warning('Jeko webhook secret not configured');

            return false;
        }

        $computedSignature = hash_hmac('sha256', $rawBody, $this->webhookSecret);

        return hash_equals($computedSignature, $signature);
    }

    /**
     * Create a payment request for a subscription via Jeko redirect flow.
     *
     * @param  \App\Models\SubscriptionPayment  $payment  The subscription payment
     * @param  string  $paymentMethod  One of: wave, orange, mtn, moov, djamo
     * @param  string  $description  Payment description
     * @return array{success: bool, redirect_url?: string, payment_id?: string, error?: string}
     */
    public function createSubscriptionPayment(\App\Models\SubscriptionPayment $payment, string $paymentMethod, string $description = ''): array
    {
        if (! $this->isEnabled()) {
            return [
                'success' => false,
                'error' => 'Le service de paiement Jeko n\'est pas activé.',
            ];
        }

        // Pour XOF, amountCents = montant en XOF directement (pas × 100)
        $amountCents = (int) round($payment->amount);

        if ($amountCents < 100) {
            return [
                'success' => false,
                'error' => 'Le montant minimum est de 100 FCFA.',
            ];
        }

        $successUrl = $this->callbackBaseUrl.'/owner/subscriptions/payment/success?reference='.$payment->reference;
        $errorUrl = $this->callbackBaseUrl.'/owner/subscriptions/payment/error?reference='.$payment->reference;

        $payload = [
            'storeId' => $this->storeId,
            'amountCents' => $amountCents,
            'currency' => $this->currency,
            'reference' => $payment->reference,
            'paymentDetails' => [
                'type' => 'redirect',
                'data' => [
                    'paymentMethod' => $paymentMethod,
                    'successUrl' => $successUrl,
                    'errorUrl' => $errorUrl,
                ],
            ],
        ];

        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::withHeaders([
                'X-API-KEY' => $this->apiKey,
                'X-API-KEY-ID' => $this->apiKeyId,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl.'/partner_api/payment_requests', $payload);

            if ($response->successful()) {
                $data = $response->json();

                $payment->update([
                    'provider_reference' => $data['id'] ?? null,
                    'metadata' => array_merge($payment->metadata ?? [], [
                        'jeko_payment_id' => $data['id'] ?? null,
                        'payment_method' => $paymentMethod,
                        'description' => $description,
                    ]),
                ]);

                Log::info('Jeko subscription payment created', [
                    'subscription_payment_id' => $payment->id,
                    'reference' => $payment->reference,
                    'amount' => $payment->amount,
                ]);

                return [
                    'success' => true,
                    'redirect_url' => $data['redirectUrl'] ?? null,
                    'payment_id' => $data['id'] ?? null,
                ];
            }

            Log::error('Jeko subscription payment failed', [
                'payment_id' => $payment->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => 'Erreur lors de la création du paiement.',
            ];
        } catch (\Throwable $e) {
            Log::error('Jeko subscription payment exception', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Service de paiement temporairement indisponible.',
            ];
        }
    }

    /**
     * Create a payment request for insurance via Jeko redirect flow.
     *
     * @param  \App\Models\BookingInsurance  $insurance  The booking insurance
     * @param  string  $paymentMethod  One of: wave, orange, mtn, moov, djamo
     * @return array{success: bool, redirect_url?: string, payment_id?: string, error?: string}
     */
    public function createInsurancePayment(\App\Models\BookingInsurance $insurance, string $paymentMethod): array
    {
        if (! $this->isEnabled()) {
            return [
                'success' => false,
                'error' => 'Le service de paiement Jeko n\'est pas activé.',
            ];
        }

        $reference = 'REZI-INS-'.$insurance->id.'-'.\Illuminate\Support\Str::random(8);
        // Pour XOF, amountCents = montant en XOF directement (pas × 100)
        $amountCents = (int) round($insurance->premium_amount);

        $successUrl = $this->callbackBaseUrl.'/insurance/payment/success?reference='.$reference;
        $errorUrl = $this->callbackBaseUrl.'/insurance/payment/error?reference='.$reference;

        $payload = [
            'storeId' => $this->storeId,
            'amountCents' => $amountCents,
            'currency' => $this->currency,
            'reference' => $reference,
            'paymentDetails' => [
                'type' => 'redirect',
                'data' => [
                    'paymentMethod' => $paymentMethod,
                    'successUrl' => $successUrl,
                    'errorUrl' => $errorUrl,
                ],
            ],
        ];

        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::withHeaders([
                'X-API-KEY' => $this->apiKey,
                'X-API-KEY-ID' => $this->apiKeyId,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl.'/partner_api/payment_requests', $payload);

            if ($response->successful()) {
                $data = $response->json();

                $insurance->update([
                    'payment_reference' => $reference,
                    'metadata' => array_merge($insurance->metadata ?? [], [
                        'jeko_payment_id' => $data['id'] ?? null,
                        'payment_method' => $paymentMethod,
                    ]),
                ]);

                return [
                    'success' => true,
                    'redirect_url' => $data['redirectUrl'] ?? null,
                    'payment_id' => $data['id'] ?? null,
                    'reference' => $reference,
                ];
            }

            return [
                'success' => false,
                'error' => 'Erreur lors de la création du paiement.',
            ];
        } catch (\Throwable $e) {
            Log::error('Jeko insurance payment exception', [
                'insurance_id' => $insurance->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Service de paiement temporairement indisponible.',
            ];
        }
    }

    /**
     * Get the list of supported Jeko payment methods.
     *
     * @return array<string, array{label: string, icon: string, color: string}>
     */
    public static function paymentMethods(): array
    {
        return [
            'wave' => [
                'label' => 'Wave',
                'description' => 'Paiement via Wave Mobile Money',
                'color' => 'blue',
            ],
            'orange' => [
                'label' => 'Orange Money',
                'description' => 'Paiement via Orange Money',
                'color' => 'orange',
            ],
            'mtn' => [
                'label' => 'MTN MoMo',
                'description' => 'Paiement via MTN Mobile Money',
                'color' => 'yellow',
            ],
            'moov' => [
                'label' => 'Moov Money',
                'description' => 'Paiement via Moov Money',
                'color' => 'green',
            ],
            'djamo' => [
                'label' => 'Djamo',
                'description' => 'Paiement via carte Djamo',
                'color' => 'purple',
            ],
        ];
    }

    // =========================================================================
    // TRANSFERS (PAY-OUT) — Jeko Partner API
    // Documentation : https://developer.jeko.africa/fr/integration/transfers
    // =========================================================================

    /**
     * Créer ou récupérer un contact bénéficiaire Jeko pour un propriétaire.
     *
     * @param  User    $user           Le propriétaire bénéficiaire
     * @param  string  $paymentMethod  wave, orange_money, mtn, moov, djamo ou bank
     * @param  array   $identifier     ['number' => '+225...'] ou détails bancaires
     * @return array{success: bool, contact_id?: string, error?: string}
     */
    public function createOrGetContact(User $user, string $paymentMethod, array $identifier): array
    {
        // Si le propriétaire a déjà un contact Jeko enregistré, le réutiliser
        if ($user->jeko_contact_id) {
            return [
                'success' => true,
                'contact_id' => $user->jeko_contact_id,
            ];
        }

        // Mapper les noms d'opérateurs vers les noms Jeko
        $jekoMethod = $this->mapPayoutMethodToJeko($paymentMethod);

        $payload = [
            'name' => $user->name,
            'paymentMethod' => $jekoMethod,
            'identifier' => $identifier,
        ];

        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::withHeaders([
                'X-API-KEY' => $this->apiKey,
                'X-API-KEY-ID' => $this->apiKeyId,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl.'/partner_api/contacts', $payload);

            if ($response->successful()) {
                $data = $response->json();
                $contactId = $data['id'] ?? null;

                if ($contactId) {
                    // Persister le contactId sur le propriétaire
                    $user->update(['jeko_contact_id' => $contactId]);

                    Log::info('Jeko contact created', [
                        'user_id' => $user->id,
                        'contact_id' => $contactId,
                        'payment_method' => $jekoMethod,
                    ]);
                }

                return [
                    'success' => true,
                    'contact_id' => $contactId,
                ];
            }

            Log::error('Jeko create contact failed', [
                'user_id' => $user->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => 'Impossible de créer le contact bénéficiaire : '.($response->json('message') ?? 'Erreur inconnue'),
            ];
        } catch (\Throwable $e) {
            Log::error('Jeko create contact exception', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Service de paiement temporairement indisponible.',
            ];
        }
    }

    /**
     * Vérifier le solde disponible du magasin Jeko.
     *
     * @return array{success: bool, balance?: int, currency?: string, error?: string}
     */
    public function getStoreBalance(): array
    {
        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::withHeaders([
                'X-API-KEY' => $this->apiKey,
                'X-API-KEY-ID' => $this->apiKeyId,
            ])->get($this->baseUrl.'/partner_api/stores/'.$this->storeId.'/balance');

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'balance' => $data['balance'] ?? $data['amount'] ?? 0,
                    'currency' => $data['currency'] ?? $this->currency,
                ];
            }

            return [
                'success' => false,
                'error' => 'Impossible de vérifier le solde du magasin.',
            ];
        } catch (\Throwable $e) {
            Log::error('Jeko store balance check failed', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => 'Erreur de connexion au service de paiement.',
            ];
        }
    }

    /**
     * Exécuter un transfert (payout) vers un propriétaire via Jeko Transfers API.
     *
     * Flux :
     *   1. Créer/récupérer le contact bénéficiaire
     *   2. Vérifier le solde du magasin
     *   3. Créer le transfert via POST /partner_api/transfers
     *
     * @param  Payout  $payout  Le payout à traiter
     * @param  User    $owner   Le propriétaire bénéficiaire
     * @return array{success: bool, transfer_id?: string, status?: string, error?: string}
     */
    public function executeTransfer(Payout $payout, User $owner): array
    {
        if (! $this->isEnabled()) {
            return [
                'success' => false,
                'error' => 'Le service de paiement Jeko n\'est pas activé.',
            ];
        }

        // 1. Créer ou récupérer le contact bénéficiaire
        $identifier = $this->buildIdentifier($payout);
        $contactResult = $this->createOrGetContact($owner, $payout->payout_method, $identifier);

        if (! $contactResult['success']) {
            return $contactResult;
        }

        $contactId = $contactResult['contact_id'];

        // 2. Montant en XOF (mapping 1:1 avec Jeko, minimum 500 XOF)
        $amountCents = (int) round($payout->net_amount);

        if ($amountCents < 500) {
            return [
                'success' => false,
                'error' => 'Le montant minimum de transfert est de 5 FCFA.',
            ];
        }

        // 3. Créer le transfert
        $payload = [
            'storeId' => $this->storeId,
            'contactId' => $contactId,
            'amountCents' => $amountCents,
            'currency' => $this->currency,
            'description' => 'Versement REZI — '.$payout->reference,
        ];

        try {
            // Protéger contre le double envoi (retries, dispatches concurrents)
            $updated = Payout::where('id', $payout->id)
                ->whereIn('status', ['pending', 'approved'])
                ->update(['status' => 'processing']);

            if (!$updated) {
                return [
                    'success' => false,
                    'error' => 'Payout already being processed or completed.',
                ];
            }

            $payout->refresh();

            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::withHeaders([
                'X-API-KEY' => $this->apiKey,
                'X-API-KEY-ID' => $this->apiKeyId,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post($this->baseUrl.'/partner_api/transfers', $payload);

            $data = $response->json();

            if ($response->successful()) {
                $transferId = $data['id'] ?? null;
                $transferStatus = $data['status'] ?? 'pending';
                $fees = $data['fees']['amount'] ?? 0;

                // Mettre à jour le payout avec la référence Jeko
                $payout->update([
                    'provider_reference' => $transferId,
                    'transfer_fee' => $fees / 100, // centimes → FCFA
                    'metadata' => array_merge($payout->metadata ?? [], [
                        'jeko_transfer_id' => $transferId,
                        'jeko_status' => $transferStatus,
                        'jeko_fees' => $fees,
                        'jeko_contact_id' => $contactId,
                    ]),
                ]);

                // Si le transfert est immédiatement complété
                if ($transferStatus === 'success') {
                    $payout->markAsCompleted($transferId);
                }

                Log::info('Jeko transfer created', [
                    'payout_id' => $payout->id,
                    'transfer_id' => $transferId,
                    'status' => $transferStatus,
                    'amount_cents' => $amountCents,
                    'owner_id' => $owner->id,
                ]);

                return [
                    'success' => true,
                    'transfer_id' => $transferId,
                    'status' => $transferStatus,
                ];
            }

            $errorMsg = $data['message'] ?? 'Erreur lors du transfert';
            $payout->markAsFailed($errorMsg);

            Log::error('Jeko transfer failed', [
                'payout_id' => $payout->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => $errorMsg,
            ];
        } catch (\Throwable $e) {
            $payout->markAsFailed('Erreur de connexion : '.$e->getMessage());

            Log::error('Jeko transfer exception', [
                'payout_id' => $payout->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Service de paiement temporairement indisponible.',
            ];
        }
    }

    /**
     * Construire l'identifiant bénéficiaire à partir du payout.
     */
    private function buildIdentifier(Payout $payout): array
    {
        if ($payout->payout_method === 'bank_transfer') {
            return [
                'bankName' => $payout->bank_name ?? '',
                'accountNumber' => $payout->bank_account ?? '',
                'bankCode' => $payout->metadata['bank_code'] ?? '',
                'swiftCode' => $payout->metadata['swift_code'] ?? '',
                'agencyCode' => $payout->metadata['agency_code'] ?? '',
                'key' => $payout->metadata['rib_key'] ?? '',
            ];
        }

        // Mobile Money — formater le numéro au format international CI
        $phone = $payout->phone_number;
        if (! str_starts_with($phone, '+')) {
            $phone = '+225'.ltrim($phone, '0');
        }

        return ['number' => $phone];
    }

    /**
     * Mapper le nom de méthode de payout vers le nom Jeko.
     */
    private function mapPayoutMethodToJeko(string $method): string
    {
        return match ($method) {
            'wave' => 'wave',
            'orange_money' => 'orange_money',
            'mtn_money', 'mtn_momo' => 'mtn',
            'moov_money' => 'moov',
            'djamo' => 'djamo',
            'bank_transfer' => 'bank',
            default => $method,
        };
    }
}
