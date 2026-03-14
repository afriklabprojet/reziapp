<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\PaymentProvider;
use App\Services\JekoService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;
    protected JekoService $jekoService;

    public function __construct(PaymentService $paymentService, JekoService $jekoService)
    {
        $this->paymentService = $paymentService;
        $this->jekoService = $jekoService;
    }

    /**
     * Page de checkout pour une réservation
     */
    public function checkout(Booking $booking)
    {
        // Vérifier que l'utilisateur peut payer
        if ($booking->user_id !== Auth::id()) {
            abort(403, 'Vous ne pouvez pas payer cette réservation.');
        }

        if ($booking->isPaid()) {
            return redirect()->route('bookings.show', $booking)
                ->with('info', 'Cette réservation est déjà payée.');
        }

        $providers = $this->paymentService->getAvailablePaymentMethods();
        $operators = $this->jekoService->getAvailableOperators();

        $savedMethods = PaymentMethod::where('user_id', Auth::id())
            ->with('provider')
            ->orderBy('is_default', 'desc')
            ->get();

        return view('payments.checkout', [
            'booking' => $booking->load('residence.photos', 'residence.owner'),
            'providers' => $providers,
            'operators' => $operators,
            'savedMethods' => $savedMethods,
            'user' => Auth::user(),
        ]);
    }

    /**
     * Initier un paiement
     */
    public function initiate(Request $request, Booking $booking)
    {
        $request->validate([
            'phone_number' => ['required', 'string', 'regex:/^[0-9]{8,10}$/'],
            'operator' => ['nullable', 'string', 'in:orange_money,mtn_momo,moov_money,wave'],
            'save_method' => ['nullable', 'boolean'],
        ]);

        // Vérifier l'autorisation
        if ($booking->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé',
            ], 403);
        }

        try {
            // Créer le paiement
            $payment = $this->paymentService->createBookingPayment($booking, Auth::user(), [
                'provider' => 'jeko',
            ]);

            // Initier le paiement Mobile Money
            $result = $this->paymentService->initiatePayment(
                $payment,
                $request->phone_number,
                $request->operator,
            );

            if ($result['success']) {
                // Sauvegarder la méthode si demandé
                if ($request->save_method) {
                    $this->paymentService->savePaymentMethod(Auth::user(), [
                        'provider_code' => $request->operator ?? $this->jekoService->detectOperator($request->phone_number),
                        'phone_number' => $request->phone_number,
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'payment_id' => $payment->id,
                    'payment_uuid' => $payment->uuid,
                    'requires_otp' => $result['requires_otp'] ?? true,
                    'expires_at' => $result['expires_at'] ?? null,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code'] ?? 'INIT_FAILED',
            ], 400);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de l\'initiation du paiement. Veuillez réessayer.',
                'error_code' => 'INTERNAL_ERROR',
            ], 500);
        }
    }

    /**
     * Vérifier l'OTP
     */
    public function verifyOtp(Request $request, Payment $payment)
    {
        $request->validate([
            'otp' => ['required', 'string', 'size:6'],
        ]);

        // Vérifier l'autorisation
        if ($payment->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé',
            ], 403);
        }

        try {
            $result = $this->paymentService->verifyOtp($payment, $request->otp);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'redirect_url' => route('payments.success', $payment->uuid),
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
                'message' => 'Une erreur est survenue lors de la vérification. Veuillez réessayer.',
                'error_code' => 'INTERNAL_ERROR',
            ], 500);
        }
    }

    /**
     * Page de succès
     */
    public function success(string $uuid)
    {
        $payment = Payment::where('uuid', $uuid)
            ->where('user_id', Auth::id())
            ->with(['booking.residence', 'invoice'])
            ->firstOrFail();

        return view('payments.success', [
            'payment' => $payment,
        ]);
    }

    /**
     * Page d'échec
     */
    public function failed(string $uuid)
    {
        $payment = Payment::where('uuid', $uuid)
            ->where('user_id', Auth::id())
            ->with(['booking.residence'])
            ->firstOrFail();

        return view('payments.failed', [
            'payment' => $payment,
        ]);
    }

    /**
     * Page de retour après paiement
     */
    public function return(string $uuid)
    {
        $payment = Payment::where('uuid', $uuid)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        // Vérifier le statut
        if ($payment->isCompleted()) {
            return redirect()->route('payments.success', $uuid);
        }

        if ($payment->isFailed()) {
            return redirect()->route('payments.failed', $uuid);
        }

        // En attente - vérifier le statut auprès de Jeko
        $result = $this->jekoService->checkPaymentStatus($payment);

        if ($result['status'] === 'completed') {
            return redirect()->route('payments.success', $uuid);
        }

        if ($result['status'] === 'failed') {
            return redirect()->route('payments.failed', $uuid);
        }

        // Toujours en attente
        return view('payments.pending', [
            'payment' => $payment,
        ]);
    }

    /**
     * Webhook Jeko
     */
    public function webhook(Request $request)
    {
        $payload = $request->all();

        $result = $this->jekoService->handleWebhook($payload);

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Historique des paiements
     */
    public function history(Request $request)
    {
        $payments = $this->paymentService->getUserPayments(Auth::user(), [
            'status' => $request->status,
            'type' => $request->type,
            'from' => $request->from,
            'to' => $request->to,
            'per_page' => 15,
        ]);

        return view('payments.history', [
            'payments' => $payments,
            'filters' => $request->only(['status', 'type', 'from', 'to']),
        ]);
    }

    /**
     * Détails d'un paiement
     */
    public function show(Payment $payment)
    {
        if ($payment->user_id !== Auth::id()) {
            abort(403);
        }

        $payment->load(['booking.residence', 'provider', 'paymentMethod', 'transactions', 'invoice']);

        return view('payments.show', [
            'payment' => $payment,
        ]);
    }

    /**
     * Vérifier le statut d'un paiement
     */
    public function checkStatus(Payment $payment)
    {
        if ($payment->user_id !== Auth::id()) {
            return response()->json(['error' => 'Non autorisé'], 403);
        }

        $result = $this->jekoService->checkPaymentStatus($payment);

        return response()->json([
            'status' => $payment->fresh()->status,
            'status_label' => $payment->fresh()->status_label,
            'is_completed' => $payment->fresh()->isCompleted(),
            'is_failed' => $payment->fresh()->isFailed(),
            'provider_status' => $result['status'] ?? null,
        ]);
    }

    /**
     * Annuler un paiement
     */
    public function cancel(Request $request, Payment $payment)
    {
        if ($payment->user_id !== Auth::id()) {
            abort(403);
        }

        if (!$payment->canBeCancelled()) {
            return back()->with('error', 'Ce paiement ne peut plus être annulé.');
        }

        $this->paymentService->cancelPayment($payment, $request->reason ?? '');

        return redirect()->route('payments.history')
            ->with('success', 'Paiement annulé avec succès.');
    }

    // ===== MÉTHODES DE PAIEMENT =====

    /**
     * Liste des méthodes de paiement
     */
    public function methods()
    {
        $methods = PaymentMethod::where('user_id', Auth::id())
            ->with('provider')
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $providers = PaymentProvider::active()->forCountry('CI')->ordered()->get();

        return view('payments.methods', [
            'methods' => $methods,
            'providers' => $providers,
            'operators' => $this->jekoService->getAvailableOperators(),
        ]);
    }

    /**
     * Ajouter une méthode de paiement
     */
    public function storeMethod(Request $request)
    {
        $request->validate([
            'provider_code' => ['required', 'string', 'exists:payment_providers,code'],
            'phone_number' => ['required', 'string', 'regex:/^[0-9]{8,10}$/'],
            'label' => ['nullable', 'string', 'max:50'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $method = $this->paymentService->savePaymentMethod(Auth::user(), $request->all());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'method' => $method,
            ]);
        }

        return back()->with('success', 'Méthode de paiement ajoutée avec succès.');
    }

    /**
     * Supprimer une méthode de paiement
     */
    public function deleteMethod(PaymentMethod $method)
    {
        if ($method->user_id !== Auth::id()) {
            abort(403);
        }

        $method->delete();

        return back()->with('success', 'Méthode de paiement supprimée.');
    }

    /**
     * Définir comme méthode par défaut
     */
    public function setDefaultMethod(PaymentMethod $method)
    {
        if ($method->user_id !== Auth::id()) {
            abort(403);
        }

        $method->setAsDefault();

        return back()->with('success', 'Méthode de paiement définie par défaut.');
    }
}
