<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Booking;
use App\Models\LeaseContract;
use App\Models\SecurityDeposit;
use App\Notifications\SecurityDepositReturnedNotification;

/**
 * Service de gestion des dépôts de garantie (caution).
 *
 * Responsabilités :
 *  - Création et enregistrement du dépôt
 *  - Marquage du paiement
 *  - Restitution totale ou partielle
 *  - Alertes délai légal (30 jours, droit ivoirien)
 */
class SecurityDepositService
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    // ===== CRÉATION =====

    public function createFromBooking(Booking $booking, float $amount, array $overrides = []): SecurityDeposit
    {
        $residence = $booking->residence;

        return SecurityDeposit::create(array_merge([
            'owner_id'     => $residence->user_id,
            'tenant_id'    => $booking->user_id,
            'residence_id' => $booking->residence_id,
            'booking_id'   => $booking->id,
            'amount'       => $amount,
            'currency'     => 'XOF',
            'status'       => SecurityDeposit::STATUS_PENDING,
        ], $overrides));
    }

    public function createFromContract(LeaseContract $contract, ?float $amountOverride = null): SecurityDeposit
    {
        return SecurityDeposit::create([
            'owner_id'          => $contract->owner_id,
            'tenant_id'         => $contract->tenant_id,
            'residence_id'      => $contract->residence_id,
            'lease_contract_id' => $contract->id,
            'amount'            => $amountOverride ?? $contract->deposit_amount,
            'currency'          => $contract->currency,
            'status'            => SecurityDeposit::STATUS_PENDING,
        ]);
    }

    public function create(array $data): SecurityDeposit
    {
        return SecurityDeposit::create(array_merge($data, [
            'status' => SecurityDeposit::STATUS_PENDING,
        ]));
    }

    // ===== PAIEMENT =====

    /**
     * Marquer le dépôt comme effectivement payé et en attente.
     */
    public function markAsPaid(SecurityDeposit $deposit, string $paymentMethod, string $paymentReference): SecurityDeposit
    {
        $deposit->update([
            'status'            => SecurityDeposit::STATUS_HELD,
            'payment_method'    => $paymentMethod,
            'payment_reference' => $paymentReference,
            'paid_at'           => now(),
            'return_deadline'   => now()->addDays(30),
        ]);

        return $deposit;
    }

    // ===== RESTITUTION =====

    /**
     * Restituer intégralement la caution.
     */
    public function returnFull(
        SecurityDeposit $deposit,
        string $paymentMethod,
        string $paymentReference,
    ): SecurityDeposit {
        if (! in_array($deposit->status, [SecurityDeposit::STATUS_HELD, SecurityDeposit::STATUS_PARTIAL_RETURN])) {
            throw new \RuntimeException('Ce dépôt ne peut pas être restitué (statut invalide).');
        }

        $deposit->update([
            'status'               => SecurityDeposit::STATUS_RETURNED,
            'returned_amount'      => $deposit->amount,
            'returned_at'          => now(),
            'return_payment_method' => $paymentMethod,
            'return_reference'     => $paymentReference,
        ]);

        $deposit->tenant->notify(new SecurityDepositReturnedNotification($deposit));

        return $deposit;
    }

    /**
     * Restitution partielle (avec retenue pour dommages).
     */
    public function returnPartial(
        SecurityDeposit $deposit,
        float $returnedAmount,
        string $deductionReason,
        array $deductionItems,
        string $paymentMethod,
        string $paymentReference,
    ): SecurityDeposit {
        if ($returnedAmount > (float) $deposit->amount) {
            throw new \InvalidArgumentException('Le montant restitué ne peut dépasser la caution.');
        }

        if ($returnedAmount < 0) {
            throw new \InvalidArgumentException('Le montant restitué doit être positif.');
        }

        $status = $returnedAmount === 0.0
            ? SecurityDeposit::STATUS_FORFEITED
            : SecurityDeposit::STATUS_PARTIAL_RETURN;

        $deposit->update([
            'status'                => $status,
            'returned_amount'       => $returnedAmount,
            'returned_at'           => now(),
            'return_payment_method' => $paymentMethod,
            'return_reference'      => $paymentReference,
            'deduction_reasons'     => $deductionReason,
            'deduction_items'       => $deductionItems,
        ]);

        $deposit->tenant->notify(new SecurityDepositReturnedNotification($deposit));

        return $deposit;
    }

    // ===== ALERTES =====

    /**
     * Lister les cautions en retard de restitution (> 30 jours).
     */
    public function getOverdueDeposits(): \Illuminate\Database\Eloquent\Collection
    {
        return SecurityDeposit::overdue()
            ->with(['owner', 'tenant', 'residence'])
            ->get();
    }

    /**
     * Statistiques pour un propriétaire.
     */
    public function getOwnerStats(int $ownerId): array
    {
        $deposits = SecurityDeposit::forOwner($ownerId)->get();

        return [
            'total'          => $deposits->count(),
            'held_amount'    => $deposits->where('status', SecurityDeposit::STATUS_HELD)->sum('amount'),
            'returned_total' => $deposits->where('status', SecurityDeposit::STATUS_RETURNED)->sum('amount'),
            'overdue_count'  => $deposits->filter(fn ($d) => $d->is_overdue)->count(),
        ];
    }
}
