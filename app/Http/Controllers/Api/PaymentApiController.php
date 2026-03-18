<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Services\BusinessEventService;
use App\Services\JekoService;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * API Payment Controller — mobile-first, JSON only.
 *
 * Handles the complete payment lifecycle:
 *   POST /payments/initiate/{booking} → start payment
 *   POST /payments/verify-otp/{payment} → verify OTP
 *   GET  /payments/{payment}/status → check status
 *   GET  /payments → payment history
 *   GET  /payments/methods → saved payment methods
 *   POST /payments/methods → save payment method
 *   DELETE /payments/methods/{method} → remove method
 *   GET  /payments/operators → available operators
 */
class PaymentApiController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService,
        protected JekoService $jekoService,
    ) {}

    /**
     * Initiate a Mobile Money payment for a booking.
     */
    public function initiate(Request $request, Booking $booking): JsonResponse
    {
        $request->validate([
            'phone_number' => ['required', 'string', 'regex:/^[0-9]{8,10}$/'],
            'operator' => ['nullable', 'string', 'in:orange_money,mtn_momo,moov_money,wave'],
            'save_method' => ['nullable', 'boolean'],
        ]);

        // Authorization
        if ($booking->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé.',
                'error_code' => 'FORBIDDEN',
            ], 403);
        }

        // State guards
        if (! in_array($booking->status, ['pending_payment', 'pending'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cette réservation ne peut pas être payée.',
                'error_code' => 'INVALID_STATE',
            ], 400);
        }

        if ($booking->isPaid()) {
            return response()->json([
                'success' => false,
                'message' => 'Cette réservation est déjà payée.',
                'error_code' => 'ALREADY_PAID',
            ], 409);
        }

        if ($booking->total_amount <= 0) {
            Log::channel('critical')->error('API Payment: Invalid amount', [
                'booking_id' => $booking->id,
                'amount' => $booking->total_amount,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Montant invalide.',
                'error_code' => 'INVALID_AMOUNT',
            ], 400);
        }

        try {
            // Create payment (idempotent)
            $payment = $this->paymentService->createBookingPayment($booking, Auth::user(), [
                'provider' => 'jeko',
            ]);

            // Initiate Mobile Money
            $result = $this->paymentService->initiatePayment(
                $payment,
                $request->phone_number,
                $request->operator,
            );

            if ($result['success']) {
                // Save method if requested
                if ($request->save_method) {
                    $this->paymentService->savePaymentMethod(Auth::user(), [
                        'provider_code' => $request->operator ?? $this->jekoService->detectOperator($request->phone_number),
                        'phone_number' => $request->phone_number,
                    ]);
                }

                BusinessEventService::paymentInitiated(
                    Auth::id(),
                    $payment->id,
                    (float) $payment->total_amount,
                    $request->operator ?? 'auto',
                    ['channel' => 'api'],
                );

                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'data' => [
                        'payment_id' => $payment->id,
                        'payment_uuid' => $payment->uuid,
                        'requires_otp' => $result['requires_otp'] ?? true,
                        'expires_at' => $result['expires_at'] ?? null,
                        'amount' => (float) $payment->total_amount,
                        'formatted_amount' => number_format((float) $payment->total_amount, 0, ',', ' ') . ' FCFA',
                    ],
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code'] ?? 'INIT_FAILED',
                'can_retry' => true,
            ], 400);
        } catch (\Exception $e) {
            report($e);
            Log::channel('critical')->error('API Payment initiation exception', [
                'booking_id' => $booking->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du paiement. Réessayez.',
                'error_code' => 'INTERNAL_ERROR',
                'can_retry' => true,
            ], 500);
        }
    }

    /**
     * Verify OTP code for a payment.
     */
    public function verifyOtp(Request $request, Payment $payment): JsonResponse
    {
        $request->validate([
            'otp' => ['required', 'string', 'size:6'],
        ]);

        if ($payment->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé.',
                'error_code' => 'FORBIDDEN',
            ], 403);
        }

        // State guard
        if (! in_array($payment->status, [Payment::STATUS_PROCESSING, Payment::STATUS_PENDING])) {
            return response()->json([
                'success' => false,
                'message' => 'Ce paiement ne peut plus être vérifié.',
                'error_code' => 'INVALID_STATE',
            ], 400);
        }

        // Expiry guard
        if ($payment->expires_at && $payment->expires_at->isPast()) {
            $payment->markAsFailed('Paiement expiré');

            BusinessEventService::paymentFailed(
                Auth::id(), $payment->id, (float) $payment->total_amount,
                'expired', ['channel' => 'api'],
            );

            return response()->json([
                'success' => false,
                'message' => 'Le délai est expiré. Relancez le paiement.',
                'error_code' => 'PAYMENT_EXPIRED',
                'can_retry' => true,
            ], 410);
        }

        try {
            $result = $this->paymentService->verifyOtp($payment, $request->otp);

            if ($result['success']) {
                Log::channel('payments')->info('API OTP verified', [
                    'payment_id' => $payment->id,
                    'user_id' => Auth::id(),
                ]);

                BusinessEventService::paymentCompleted(
                    Auth::id(), $payment->id, (float) $payment->total_amount,
                    ['channel' => 'api'],
                );

                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'data' => [
                        'payment' => new PaymentResource($payment->fresh()),
                        'booking_confirmed' => true,
                    ],
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'pending' => $result['pending'] ?? false,
                'attempts_remaining' => $result['attempts_remaining'] ?? null,
            ], $result['pending'] ?? false ? 202 : 400);
        } catch (\Exception $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Erreur de vérification. Réessayez.',
                'error_code' => 'INTERNAL_ERROR',
            ], 500);
        }
    }

    /**
     * Check payment status (polling from mobile).
     */
    public function status(Payment $payment): JsonResponse
    {
        if ($payment->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé.',
                'error_code' => 'FORBIDDEN',
            ], 403);
        }

        // If still processing, check provider
        if (in_array($payment->status, [Payment::STATUS_PROCESSING, Payment::STATUS_PENDING])) {
            try {
                $this->jekoService->checkPaymentStatus($payment);
                $payment->refresh();
            } catch (\Throwable $e) {
                // Jeko check failed — return current status anyway
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'status' => $payment->status,
                'is_completed' => $payment->isCompleted(),
                'is_failed' => $payment->isFailed(),
                'is_pending' => in_array($payment->status, [Payment::STATUS_PROCESSING, Payment::STATUS_PENDING]),
                'can_retry' => $payment->isFailed(),
                'amount' => (float) $payment->total_amount,
                'formatted_amount' => number_format((float) $payment->total_amount, 0, ',', ' ') . ' FCFA',
            ],
        ]);
    }

    /**
     * Payment history.
     */
    public function history(Request $request): JsonResponse
    {
        $perPage = min(50, max(1, (int) $request->get('per_page', 20)));

        $payments = Payment::where('user_id', Auth::id())
            ->with('booking.residence')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => PaymentResource::collection($payments),
            'meta' => [
                'total' => $payments->total(),
                'per_page' => $payments->perPage(),
                'current_page' => $payments->currentPage(),
                'total_pages' => $payments->lastPage(),
            ],
        ]);
    }

    /**
     * Available Mobile Money operators.
     */
    public function operators(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->jekoService->getAvailableOperators(),
        ]);
    }

    /**
     * User's saved payment methods.
     */
    public function methods(): JsonResponse
    {
        $methods = PaymentMethod::where('user_id', Auth::id())
            ->with('provider')
            ->orderBy('is_default', 'desc')
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'provider_code' => $m->provider_code,
                'phone_number' => $m->phone_number,
                'label' => $m->label,
                'is_default' => (bool) $m->is_default,
                'provider_name' => $m->provider?->name,
            ]);

        return response()->json([
            'success' => true,
            'data' => $methods,
        ]);
    }

    /**
     * Save a payment method.
     */
    public function storeMethod(Request $request): JsonResponse
    {
        $request->validate([
            'provider_code' => ['required', 'string', 'exists:payment_providers,code'],
            'phone_number' => ['required', 'string', 'regex:/^[0-9]{8,10}$/'],
            'label' => ['nullable', 'string', 'max:50'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $method = $this->paymentService->savePaymentMethod(Auth::user(), $request->all());

        return response()->json([
            'success' => true,
            'message' => 'Méthode de paiement sauvegardée.',
            'data' => [
                'id' => $method->id,
                'provider_code' => $method->provider_code,
                'phone_number' => $method->phone_number,
                'is_default' => (bool) $method->is_default,
            ],
        ], 201);
    }

    /**
     * Delete a payment method.
     */
    public function deleteMethod(PaymentMethod $method): JsonResponse
    {
        if ($method->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé.',
                'error_code' => 'FORBIDDEN',
            ], 403);
        }

        $method->delete();

        return response()->json([
            'success' => true,
            'message' => 'Méthode supprimée.',
        ]);
    }
}
