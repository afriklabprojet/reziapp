<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\SponsoredListing;
use App\Services\JekoPaymentService;
use App\Services\SponsoredListingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class JekoCallbackController extends Controller
{
    public function __construct(
        protected JekoPaymentService $jekoService,
        protected SponsoredListingService $sponsoredListingService,
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

        if ($this->resolvePaymentStatusOutcome($sponsored) === 'success') {
            return redirect()->route('owner.marketing.sponsored.show', $sponsored)
                ->with('success', 'Paiement confirmé ! Votre résidence est maintenant mise en avant.');
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

        $this->sponsoredListingService->markPaymentAsFailed($sponsored);

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

        return response()->json($this->buildCheckStatusPayload(
            $this->resolvePaymentStatusOutcome($sponsored),
            $sponsored,
        ));
    }

    protected function resolvePaymentStatusOutcome(SponsoredListing $sponsored): string
    {
        $status = 'pending';

        if ($sponsored->is_paid && $sponsored->payment_status === 'success') {
            $status = 'success';
        } elseif ($sponsored->jeko_payment_id) {
            $result = $this->jekoService->getPaymentStatus($sponsored->jeko_payment_id);

            if ($result['success'] ?? false) {
                $paymentStatus = $result['status'] ?? null;

                if (in_array($paymentStatus, ['success', 'completed'], true)) {
                    $this->sponsoredListingService->markPaymentAsSuccessful($sponsored);
                    $status = 'success';
                } elseif ($paymentStatus === 'error') {
                    $status = 'error';
                }
            }
        }

        return $status;
    }

    protected function buildCheckStatusPayload(string $status, SponsoredListing $sponsored): array
    {
        return match ($status) {
            'success' => [
                'status' => 'success',
                'redirect' => route('owner.marketing.sponsored.show', $sponsored),
            ],
            'error' => [
                'status' => 'error',
                'redirect' => route('owner.marketing.sponsored.payment', $sponsored),
            ],
            default => [
                'status' => 'pending',
            ],
        };
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
