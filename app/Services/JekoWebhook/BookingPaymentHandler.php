<?php

declare(strict_types=1);

namespace App\Services\JekoWebhook;

use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class BookingPaymentHandler extends AbstractJekoPaymentHandler
{
    public function __construct(
        private readonly PaymentService $paymentService,
    ) {}

    public function supports(string $reference): bool
    {
        return str_starts_with($reference, 'REZI-BK-');
    }

    protected function findByReference(string $reference): ?Payment
    {
        return Payment::where('reference', $reference)
            ->where('type', Payment::TYPE_BOOKING)
            ->first();
    }

    protected function wasAlreadyProcessed(mixed $subject): bool
    {
        return $subject->isCompleted();
    }

    protected function processSuccess(mixed $subject, TransactionCompletedData $data): bool
    {
        return DB::transaction(function () use ($subject, $data): bool {
            $lockedPayment = Payment::query()
                ->with('booking')
                ->whereKey($subject->getKey())
                ->lockForUpdate()
                ->first();

            if (! $lockedPayment || $lockedPayment->isCompleted()) {
                return false;
            }

            $paidAmountCents = $this->extractPaidAmountCents($data);
            $expectedAmountCents = $this->expectedAmountCents($lockedPayment);

            if ($paidAmountCents === null || $paidAmountCents !== $expectedAmountCents) {
                $lockedPayment->markAsFailed('Montant de paiement invalide');
                $lockedPayment->update([
                    'provider_response' => array_merge($lockedPayment->provider_response ?? [], [
                        'received_amount_cents' => $paidAmountCents,
                        'expected_amount_cents' => $expectedAmountCents,
                        'transaction_id' => $data->transactionId,
                        'payment_method' => $data->paymentMethod,
                        'executed_at' => $data->executedAt,
                    ]),
                ]);

                Log::warning('Jeko webhook: Booking payment amount mismatch', [
                    'payment_id' => $lockedPayment->id,
                    'booking_id' => $lockedPayment->booking_id,
                    'reference' => $data->reference,
                    'received_amount_cents' => $paidAmountCents,
                    'expected_amount_cents' => $expectedAmountCents,
                ]);

                return false;
            }

            $lockedPayment->markAsCompleted([
                'jeko_transaction_id' => $data->transactionId,
                'payment_method' => $data->paymentMethod,
                'executed_at' => $data->executedAt,
                'verified_amount_cents' => $paidAmountCents,
            ]);

            $this->paymentService->onPaymentSuccess($lockedPayment);

            return true;
        });
    }

    protected function processFailure(mixed $subject, TransactionCompletedData $data): void
    {
        $subject->markAsFailed('Paiement échoué via Jeko');
    }

    protected function missingLogMessage(): string
    {
        return 'Jeko webhook: No booking payment found for reference';
    }

    protected function missingLogContext(TransactionCompletedData $data): array
    {
        return [
            'reference' => $data->reference,
        ];
    }

    protected function alreadyProcessedLogMessage(): string
    {
        return 'Jeko webhook: Booking payment already completed';
    }

    protected function alreadyProcessedLogContext(mixed $subject, TransactionCompletedData $data): array
    {
        return [
            'payment_id' => $subject->id,
        ];
    }

    protected function successLogMessage(): string
    {
        return 'Jeko webhook: Booking payment confirmed';
    }

    protected function successLogContext(mixed $subject, TransactionCompletedData $data): array
    {
        return [
            'payment_id' => $subject->id,
            'booking_id' => $subject->booking_id,
        ];
    }

    private function expectedAmountCents(Payment $payment): int
    {
        $booking = $payment->booking;

        if ($booking && $booking->payment_split && (float) $booking->deposit_amount > 0) {
            return (int) round((float) $booking->deposit_amount * 100);
        }

        $amount = $booking ? (float) $booking->total_amount : (float) $payment->amount;

        return (int) round($amount * 100);
    }
}

