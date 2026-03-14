<?php

namespace App\Services;

use App\Models\SponsoredListing;
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
        $this->baseUrl = config('services.jeko.base_url', 'https://api.jeko.africa');
        $this->apiKey = config('services.jeko.api_key');
        $this->apiKeyId = config('services.jeko.api_key_id');
        $this->storeId = config('services.jeko.store_id');
        $this->currency = config('services.jeko.currency', 'XOF');
        $this->webhookSecret = config('services.jeko.webhook_secret');
        $this->callbackBaseUrl = config('services.jeko.callback_base_url') ?: config('app.url');
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
        $reference = 'REZI-SP-' . $sponsored->id . '-' . Str::random(8);

        // Amount in cents (minimum 100 centimes = 1 XOF — Jeko requires amountCents)
        $amountCents = (int) round($sponsored->total_budget * 100);

        if ($amountCents < 100) {
            return [
                'success' => false,
                'error' => 'Le montant minimum est de 1 FCFA.',
            ];
        }

        $successUrl = $this->callbackBaseUrl . '/payment/jeko/success?sponsored_id=' . $sponsored->id;
        $errorUrl = $this->callbackBaseUrl . '/payment/jeko/error?sponsored_id=' . $sponsored->id;

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
            $response = Http::withHeaders([
                'X-API-KEY' => $this->apiKey,
                'X-API-KEY-ID' => $this->apiKeyId,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/partner_api/payment_requests', $payload);

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
                'error' => 'Erreur lors de la création du paiement : ' . ($response->json('message') ?? 'Erreur inconnue'),
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
     * Check the status of a payment request.
     *
     * @param  string  $paymentRequestId  The Jeko payment request ID
     * @return array{success: bool, status?: string, data?: array, error?: string}
     */
    public function getPaymentStatus(string $paymentRequestId): array
    {
        try {
            $response = Http::withHeaders([
                'X-API-KEY' => $this->apiKey,
                'X-API-KEY-ID' => $this->apiKeyId,
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl . '/partner_api/payment_requests/' . $paymentRequestId);

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

        $amountCents = (int) round($payment->amount * 100);

        if ($amountCents < 100) {
            return [
                'success' => false,
                'error' => 'Le montant minimum est de 1 FCFA.',
            ];
        }

        $successUrl = $this->callbackBaseUrl . '/owner/subscriptions/payment/success?reference=' . $payment->reference;
        $errorUrl = $this->callbackBaseUrl . '/owner/subscriptions/payment/error?reference=' . $payment->reference;

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
            $response = Http::withHeaders([
                'X-API-KEY' => $this->apiKey,
                'X-API-KEY-ID' => $this->apiKeyId,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/partner_api/payment_requests', $payload);

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

        $reference = 'REZI-INS-' . $insurance->id . '-' . \Illuminate\Support\Str::random(8);
        $amountCents = (int) round($insurance->premium_amount * 100);

        $successUrl = $this->callbackBaseUrl . '/insurance/payment/success?reference=' . $reference;
        $errorUrl = $this->callbackBaseUrl . '/insurance/payment/error?reference=' . $reference;

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
            $response = Http::withHeaders([
                'X-API-KEY' => $this->apiKey,
                'X-API-KEY-ID' => $this->apiKeyId,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/partner_api/payment_requests', $payload);

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
}
