<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\SponsoredListing;
use App\Services\JekoPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class JekoCallbackController extends Controller
{
    public function __construct(
        protected JekoPaymentService $jekoService
    ) {}

    /**
     * Handle Jeko success redirect callback.
     *
     * User is redirected here after successful payment on Jeko.
     * Note: The actual payment confirmation comes via webhook —
     * this just provides a good UX with a polling mechanism.
     */
    public function success(Request $request)
    {
        $sponsoredId = $request->query('sponsored_id');

        if (! $sponsoredId) {
            return redirect()->route('owner.marketing.sponsored.index')
                ->with('error', 'Référence de paiement invalide.');
        }

        $sponsored = SponsoredListing::find($sponsoredId);

        if (! $sponsored) {
            return redirect()->route('owner.marketing.sponsored.index')
                ->with('error', 'Campagne sponsorisée introuvable.');
        }

        // If the webhook already confirmed it, redirect directly
        if ($sponsored->is_paid && $sponsored->payment_status === 'success') {
            return redirect()->route('owner.marketing.sponsored.show', $sponsored)
                ->with('success', 'Paiement confirmé ! Votre résidence est maintenant mise en avant.');
        }

        // Try to verify status via API if webhook hasn't arrived yet
        if ($sponsored->jeko_payment_id) {
            $statusResult = $this->jekoService->getPaymentStatus($sponsored->jeko_payment_id);

            if ($statusResult['success'] && ($statusResult['status'] === 'success' || $statusResult['status'] === 'completed')) {
                $sponsored->update([
                    'is_paid' => true,
                    'status' => 'active',
                    'payment_status' => 'success',
                    'paid_at' => now(),
                ]);

                return redirect()->route('owner.marketing.sponsored.show', $sponsored)
                    ->with('success', 'Paiement confirmé ! Votre résidence est maintenant mise en avant.');
            }
        }

        // Payment likely pending — show the waiting page
        return view('owner.marketing.sponsored.payment-processing', compact('sponsored'));
    }

    /**
     * Handle Jeko error redirect callback.
     *
     * User is redirected here if payment fails or is cancelled on Jeko.
     */
    public function error(Request $request)
    {
        $sponsoredId = $request->query('sponsored_id');

        if (! $sponsoredId) {
            return redirect()->route('owner.marketing.sponsored.index')
                ->with('error', 'Le paiement a échoué.');
        }

        $sponsored = SponsoredListing::find($sponsoredId);

        if ($sponsored) {
            $sponsored->update([
                'payment_status' => 'error',
            ]);

            Log::info('Jeko callback: User redirected to error URL', [
                'sponsored_id' => $sponsored->id,
            ]);

            return redirect()->route('owner.marketing.sponsored.payment', $sponsored)
                ->with('error', 'Le paiement a échoué ou a été annulé. Veuillez réessayer.');
        }

        return redirect()->route('owner.marketing.sponsored.index')
            ->with('error', 'Le paiement a échoué. Veuillez réessayer.');
    }

    /**
     * AJAX endpoint to check payment status (for polling from processing page).
     */
    public function checkStatus(Request $request, SponsoredListing $sponsored)
    {
        // Security: Ensure the user owns this listing
        if ($sponsored->user_id !== Auth::id()) {
            return response()->json(['error' => 'Non autorisé'], 403);
        }

        // Check DB first (webhook may have updated it)
        if ($sponsored->is_paid && $sponsored->payment_status === 'success') {
            return response()->json([
                'status' => 'success',
                'redirect' => route('owner.marketing.sponsored.show', $sponsored),
            ]);
        }

        // Try Jeko API if we have a payment ID
        if ($sponsored->jeko_payment_id) {
            $result = $this->jekoService->getPaymentStatus($sponsored->jeko_payment_id);

            if ($result['success'] && in_array($result['status'], ['success', 'completed'])) {
                $sponsored->update([
                    'is_paid' => true,
                    'status' => 'active',
                    'payment_status' => 'success',
                    'paid_at' => now(),
                ]);

                return response()->json([
                    'status' => 'success',
                    'redirect' => route('owner.marketing.sponsored.show', $sponsored),
                ]);
            }

            if ($result['success'] && $result['status'] === 'error') {
                return response()->json([
                    'status' => 'error',
                    'redirect' => route('owner.marketing.sponsored.payment', $sponsored),
                ]);
            }
        }

        return response()->json([
            'status' => 'pending',
        ]);
    }
}
