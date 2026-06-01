<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\SponsoredListing;
use App\Services\JekoPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class JekoCallbackController extends Controller
{
    public function __construct(
        protected JekoPaymentService $jekoService,
    ) {
    }

    /**
     * Handle Jeko success redirect callback.
     *
     * User is redirected here after successful payment on Jeko.
     * Note: The actual payment confirmation comes via webhook —
     * this just provides a good UX with a polling mechanism.
     */
    public function success(Request $request)
    {
        $sponsored = $this->resolveSignedSponsoredListing($request);

        // If the webhook already confirmed it, redirect directly
        if ($sponsored->is_paid && $sponsored->payment_status === 'success') {
            return redirect()->route('owner.marketing.sponsored.show', $sponsored)
                ->with('success', 'Paiement confirmé ! Votre résidence est maintenant mise en avant.');
        }

        // Try to verify status via API if webhook hasn't arrived yet
        if ($sponsored->jeko_payment_id) {
            $statusResult = $this->jekoService->getPaymentStatus($sponsored->jeko_payment_id);

            if ($statusResult['success'] && ($statusResult['status'] === 'success' || $statusResult['status'] === 'completed')) {
                $duration = $sponsored->duration_days ?? 7;
                $sponsored->update([
                    'is_paid' => true,
                    'status' => 'active',
                    'payment_status' => 'success',
                    'paid_at' => now(),
                    'starts_at' => now(),
                    'ends_at' => now()->addDays($duration),
                ]);

                return redirect()->route('owner.marketing.sponsored.show', $sponsored)
                    ->with('success', 'Paiement confirmé ! Votre résidence est maintenant mise en avant.');
            }
        }

        // Payment likely pending — show the waiting page
        $checkStatusUrl = URL::temporarySignedRoute(
            'payment.jeko.check',
            now()->addDay(),
            [
                'sponsored' => $sponsored->getKey(),
                'reference' => $sponsored->jeko_reference,
            ],
            absolute: false,
        );

        return view('owner.marketing.sponsored.payment-processing', compact('sponsored', 'checkStatusUrl'));
    }

    /**
     * Handle Jeko error redirect callback.
     *
     * User is redirected here if payment fails or is cancelled on Jeko.
     */
    public function error(Request $request)
    {
        $sponsored = $this->resolveSignedSponsoredListing($request);

        $sponsored->update([
            'payment_status' => 'error',
        ]);

        Log::info('Jeko callback: User redirected to error URL', [
            'sponsored_id' => $sponsored->id,
        ]);

        return redirect()->route('owner.marketing.sponsored.payment', $sponsored)
            ->with('error', 'Le paiement a échoué ou a été annulé. Veuillez réessayer.');
    }

    /**
     * AJAX endpoint to check payment status (for polling from processing page).
     */
    public function checkStatus(Request $request, SponsoredListing $sponsored)
    {
        if (! $this->hasValidSignedAccess($request, $sponsored)) {
            return response()->json(['error' => 'Lien de vérification invalide'], 403);
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
                $duration = $sponsored->duration_days ?? 7;
                $sponsored->update([
                    'is_paid' => true,
                    'status' => 'active',
                    'payment_status' => 'success',
                    'paid_at' => now(),
                    'starts_at' => $sponsored->starts_at ?? now(),
                    'ends_at' => $sponsored->ends_at ?? now()->addDays($duration),
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

    protected function resolveSignedSponsoredListing(Request $request): SponsoredListing
    {
        $sponsoredId = $request->query('sponsored_id');

        if (! $sponsoredId) {
            abort(403, 'Référence de paiement invalide.');
        }

        $sponsored = SponsoredListing::findOrFail($sponsoredId);

        if (! $this->hasValidSignedAccess($request, $sponsored)) {
            Log::warning('Jeko callback: Invalid signed access', [
                'sponsored_id' => $sponsoredId,
                'ip' => $request->ip(),
            ]);

            abort(403, 'Lien de paiement invalide.');
        }

        return $sponsored;
    }

    protected function hasValidSignedAccess(Request $request, SponsoredListing $sponsored): bool
    {
        $reference = (string) $request->query('reference', '');

        return $request->hasValidRelativeSignature()
            && $reference !== ''
            && $sponsored->jeko_reference !== null
            && hash_equals((string) $sponsored->jeko_reference, $reference);
    }
}
