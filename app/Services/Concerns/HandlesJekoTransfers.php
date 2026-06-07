<?php

namespace App\Services\Concerns;

use App\Models\Payout;
use App\Models\User;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait HandlesJekoTransfers
{
    private function executeEnabledTransfer(Payout $payout, User $owner): array
    {
        $identifier = $this->buildIdentifier($payout);
        $contactResult = $this->createOrGetContact($owner, $payout->payout_method, $identifier);
        $result = null;

        if (! $contactResult['success']) {
            $result = $this->failure($contactResult['error']);
        } else {
            $amountCents = (int) round($payout->net_amount);
            $result = $amountCents < 500
                ? $this->failure('Le montant minimum de transfert est de 5 FCFA.')
                : $this->sendTransferRequest($payout, $owner, $contactResult['contact_id'], $amountCents);
        }

        return $result;
    }

    private function sendTransferRequest(Payout $payout, User $owner, string $contactId, int $amountCents): array
    {
        $payload = [
            'storeId' => $this->storeId,
            'contactId' => $contactId,
            'amountCents' => $amountCents,
            'currency' => $this->currency,
            'description' => 'Versement Rezi Studio Meublé Faya — '.$payout->reference,
        ];
        $result = null;

        try {
            $updated = Payout::where('id', $payout->id)
                ->whereIn('status', ['pending', 'approved'])
                ->update(['status' => 'processing']);

            if (! $updated) {
                $result = $this->failure('Payout already being processed or completed.');
            } else {
                $payout->refresh();

                /** @var Response $response */
                $response = Http::withHeaders([
                    'X-API-KEY' => $this->apiKey,
                    'X-API-KEY-ID' => $this->apiKeyId,
                    'Content-Type' => 'application/json',
                ])->timeout(30)->post($this->baseUrl.'/partner_api/transfers', $payload);

                $result = $this->handleTransferResponse($payout, $owner, $contactId, $amountCents, $response);
            }
        } catch (\Throwable $e) {
            $payout->markAsFailed('Erreur de connexion : '.$e->getMessage());

            Log::error('Jeko transfer exception', [
                'payout_id' => $payout->id,
                'error' => $e->getMessage(),
            ]);

            $result = $this->failure('Service de paiement temporairement indisponible.');
        }

        return $result;
    }

    private function handleTransferResponse(Payout $payout, User $owner, string $contactId, int $amountCents, Response $response): array
    {
        $data = $response->json();

        if ($response->successful()) {
            return $this->completeTransfer($payout, $owner, $contactId, $amountCents, $data);
        }

        $errorMsg = $data['message'] ?? 'Erreur lors du transfert';
        $payout->markAsFailed($errorMsg);

        Log::error('Jeko transfer failed', [
            'payout_id' => $payout->id,
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return $this->failure($errorMsg);
    }

    private function completeTransfer(Payout $payout, User $owner, string $contactId, int $amountCents, array $data): array
    {
        $transferId = $data['id'] ?? null;
        $transferStatus = $data['status'] ?? 'pending';
        $fees = $data['fees']['amount'] ?? 0;

        $payout->update([
            'provider_reference' => $transferId,
            'transfer_fee' => $fees / 100,
            'metadata' => array_merge($payout->metadata ?? [], [
                'jeko_transfer_id' => $transferId,
                'jeko_status' => $transferStatus,
                'jeko_fees' => $fees,
                'jeko_contact_id' => $contactId,
            ]),
        ]);

        if ($transferStatus === 'success') {
            $payout->markAsCompleted($transferId);
        }

        Log::info('Jeko transfer created', [
            'payout_id' => $payout->id,
            'transfer_id' => $transferId,
            'status' => $transferStatus,
            'amount_cents' => $amountCents,
            'owner_id' => $owner->id,
        ]);

        return [
            'success' => true,
            'transfer_id' => $transferId,
            'status' => $transferStatus,
        ];
    }
}
