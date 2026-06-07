<?php

namespace App\Services\Jeko;

use App\Models\Booking;
use App\Models\BookingInsurance;
use App\Models\Payment;
use App\Models\SponsoredListing;
use App\Models\SubscriptionPayment;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class JekoPaymentRequestService
{
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
    protected JekoCallbackUrlService $callbackUrls;

    public function __construct(?JekoCallbackUrlService $callbackUrls = null)
    {
        $this->baseUrl = config('services.jeko.base_url') ?? 'https://api.jeko.africa';
        $this->apiKey = config('services.jeko.api_key') ?? '';
        $this->apiKeyId = config('services.jeko.api_key_id') ?? '';
        $this->storeId = config('services.jeko.store_id') ?? '';
        $this->currency = config('services.jeko.currency') ?? 'XOF';
        $this->callbackUrls = $callbackUrls ?? new JekoCallbackUrlService();
    }

    public function createSponsoredPaymentRequest(SponsoredListing $sponsored, string $paymentMethod): array
    {
        if (! $this->isEnabled()) {
            return $this->failure(self::ERROR_JEKO_DISABLED);
        }

        $reference = 'REZI-SP-'.$sponsored->id.'-'.Str::random(8);
        $amount = (float) $sponsored->total_budget;

        if ($amount < 100) {
            return $this->failure(self::ERROR_MINIMUM_AMOUNT);
        }

        $amountCents = (int) round($amount * 100);
        $payload = [
            'storeId' => $this->storeId,
            'amountCents' => $amountCents,
            'currency' => $this->currency,
            'reference' => $reference,
            'paymentDetails' => [
                'type' => 'redirect',
                'data' => [
                    'paymentMethod' => $paymentMethod,
                    'successUrl' => $this->callbackUrls->sponsoredSuccessUrl($sponsored, $reference),
                    'errorUrl' => $this->callbackUrls->sponsoredErrorUrl($sponsored, $reference),
                ],
            ],
        ];

        return $this->sendPaymentRequest(
            $payload,
            success: function (array $data) use ($sponsored, $reference, $paymentMethod, $amountCents): array {
                $sponsored->update([
                    'jeko_reference' => $reference,
                    'jeko_payment_id' => $data['id'] ?? null,
                    'payment_method' => $paymentMethod,
                    'payment_status' => 'pending',
                ]);

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
            },
            failure: function (int $status, string $body, ?string $message) use ($sponsored): array {
                Log::error('Jeko payment request failed', [
                    'sponsored_id' => $sponsored->id,
                    'status' => $status,
                    'body' => $body,
                ]);

                return $this->failure('Erreur lors de la création du paiement : '.($message ?? self::ERROR_UNKNOWN));
            },
            requestException: function (RequestException $exception) use ($sponsored): array {
                Log::error('Jeko payment request exception', [
                    'sponsored_id' => $sponsored->id,
                    'error' => $exception->getMessage(),
                ]);

                return $this->failure(self::ERROR_TEMPORARILY_UNAVAILABLE_RETRY);
            },
            unexpectedException: function (\Throwable $exception) use ($sponsored): array {
                Log::error('Jeko payment unexpected error', [
                    'sponsored_id' => $sponsored->id,
                    'error' => $exception->getMessage(),
                ]);

                return $this->failure('Une erreur inattendue est survenue. Veuillez réessayer.');
            },
        );
    }

    /**
     * Create a Jeko payment request for a booking.
     *
     * The Payment record must already be created (via PaymentService::createBookingPayment)
     * so that wallet/referral credits have been atomically deducted. This method uses
     * the post-credit total_amount from the Payment record as the charge amount sent to Jeko.
     */
    public function createBookingPaymentRequest(Booking $booking, string $paymentMethod, Payment $payment): array
    {
        if (! $this->isEnabled()) {
            return $this->failure(self::ERROR_JEKO_DISABLED);
        }

        $booking->loadMissing('user');

        $reference = 'REZI-BK-'.$booking->id.'-'.Str::random(8);

        // Use the post-credit amount from the already-created Payment record
        $chargeAmount = (float) $payment->total_amount;

        if ($chargeAmount < 100) {
            return $this->failure(self::ERROR_MINIMUM_AMOUNT);
        }

        $amountCents = (int) round($chargeAmount * 100);
        $payload = [
            'storeId' => $this->storeId,
            'amountCents' => $amountCents,
            'currency' => $this->currency,
            'reference' => $reference,
            'paymentDetails' => [
                'type' => 'redirect',
                'data' => [
                    'paymentMethod' => $paymentMethod,
                    'successUrl' => $this->callbackUrls->bookingSuccessUrl($booking),
                    'errorUrl' => $this->callbackUrls->bookingErrorUrl($booking),
                ],
            ],
        ];

        return $this->sendPaymentRequest(
            $payload,
            success: function (array $data) use ($booking, $payment, $reference, $paymentMethod, $amountCents): array {
                Log::info('Jeko booking payment request created', [
                    'booking_id' => $booking->id,
                    'payment_id' => $payment->id,
                    'jeko_payment_id' => $data['id'] ?? null,
                    'reference' => $reference,
                    'amount_cents' => $amountCents,
                    'payment_method' => $paymentMethod,
                ]);

                $booking->update([
                    'payment_reference' => $reference,
                    'payment_method' => $paymentMethod,
                ]);

                // Store Jeko's payment ID on the Payment record for webhook reconciliation
                $payment->update([
                    'metadata' => array_merge($payment->metadata ?? [], [
                        'jeko_payment_id' => $data['id'] ?? null,
                        'jeko_reference' => $reference,
                    ]),
                ]);

                return [
                    'success' => true,
                    'redirect_url' => $data['redirectUrl'] ?? null,
                    'payment_id' => $data['id'] ?? null,
                    'reference' => $reference,
                ];
            },
            failure: function (int $status, string $body, ?string $message) use ($booking): array {
                Log::error('Jeko booking payment request failed', [
                    'booking_id' => $booking->id,
                    'status' => $status,
                    'body' => $body,
                ]);

                return $this->failure('Erreur lors de la création du paiement : '.($message ?? self::ERROR_UNKNOWN));
            },
            requestException: function (RequestException $exception) use ($booking): array {
                Log::error('Jeko booking payment request exception', [
                    'booking_id' => $booking->id,
                    'error' => $exception->getMessage(),
                ]);

                return $this->failure(self::ERROR_TEMPORARILY_UNAVAILABLE_RETRY);
            },
            unexpectedException: function (\Throwable $exception) use ($booking): array {
                Log::error('Jeko booking payment unexpected error', [
                    'booking_id' => $booking->id,
                    'error' => $exception->getMessage(),
                ]);

                return $this->failure('Une erreur inattendue est survenue. Veuillez réessayer.');
            },
        );
    }

    public function createSubscriptionPayment(SubscriptionPayment $payment, string $paymentMethod, string $description = ''): array
    {
        if (! $this->isEnabled()) {
            return $this->failure(self::ERROR_JEKO_DISABLED);
        }

        $amount = (float) $payment->amount;
        if ($amount < 100) {
            return $this->failure(self::ERROR_MINIMUM_AMOUNT);
        }

        $payload = [
            'storeId' => $this->storeId,
            'amountCents' => (int) round($amount * 100),
            'currency' => $this->currency,
            'reference' => $payment->reference,
            'paymentDetails' => [
                'type' => 'redirect',
                'data' => [
                    'paymentMethod' => $paymentMethod,
                    'successUrl' => $this->callbackUrls->subscriptionSuccessUrl($payment->reference),
                    'errorUrl' => $this->callbackUrls->subscriptionErrorUrl($payment->reference),
                ],
            ],
        ];

        return $this->sendPaymentRequest(
            $payload,
            success: function (array $data) use ($payment, $paymentMethod, $description): array {
                $payment->update([
                    'provider_response' => array_merge($payment->provider_response ?? [], [
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
            },
            failure: function (int $status, string $body, ?string $message) use ($payment): array {
                Log::error('Jeko subscription payment failed', [
                    'payment_id' => $payment->id,
                    'status' => $status,
                    'body' => $body,
                    'message' => $message,
                ]);

                return $this->failure('Erreur lors de la création du paiement.');
            },
            requestException: function (RequestException $exception) use ($payment): array {
                Log::error('Jeko subscription payment exception', [
                    'payment_id' => $payment->id,
                    'error' => $exception->getMessage(),
                ]);

                return $this->failure(self::ERROR_TEMPORARILY_UNAVAILABLE);
            },
            unexpectedException: function (\Throwable $exception) use ($payment): array {
                Log::error('Jeko subscription payment exception', [
                    'payment_id' => $payment->id,
                    'error' => $exception->getMessage(),
                ]);

                return $this->failure(self::ERROR_TEMPORARILY_UNAVAILABLE);
            },
        );
    }

    public function createInsurancePayment(BookingInsurance $insurance, string $paymentMethod): array
    {
        if (! $this->isEnabled()) {
            return $this->failure(self::ERROR_JEKO_DISABLED);
        }

        $reference = 'REZI-INS-'.$insurance->id.'-'.Str::random(8);
        $amount = (float) $insurance->premium_amount;
        if ($amount < 100) {
            return $this->failure(self::ERROR_MINIMUM_AMOUNT);
        }

        $payload = [
            'storeId' => $this->storeId,
            'amountCents' => (int) round($amount * 100),
            'currency' => $this->currency,
            'reference' => $reference,
            'paymentDetails' => [
                'type' => 'redirect',
                'data' => [
                    'paymentMethod' => $paymentMethod,
                    'successUrl' => $this->callbackUrls->insuranceSuccessUrl($reference),
                    'errorUrl' => $this->callbackUrls->insuranceErrorUrl($reference),
                ],
            ],
        ];

        return $this->sendPaymentRequest(
            $payload,
            success: function (array $data) use ($insurance, $reference, $paymentMethod): array {
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
            },
            failure: fn (int $status, string $body, ?string $message): array => $this->failure('Erreur lors de la création du paiement.'),
            requestException: function (RequestException $exception) use ($insurance): array {
                Log::error('Jeko insurance payment exception', [
                    'insurance_id' => $insurance->id,
                    'error' => $exception->getMessage(),
                ]);

                return $this->failure(self::ERROR_TEMPORARILY_UNAVAILABLE);
            },
            unexpectedException: function (\Throwable $exception) use ($insurance): array {
                Log::error('Jeko insurance payment exception', [
                    'insurance_id' => $insurance->id,
                    'error' => $exception->getMessage(),
                ]);

                return $this->failure(self::ERROR_TEMPORARILY_UNAVAILABLE);
            },
        );
    }

    private function isEnabled(): bool
    {
        return config('services.jeko.enabled', false)
            && $this->apiKey !== ''
            && $this->apiKeyId !== ''
            && $this->storeId !== '';
    }

    private function headers(): array
    {
        return [
            'X-API-KEY' => $this->apiKey,
            'X-API-KEY-ID' => $this->apiKeyId,
            'Content-Type' => self::CONTENT_TYPE_JSON,
        ];
    }

    private function failure(string $error): array
    {
        return [
            'success' => false,
            'error' => $error,
        ];
    }

    private function sendPaymentRequest(
        array $payload,
        callable $success,
        callable $failure,
        callable $requestException,
        callable $unexpectedException,
    ): array {
        $result = [];

        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::withHeaders($this->headers())
                ->post($this->baseUrl.self::PAYMENT_REQUESTS_ENDPOINT, $payload);

            if ($response->successful()) {
                $result = $success($response->json());
            } else {
                $result = $failure(
                    $response->status(),
                    $response->body(),
                    $response->json('message'),
                );
            }
        } catch (RequestException $exception) {
            $result = $requestException($exception);
        } catch (\Throwable $exception) {
            $result = $unexpectedException($exception);
        }

        return $result;
    }
}
