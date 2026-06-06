<?php

declare(strict_types=1);

namespace App\Services\JekoWebhook;

use App\Models\SponsoredListing;
use App\Services\SponsoredListingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class SponsoredListingPaymentHandler extends AbstractJekoPaymentHandler
{
    public function __construct(private readonly SponsoredListingService $sponsoredListingService) {}

    public function supports(string $reference): bool
    {
        return str_starts_with($reference, 'REZI-SP-');
    }

    protected function findByReference(string $reference): ?SponsoredListing
    {
        return SponsoredListing::where('jeko_reference', $reference)->first();
    }

    protected function wasAlreadyProcessed(mixed $subject): bool
    {
        return $subject->is_paid && $subject->payment_status === 'success';
    }

    protected function processSuccess(mixed $subject, TransactionCompletedData $data): bool
    {
        return DB::transaction(function () use ($subject, $data): bool {
            $listing = SponsoredListing::query()
                ->whereKey($subject->getKey())
                ->lockForUpdate()
                ->first();

            if (! $listing || ($listing->is_paid && $listing->payment_status === 'success')) {
                return false;
            }

            $paidAmountCents = $this->extractPaidAmountCents($data);
            $expectedAmountCents = (int) round((float) $listing->total_budget * 100);

            if ($paidAmountCents === null || $paidAmountCents !== $expectedAmountCents) {
                $this->sponsoredListingService->markPaymentAsFailed($listing);

                Log::warning('Jeko webhook: Sponsored listing amount mismatch', [
                    'sponsored_id' => $listing->id,
                    'reference' => $data->reference,
                    'received_amount_cents' => $paidAmountCents,
                    'expected_amount_cents' => $expectedAmountCents,
                    'transaction_id' => $data->transactionId,
                ]);

                return false;
            }

            $this->sponsoredListingService->markPaymentAsSuccessful(
                $listing,
                $data->transactionId,
                $data->paymentMethod,
                $data->executedAt ? Carbon::parse($data->executedAt) : null,
            );

            return true;
        });
    }

    protected function processFailure(mixed $subject, TransactionCompletedData $data): void
    {
        $this->sponsoredListingService->markPaymentAsFailed($subject);
    }

    protected function missingLogMessage(): string
    {
        return 'Jeko webhook: No sponsored listing found for reference';
    }

    protected function missingLogContext(TransactionCompletedData $data): array
    {
        return [
            'reference' => $data->reference,
            'transaction_id' => $data->transactionId,
        ];
    }

    protected function alreadyProcessedLogMessage(): string
    {
        return 'Jeko webhook: Payment already processed';
    }

    protected function alreadyProcessedLogContext(mixed $subject, TransactionCompletedData $data): array
    {
        return [
            'sponsored_id' => $subject->id,
            'reference' => $data->reference,
        ];
    }

    protected function successLogMessage(): string
    {
        return 'Jeko webhook: Sponsored listing payment confirmed';
    }

    protected function successLogContext(mixed $subject, TransactionCompletedData $data): array
    {
        return [
            'sponsored_id' => $subject->id,
            'reference' => $data->reference,
            'transaction_id' => $data->transactionId,
            'amount' => $data->rawData['amount']['amount'] ?? null,
        ];
    }

    protected function failureLogMessage(): ?string
    {
        return 'Jeko webhook: Sponsored payment failed';
    }

    protected function failureLogContext(mixed $subject, TransactionCompletedData $data): array
    {
        return [
            'sponsored_id' => $subject->id,
            'reference' => $data->reference,
            'status' => $data->status,
        ];
    }
}

