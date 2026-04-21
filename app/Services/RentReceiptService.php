<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Booking;
use App\Models\LeaseContract;
use App\Models\RentReceipt;
use App\Notifications\RentReceiptNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Service de génération et d'envoi des quittances de loyer.
 *
 * Responsabilités :
 *  - Création manuelle ou automatique depuis réservation/contrat
 *  - Génération du PDF (DomPDF)
 *  - Envoi par email et/ou WhatsApp
 *  - Récapitulatif mensuel pour le propriétaire
 */
class RentReceiptService
{
    public function __construct(
        private readonly WhatsAppService $whatsApp,
        private readonly NotificationService $notifications,
    ) {
    }

    // ===== CRÉATION =====

    /**
     * Créer automatiquement une quittance depuis une réservation payée.
     */
    public function createFromBooking(Booking $booking, array $overrides = []): RentReceipt
    {
        $residence = $booking->residence;

        $data = array_merge([
            'owner_id'        => $residence->owner_id,
            'tenant_id'       => $booking->user_id,
            'residence_id'    => $booking->residence_id,
            'booking_id'      => $booking->id,
            'period_start'    => $booking->check_in,
            'period_end'      => $booking->check_out,
            'rent_amount'     => $booking->total_amount,
            'charges_amount'  => 0,
            'total_amount'    => $booking->total_amount,
            'currency'        => 'XOF',
            'payment_method'  => $booking->payment_method ?? 'mobile_money',
            'payment_date'    => $booking->updated_at ?? now(),
            'is_paid'         => true,
        ], $overrides);

        $receipt = RentReceipt::create($data);

        // Générer automatiquement le PDF
        $this->generatePdf($receipt);

        return $receipt;
    }

    /**
     * Créer une quittance depuis un contrat de bail (loyer mensuel).
     */
    public function createFromContract(
        LeaseContract $contract,
        Carbon $periodStart,
        ?Carbon $periodEnd = null,
        array $charges = [],
    ): RentReceipt {
        $periodEnd  = $periodEnd ?? $periodStart->copy()->endOfMonth();
        $chargesSum = array_sum(array_column($charges, 'amount'));

        $receipt = RentReceipt::create([
            'owner_id'         => $contract->owner_id,
            'tenant_id'        => $contract->tenant_id,
            'residence_id'     => $contract->residence_id,
            'lease_contract_id' => $contract->id,
            'period_start'     => $periodStart->startOfDay(),
            'period_end'       => $periodEnd->endOfDay(),
            'rent_amount'      => $contract->monthly_rent,
            'charges_amount'   => $chargesSum,
            'total_amount'     => $contract->monthly_rent + $chargesSum,
            'currency'         => $contract->currency,
            'payment_day'      => $periodStart->day($contract->payment_day),
            'is_paid'          => true,
            'charges_detail'   => $charges,
            'payment_date'     => now()->toDateString(),
        ]);

        $this->generatePdf($receipt);

        return $receipt;
    }

    /**
     * Créer manuellement une quittance.
     */
    public function createManual(array $data): RentReceipt
    {
        $data['total_amount'] = ($data['rent_amount'] ?? 0) + ($data['charges_amount'] ?? 0);
        $data['is_paid']      = true;

        $receipt = RentReceipt::create($data);
        $this->generatePdf($receipt);

        return $receipt;
    }

    // ===== GÉNÉRATION PDF =====

    public function generatePdf(RentReceipt $receipt): RentReceipt
    {
        $receipt->load(['owner', 'tenant', 'residence']);

        $pdf = Pdf::loadView('pdf.rent-receipt', ['receipt' => $receipt])
            ->setPaper('A4', 'portrait');

        $path = "rent-receipts/{$receipt->reference}.pdf";
        Storage::disk('local')->put($path, $pdf->output());

        $receipt->update([
            'pdf_path'          => $path,
            'pdf_generated_at'  => now(),
        ]);

        return $receipt->fresh();
    }

    public function downloadPdf(RentReceipt $receipt): string
    {
        if (! $receipt->pdf_path || ! Storage::disk('local')->exists($receipt->pdf_path)) {
            $this->generatePdf($receipt);
            $receipt->refresh();
        }

        return Storage::disk('local')->get($receipt->pdf_path);
    }

    // ===== ENVOI =====

    /**
     * Envoyer la quittance au locataire (email + WhatsApp optionnel).
     */
    public function sendToTenant(RentReceipt $receipt, bool $viaWhatsApp = false): void
    {
        $receipt->load(['tenant', 'owner', 'residence']);

        // Email via notification
        $receipt->tenant->notify(new RentReceiptNotification($receipt));
        $receipt->markAsSent('email');

        // WhatsApp optionnel
        if ($viaWhatsApp && $receipt->tenant->phone) {
            $message = $this->buildWhatsAppMessage($receipt);
            $this->whatsApp->sendMessage($receipt->tenant->phone, $message);
            $receipt->markAsSent('whatsapp');
        }
    }

    // ===== RÉCAPITULATIF =====

    /**
     * Revenus mensuels agrégés pour le propriétaire (12 derniers mois).
     */
    public function getMonthlyRevenueSummary(int $ownerId, int $months = 12): array
    {
        $start = now()->subMonths($months - 1)->startOfMonth();

        $data = RentReceipt::forOwner($ownerId)
            ->where('period_start', '>=', $start)
            ->where('is_paid', true)
            ->selectRaw(
                'YEAR(period_start) as year, MONTH(period_start) as month,
                 SUM(rent_amount) as rent_total, SUM(charges_amount) as charges_total,
                 SUM(total_amount) as grand_total, COUNT(*) as count',
            )
            ->groupByRaw('YEAR(period_start), MONTH(period_start)')
            ->orderByRaw('YEAR(period_start), MONTH(period_start)')
            ->get()
            ->keyBy(fn ($row) => "{$row->year}-{$row->month}");

        // Combler les mois sans données
        $result = [];
        for ($i = 0; $i < $months; $i++) {
            $date = now()->subMonths($months - 1 - $i)->startOfMonth();
            $key  = "{$date->year}-{$date->month}";
            $row  = $data[$key] ?? null;

            $result[] = [
                'label'        => $date->translatedFormat('M Y'),
                'year'         => $date->year,
                'month'        => $date->month,
                'rent_total'   => (float) ($row?->rent_total ?? 0),
                'charges_total' => (float) ($row?->charges_total ?? 0),
                'grand_total'  => (float) ($row?->grand_total ?? 0),
                'count'        => (int) ($row?->count ?? 0),
            ];
        }

        return $result;
    }

    // ===== PRIVÉ =====

    private function buildWhatsAppMessage(RentReceipt $receipt): string
    {
        return "🏠 *Quittance de loyer - REZI*\n\n"
            ."Bonjour {$receipt->tenant->name},\n\n"
            ."Votre quittance de loyer est disponible :\n"
            ."• *Référence :* {$receipt->reference}\n"
            ."• *Période :* {$receipt->period_label}\n"
            .'• *Montant :* '.number_format($receipt->total_amount, 0, ',', ' ')." {$receipt->currency}\n"
            ."• *Résidence :* {$receipt->residence->title}\n\n"
            ."Connectez-vous à votre espace REZI pour télécharger le document.\n\n"
            .'_REZI – Votre plateforme de résidences meublées à Abidjan_';
    }
}
