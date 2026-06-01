<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payout;
use App\Models\SponsoredListing;
use App\Models\User;
use App\Services\Concerns\HandlesJekoTransfers;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class JekoPaymentService
{
    use HandlesJekoTransfers;

    private const CONTENT_TYPE_JSON = 'application/json';
    private const PAYMENT_REQUESTS_ENDPOINT = '/partner_api/payment_requests';
    private const ERROR_JEKO_DISABLED = 'Le service de paiement Jeko n\'est pas activé.';
    private const ERROR_MINIMUM_AMOUNT = 'Le montant minimum est de 100 FCFA.';
    private const ERROR_UNKNOWN = 'Erreur inconnue';
    private const ERROR_TEMPORARILY_UNAVAILABLE = 'Service de paiement temporairement indisponible.';
    private const ERROR_TEMPORARILY_UNAVAILABLE_RETRY = 'Service de paiement temporairement indisponible. Veuillez réessayer.';

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

    private function failure(string $error): array
    {
        return [
            'success' => false,
            'error' => $error,
        ];
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
        $result = null;

        if (! $this->isEnabled()) {
            $result = $this->failure(self::ERROR_JEKO_DISABLED);
        } else {
            $reference = 'REZI-SP-'.$sponsored->id.'-'.Str::random(8);
            $amountCents = (int) round($sponsored->total_budget);

            if ($amountCents < 100) {
                $result = $this->failure(self::ERROR_MINIMUM_AMOUNT);
            } else {
                $payload = [
                    'storeId' => $this->storeId,
                    'amountCents' => $amountCents,
                    'currency' => $this->currency,
                    'reference' => $reference,
                    'paymentDetails' => [
                        'type' => 'redirect',
                        'data' => [
                            'paymentMethod' => $paymentMethod,
                            'successUrl' => $this->signedSponsoredSuccessUrl($sponsored, $reference),
                            'errorUrl' => $this->signedSponsoredErrorUrl($sponsored, $reference),
                        ],
                    ],
                ];

                try {
                    /** @var \Illuminate\Http\Client\Response $response */
                    $response = Http::withHeaders([
                        'X-API-KEY' => $this->apiKey,
                        'X-API-KEY-ID' => $this->apiKeyId,
                        'Content-Type' => self::CONTENT_TYPE_JSON,
                    ])->post($this->baseUrl.self::PAYMENT_REQUESTS_ENDPOINT, $payload);

                    if ($response->successful()) {
                        $data = $response->json();

                        Log::info('Jeko payment request created', [
                            'sponsored_id' => $sponsored->id,
                            'jeko_payment_id' => $data['id'] ?? null,
                            'reference' => $reference,
                            'amount_cents' => $amountCents,
                            'payment_method' => $paymentMethod,
                        ]);

                        $result = [
                            'success' => true,
                            'redirect_url' => $data['redirectUrl'] ?? null,
                            'payment_id' => $data['id'] ?? null,
                            'reference' => $reference,
                        ];
                    } else {
                        Log::error('Jeko payment request failed', [
                            'sponsored_id' => $sponsored->id,
                            'status' => $response->status(),
                            'body' => $response->body(),
                        ]);

                        $result = $this->failure('Erreur lors de la création du paiement : '.($response->json('message') ?? self::ERROR_UNKNOWN));
                    }
                } catch (RequestException $e) {
                    Log::error('Jeko payment request exception', [
                        'sponsored_id' => $sponsored->id,
                        'error' => $e->getMessage(),
                    ]);

                    $result = $this->failure(self::ERROR_TEMPORARILY_UNAVAILABLE_RETRY);
                } catch (\Throwable $e) {
                    Log::error('Jeko payment unexpected error', [
                        'sponsored_id' => $sponsored->id,
                        'error' => $e->getMessage(),
                    ]);

                    $result = $this->failure('Une erreur inattendue est survenue. Veuillez réessayer.');
                }
            }
        }

        return $result;
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
        $result = null;

        if (! $this->isEnabled()) {
            $result = $this->failure(self::ERROR_JEKO_DISABLED);
        } else {
            $reference = 'REZI-BK-'.$booking->id.'-'.Str::random(8);
            $chargeAmount = ($booking->payment_split && $booking->deposit_amount > 0)
                ? $booking->deposit_amount
                : $booking->total_amount;
            $amountCents = (int) round($chargeAmount * 100);

            if ($chargeAmount < 100) {
                $result = $this->failure(self::ERROR_MINIMUM_AMOUNT);
            } else {
                $result = $this->sendBookingPaymentRequest($booking, $paymentMethod, $reference, $amountCents);
            }
        }

        return $result;
    }

    private function sendBookingPaymentRequest(Booking $booking, string $paymentMethod, string $reference, int $amountCents): array
    {
        $result = null;
        $payload = [
            'storeId' => $this->storeId,
            'amountCents' => $amountCents,
            'currency' => $this->currency,
            'reference' => $reference,
            'paymentDetails' => [
                'type' => 'redirect',
                'data' => [
                    'paymentMethod' => $paymentMethod,
                    'successUrl' => $this->callbackBaseUrl.'/bookings/payment/success?booking='.$booking->uuid,
                    'errorUrl' => $this->callbackBaseUrl.'/bookings/payment/error?booking='.$booking->uuid,
                ],
            ],
        ];

        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::withHeaders([
                'X-API-KEY' => $this->apiKey,
                'X-API-KEY-ID' => $this->apiKeyId,
                'Content-Type' => self::CONTENT_TYPE_JSON,
            ])->post($this->baseUrl.self::PAYMENT_REQUESTS_ENDPOINT, $payload);

            if ($response->successful()) {
                $data = $response->json();

                Log::info('Jeko booking payment request created', [
                    'booking_id' => $booking->id,
                    'jeko_payment_id' => $data['id'] ?? null,
                    'reference' => $reference,
                    'amount_cents' => $amountCents,
                    'payment_method' => $paymentMethod,
                ]);

                $booking->update([
                    'payment_reference' => $reference,
                    'payment_method' => $paymentMethod,
                ]);

                $result = [
                    'success' => true,
                    'redirect_url' => $data['redirectUrl'] ?? null,
                    'payment_id' => $data['id'] ?? null,
                    'reference' => $reference,
                ];
            } else {
                Log::error('Jeko booking payment request failed', [
                    'booking_id' => $booking->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                $result = $this->failure('Erreur lors de la création du paiement : '.($response->json('message') ?? self::ERROR_UNKNOWN));
            }
        } catch (RequestException $e) {
            Log::error('Jeko booking payment request exception', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);

            $result = $this->failure(self::ERROR_TEMPORARILY_UNAVAILABLE_RETRY);
        } catch (\Throwable $e) {
            Log::error('Jeko booking payment unexpected error', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);

            $result = $this->failure('Une erreur inattendue est survenue. Veuillez réessayer.');
        }

        return $result;
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
                'Content-Type' => self::CONTENT_TYPE_JSON,
            ])->get($this->baseUrl.self::PAYMENT_REQUESTS_ENDPOINT.'/'.$paymentRequestId);

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

    public function signedSponsoredSuccessUrl(SponsoredListing $sponsored, string $reference): string
    {
        return $this->formatRelativeSignedCallbackUrl(
            URL::temporarySignedRoute(
                'payment.jeko.success',
                now()->addDay(),
                [
                    'sponsored_id' => $sponsored->getKey(),
                    'reference' => $reference,
                ],
                absolute: false,
            ),
        );
    }

    public function signedSponsoredErrorUrl(SponsoredListing $sponsored, string $reference): string
    {
        return $this->formatRelativeSignedCallbackUrl(
            URL::temporarySignedRoute(
                'payment.jeko.error',
                now()->addDay(),
                [
                    'sponsored_id' => $sponsored->getKey(),
                    'reference' => $reference,
                ],
                absolute: false,
            ),
        );
    }

    public function signedSponsoredCheckUrl(SponsoredListing $sponsored): string
    {
        return $this->formatRelativeSignedCallbackUrl(
            URL::temporarySignedRoute(
                'payment.jeko.check',
                now()->addDay(),
                [
                    'sponsored' => $sponsored->getKey(),
                    'reference' => $sponsored->jeko_reference,
                ],
                absolute: false,
            ),
        );
    }

    protected function formatRelativeSignedCallbackUrl(string $relativeSignedUrl): string
    {
        if ($this->callbackBaseUrl) {
            return rtrim($this->callbackBaseUrl, '/').$relativeSignedUrl;
        }

        return URL::to($relativeSignedUrl);
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
        $result = null;

        if (! $this->isEnabled()) {
            $result = $this->failure(self::ERROR_JEKO_DISABLED);
        } else {
            $amountCents = (int) round($payment->amount);

            if ($amountCents < 100) {
                $result = $this->failure(self::ERROR_MINIMUM_AMOUNT);
            } else {
                $payload = [
                    'storeId' => $this->storeId,
                    'amountCents' => $amountCents,
                    'currency' => $this->currency,
                    'reference' => $payment->reference,
                    'paymentDetails' => [
                        'type' => 'redirect',
                        'data' => [
                            'paymentMethod' => $paymentMethod,
                            'successUrl' => $this->callbackBaseUrl.'/owner/subscriptions/payment/success?reference='.$payment->reference,
                            'errorUrl' => $this->callbackBaseUrl.'/owner/subscriptions/payment/error?reference='.$payment->reference,
                        ],
                    ],
                ];

                try {
                    /** @var \Illuminate\Http\Client\Response $response */
                    $response = Http::withHeaders([
                        'X-API-KEY' => $this->apiKey,
                        'X-API-KEY-ID' => $this->apiKeyId,
                        'Content-Type' => self::CONTENT_TYPE_JSON,
                    ])->post($this->baseUrl.self::PAYMENT_REQUESTS_ENDPOINT, $payload);

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

                        $result = [
                            'success' => true,
                            'redirect_url' => $data['redirectUrl'] ?? null,
                            'payment_id' => $data['id'] ?? null,
                        ];
                    } else {
                        Log::error('Jeko subscription payment failed', [
                            'payment_id' => $payment->id,
                            'status' => $response->status(),
                            'body' => $response->body(),
                        ]);

                        $result = $this->failure('Erreur lors de la création du paiement.');
                    }
                } catch (\Throwable $e) {
                    Log::error('Jeko subscription payment exception', [
                        'payment_id' => $payment->id,
                        'error' => $e->getMessage(),
                    ]);

                    $result = $this->failure(self::ERROR_TEMPORARILY_UNAVAILABLE);
                }
            }
        }

        return $result;
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
        $result = null;

        if (! $this->isEnabled()) {
            $result = $this->failure(self::ERROR_JEKO_DISABLED);
        } else {
            $reference = 'REZI-INS-'.$insurance->id.'-'.\Illuminate\Support\Str::random(8);
            $amountCents = (int) round($insurance->premium_amount);

            $payload = [
                'storeId' => $this->storeId,
                'amountCents' => $amountCents,
                'currency' => $this->currency,
                'reference' => $reference,
                'paymentDetails' => [
                    'type' => 'redirect',
                    'data' => [
                        'paymentMethod' => $paymentMethod,
                        'successUrl' => $this->callbackBaseUrl.'/insurance/payment/success?reference='.$reference,
                        'errorUrl' => $this->callbackBaseUrl.'/insurance/payment/error?reference='.$reference,
                    ],
                ],
            ];

            try {
                /** @var \Illuminate\Http\Client\Response $response */
                $response = Http::withHeaders([
                    'X-API-KEY' => $this->apiKey,
                    'X-API-KEY-ID' => $this->apiKeyId,
                    'Content-Type' => self::CONTENT_TYPE_JSON,
                ])->post($this->baseUrl.self::PAYMENT_REQUESTS_ENDPOINT, $payload);

                if ($response->successful()) {
                    $data = $response->json();

                    $insurance->update([
                        'payment_reference' => $reference,
                        'metadata' => array_merge($insurance->metadata ?? [], [
                            'jeko_payment_id' => $data['id'] ?? null,
                            'payment_method' => $paymentMethod,
                        ]),
                    ]);

                    $result = [
                        'success' => true,
                        'redirect_url' => $data['redirectUrl'] ?? null,
                        'payment_id' => $data['id'] ?? null,
                        'reference' => $reference,
                    ];
                } else {
                    $result = $this->failure('Erreur lors de la création du paiement.');
                }
            } catch (\Throwable $e) {
                Log::error('Jeko insurance payment exception', [
                    'insurance_id' => $insurance->id,
                    'error' => $e->getMessage(),
                ]);

                $result = $this->failure(self::ERROR_TEMPORARILY_UNAVAILABLE);
            }
        }

        return $result;
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
        $result = null;

        if ($user->jeko_contact_id) {
            $result = [
                'success' => true,
                'contact_id' => $user->jeko_contact_id,
            ];
        } else {
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
                    'Content-Type' => self::CONTENT_TYPE_JSON,
                ])->post($this->baseUrl.'/partner_api/contacts', $payload);

                if ($response->successful()) {
                    $data = $response->json();
                    $contactId = $data['id'] ?? null;

                    if ($contactId) {
                        $user->update(['jeko_contact_id' => $contactId]);

                        Log::info('Jeko contact created', [
                            'user_id' => $user->id,
                            'contact_id' => $contactId,
                            'payment_method' => $jekoMethod,
                        ]);
                    }

                    $result = [
                        'success' => true,
                        'contact_id' => $contactId,
                    ];
                } else {
                    Log::error('Jeko create contact failed', [
                        'user_id' => $user->id,
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);

                    $result = $this->failure('Impossible de créer le contact bénéficiaire : '.($response->json('message') ?? self::ERROR_UNKNOWN));
                }
            } catch (\Throwable $e) {
                Log::error('Jeko create contact exception', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);

                $result = $this->failure(self::ERROR_TEMPORARILY_UNAVAILABLE);
            }
        }

        return $result;
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
        $result = null;

        if (! $this->isEnabled()) {
            $result = $this->failure(self::ERROR_JEKO_DISABLED);
        } else {
            $result = $this->executeEnabledTransfer($payout, $owner);
        }

        return $result;
    }

    /**
     * Construire l'identifiant bénéficiaire à partir du payout.
     */
    protected function buildIdentifier(Payout $payout): array
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
