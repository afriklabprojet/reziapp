<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Support\Facades\Log;

/**
 * Observes Payment model lifecycle events.
 *
 * Responsibility: restore wallet/referral credits when a payment
 * transitions to a terminal failure state (failed or cancelled).
 * This ensures credits are never permanently lost due to a failed provider call.
 */
class PaymentObserver
{
    public function __construct(
        protected PaymentService $paymentService,
    ) {
    }

    /**
     * Restore credits when a payment moves to a terminal failure state.
     * Triggered after the payment row is persisted so the updated values are available.
     */
    public function updated(Payment $payment): void
    {
        $terminalFailureStatuses = [Payment::STATUS_FAILED, Payment::STATUS_CANCELLED];

        $statusJustFailed = $payment->wasChanged('status')
            && in_array($payment->status, $terminalFailureStatuses, true)
            && ! in_array($payment->getOriginal('status'), $terminalFailureStatuses, true);

        if (! $statusJustFailed) {
            return;
        }

        $walletCredit   = (float) $payment->wallet_credit_used;
        $referralCredit = (float) $payment->referral_credit_used;

        if ($walletCredit <= 0 && $referralCredit <= 0) {
            return;
        }

        try {
            $this->paymentService->restoreCredits($payment);
        } catch (\Throwable $e) {
            // Log and alert — credits must be restored manually if this fails
            Log::channel('critical')->error('PaymentObserver: credit restoration failed', [
                'payment_id'           => $payment->id,
                'status'               => $payment->status,
                'wallet_credit_used'   => $walletCredit,
                'referral_credit_used' => $referralCredit,
                'error'                => $e->getMessage(),
            ]);
        }
    }
}
