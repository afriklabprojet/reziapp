<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\LeaseContractSigned;
use App\Models\Booking;
use App\Models\LeaseContract;
use App\Models\Residence;
use App\Models\User;
use App\Notifications\LeaseContractReadyNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

/**
 * Service de gestion des contrats de bail numériques.
 *
 * Responsabilités :
 *  - Création d'un contrat à partir d'une réservation ou manuellement
 *  - Génération du PDF via DomPDF
 *  - Workflow de signature (propriétaire puis locataire)
 *  - Résiliation
 */
class LeaseContractService
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    // ===== CRÉATION =====

    /**
     * Créer un contrat de bail depuis une réservation confirmée.
     */
    public function createFromBooking(Booking $booking, array $overrides = []): LeaseContract
    {
        $residence = $booking->residence;
        $nights    = $booking->check_in->diffInDays($booking->check_out);

        $data = array_merge([
            'owner_id'       => $residence->user_id,
            'tenant_id'      => $booking->user_id,
            'residence_id'   => $booking->residence_id,
            'booking_id'     => $booking->id,
            'start_date'     => $booking->check_in,
            'end_date'       => $booking->check_out,
            'lease_type'     => $nights > 30 ? LeaseContract::TYPE_MONTHLY : LeaseContract::TYPE_SHORT_TERM,
            'monthly_rent'   => $booking->total_amount,
            'deposit_amount' => 0,
            'currency'       => 'XOF',
            'status'         => LeaseContract::STATUS_DRAFT,
        ], $overrides);

        $contract = LeaseContract::create($data);

        return $contract;
    }

    /**
     * Créer un contrat manuellement.
     */
    public function create(array $data): LeaseContract
    {
        $data['status'] = LeaseContract::STATUS_DRAFT;

        return LeaseContract::create($data);
    }

    // ===== GÉNÉRATION PDF =====

    /**
     * Générer le PDF du contrat et le stocker.
     */
    public function generatePdf(LeaseContract $contract): LeaseContract
    {
        $contract->load(['owner', 'tenant', 'residence']);

        $pdf = Pdf::loadView('pdf.lease-contract', ['contract' => $contract])
            ->setPaper('A4', 'portrait');

        $path = "lease-contracts/{$contract->reference}.pdf";
        Storage::disk('local')->put($path, $pdf->output());

        $contract->update([
            'pdf_path'           => $path,
            'pdf_generated_at'   => now(),
        ]);

        return $contract;
    }

    /**
     * Télécharger le PDF (retourne le contenu brut).
     */
    public function downloadPdf(LeaseContract $contract): string
    {
        if (! $contract->pdf_path || ! Storage::disk('local')->exists($contract->pdf_path)) {
            $this->generatePdf($contract);
            $contract->refresh();
        }

        return Storage::disk('local')->get($contract->pdf_path);
    }

    // ===== WORKFLOW DE SIGNATURE =====

    /**
     * Envoyer le contrat au locataire pour signature.
     */
    public function sendToTenant(LeaseContract $contract): LeaseContract
    {
        if ($contract->status !== LeaseContract::STATUS_DRAFT) {
            throw new \RuntimeException('Le contrat doit être à l\'état brouillon pour être envoyé.');
        }

        // Régénérer le PDF à jour
        $this->generatePdf($contract);

        $contract->update(['status' => LeaseContract::STATUS_PENDING_TENANT]);

        // Notifier le locataire
        $contract->tenant->notify(new LeaseContractReadyNotification($contract, 'tenant'));

        return $contract;
    }

    /**
     * Signature électronique du contrat.
     */
    public function sign(LeaseContract $contract, User $signer, string $ip): LeaseContract
    {
        $isOwner  = $signer->id === $contract->owner_id;
        $isTenant = $signer->id === $contract->tenant_id;

        if (! $isOwner && ! $isTenant) {
            throw new \RuntimeException('Vous n\'êtes pas autorisé à signer ce contrat.');
        }

        if ($isOwner && ! $contract->canBeSignedByOwner()) {
            throw new \RuntimeException('Le propriétaire a déjà signé ou le contrat ne peut pas être signé.');
        }

        if ($isTenant && ! $contract->canBeSignedByTenant()) {
            throw new \RuntimeException('Le locataire a déjà signé ou le contrat ne peut pas être signé.');
        }

        if ($isOwner) {
            $contract->update([
                'owner_signed_at'    => now(),
                'owner_signature_ip' => $ip,
                'status'             => LeaseContract::STATUS_PENDING_TENANT,
            ]);
            $contract->tenant->notify(new LeaseContractReadyNotification($contract, 'tenant'));
        }

        if ($isTenant) {
            $contract->update([
                'tenant_signed_at'    => now(),
                'tenant_signature_ip' => $ip,
                'status'              => LeaseContract::STATUS_ACTIVE,
            ]);

            // Le contrat est maintenant actif — notifier les deux parties
            event(new LeaseContractSigned($contract));
        }

        // Régénérer le PDF avec les horodatages de signature
        $this->generatePdf($contract->fresh());

        return $contract->fresh();
    }

    // ===== RÉSILIATION =====

    /**
     * Résilier un contrat actif.
     */
    public function terminate(LeaseContract $contract, User $initiator, string $reason): LeaseContract
    {
        if ($contract->status !== LeaseContract::STATUS_ACTIVE) {
            throw new \RuntimeException('Seul un contrat actif peut être résilié.');
        }

        $terminatedBy = $initiator->id === $contract->owner_id ? 'owner' : 'tenant';

        $contract->update([
            'status'            => LeaseContract::STATUS_TERMINATED,
            'terminated_at'     => today(),
            'termination_reason' => $reason,
            'terminated_by'     => $terminatedBy,
        ]);

        return $contract;
    }

    // ===== STATISTIQUES =====

    public function getOwnerStats(int $ownerId): array
    {
        $contracts = LeaseContract::forOwner($ownerId)->get();

        return [
            'total'             => $contracts->count(),
            'active'            => $contracts->where('status', LeaseContract::STATUS_ACTIVE)->count(),
            'pending_signature' => $contracts->whereIn('status', [
                LeaseContract::STATUS_PENDING_TENANT,
                LeaseContract::STATUS_PENDING_OWNER,
            ])->count(),
            'terminated'        => $contracts->where('status', LeaseContract::STATUS_TERMINATED)->count(),
            'monthly_rent_total' => $contracts
                ->where('status', LeaseContract::STATUS_ACTIVE)
                ->sum('monthly_rent'),
        ];
    }
}
