<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\BookingInsurance;
use App\Services\JekoPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InsuranceCallbackController extends Controller
{
    public function __construct(
        protected JekoPaymentService $jekoService,
    ) {
    }

    /**
     * Handle insurance payment success redirect.
     */
    public function success(Request $request)
    {
        $reference = $request->query('reference');

        if (!$reference) {
            return redirect()->route('bookings.index')
                ->with('error', 'Référence de paiement invalide.');
        }

        $insurance = BookingInsurance::where('payment_reference', $reference)
            ->with('booking.residence')
            ->first();

        if (!$insurance) {
            return redirect()->route('bookings.index')
                ->with('error', 'Assurance introuvable.');
        }

        // Already confirmed via webhook
        if ($insurance->status === 'active') {
            return redirect()->route('bookings.show', $insurance->booking)
                ->with('success', 'Assurance activée avec succès ! Votre réservation est protégée.');
        }

        // Try to verify via API if webhook hasn't arrived yet
        $jekoPaymentId = $insurance->metadata['jeko_payment_id'] ?? null;
        if ($jekoPaymentId) {
            $statusResult = $this->jekoService->getPaymentStatus($jekoPaymentId);

            if ($statusResult['success'] && in_array($statusResult['status'], ['success', 'completed'])) {
                $insurance->update([
                    'status' => 'active',
                    'metadata' => array_merge($insurance->metadata ?? [], [
                        'payment_confirmed_at' => now()->toIso8601String(),
                    ]),
                ]);

                return redirect()->route('bookings.show', $insurance->booking)
                    ->with('success', 'Assurance activée avec succès ! Votre réservation est protégée.');
            }
        }

        // Payment still pending
        return redirect()->route('bookings.show', $insurance->booking)
            ->with('info', 'Paiement de l\'assurance en cours de traitement. Vous recevrez une confirmation.');
    }

    /**
     * Handle insurance payment error redirect.
     */
    public function error(Request $request)
    {
        $reference = $request->query('reference');

        if (!$reference) {
            return redirect()->route('bookings.index')
                ->with('error', 'Le paiement de l\'assurance a échoué.');
        }

        $insurance = BookingInsurance::where('payment_reference', $reference)
            ->with('booking')
            ->first();

        if ($insurance) {
            Log::info('Insurance payment failed via callback', [
                'insurance_id' => $insurance->id,
                'reference' => $reference,
            ]);

            return redirect()->route('bookings.show', $insurance->booking)
                ->with('error', 'Le paiement de l\'assurance a échoué. Vous pouvez réessayer depuis votre réservation.');
        }

        return redirect()->route('bookings.index')
            ->with('error', 'Le paiement de l\'assurance a échoué. Veuillez réessayer.');
    }
}
