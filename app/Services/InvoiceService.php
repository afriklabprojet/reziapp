<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class InvoiceService
{
    /**
     * Générer une facture à partir d'un paiement
     */
    public function generateFromPayment(Payment $payment): Invoice
    {
        $booking = $payment->booking;
        $user = $payment->user;

        // Obtenir les infos du vendeur
        $sellerInfo = $this->getSellerInfo($booking);

        $invoice = Invoice::create([
            'user_id' => $user->id,
            'payment_id' => $payment->id,
            'booking_id' => $booking?->id,
            'invoice_date' => now(),
            'due_date' => now(),
            'currency' => $payment->currency,
            'status' => Invoice::STATUS_PAID,
            'paid_at' => $payment->completed_at ?? now(),

            // Client
            'client_name' => $user->name,
            'client_email' => $user->email,
            'client_phone' => $user->phone,
            'client_address' => $user->address,

            // Vendeur
            'seller_name' => $sellerInfo['name'],
            'seller_email' => $sellerInfo['email'],
            'seller_phone' => $sellerInfo['phone'],
            'seller_address' => $sellerInfo['address'],
            'seller_tax_id' => $sellerInfo['tax_id'],

            'tax_rate' => 18, // TVA Côte d'Ivoire
            'line_items' => [],
        ]);

        // Ajouter les lignes de facture
        $this->addLineItems($invoice, $payment, $booking);

        // Générer le PDF
        $invoice->generatePdf();

        return $invoice;
    }

    /**
     * Ajouter les lignes de facture
     */
    protected function addLineItems(Invoice $invoice, Payment $payment, ?Booking $booking): void
    {
        if ($booking && $booking->residence) {
            $residence = $booking->residence;
            $nights = $booking->check_in->diffInDays($booking->check_out);
            $pricePerNight = $booking->price_per_night ?? ($booking->total_amount / max($nights, 1));

            // Séjour
            $invoice->addLineItem(
                "Séjour - {$residence->title}",
                $nights,
                $pricePerNight,
                $booking->reference,
            );

            // Période
            $invoice->addLineItem(
                "Du {$booking->check_in->format('d/m/Y')} au {$booking->check_out->format('d/m/Y')}",
                1,
                0,
            );

            // Frais de ménage
            if (($booking->cleaning_fee ?? 0) > 0) {
                $invoice->addLineItem('Frais de ménage', 1, $booking->cleaning_fee);
            }

            // Frais de service
            if (($booking->service_fee ?? 0) > 0) {
                $invoice->addLineItem('Frais de service Rezi Studio Meublé Faya', 1, $booking->service_fee);
            }
        } else {
            // Paiement sans réservation
            $invoice->addLineItem(
                $payment->type_label,
                1,
                $payment->amount,
                $payment->reference,
            );
        }

        // Frais de transaction
        if ($payment->fee > 0) {
            $invoice->addLineItem('Frais de paiement électronique', 1, $payment->fee);
        }

        $invoice->calculateTotals();
        $invoice->save();
    }

    /**
     * Obtenir les informations du vendeur
     */
    protected function getSellerInfo(?Booking $booking): array
    {
        // Si la facture est au nom du propriétaire
        if ($booking && config('rezi.invoice_to_owner', false)) {
            $owner = $booking->residence?->user;

            if ($owner) {
                return [
                    'name' => $owner->name,
                    'email' => $owner->email,
                    'phone' => $owner->phone,
                    'address' => $owner->address ?? config('rezi.company.address', 'Abidjan, Côte d\'Ivoire'),
                    'tax_id' => $owner->tax_id ?? null,
                ];
            }
        }

        // Facture Rezi Studio Meublé Faya par défaut
        return [
            'name' => config('rezi.company.name', 'Rezi Studio Meublé Faya SAS'),
            'email' => config('rezi.company.email', 'facturation@rezi.ci'),
            'phone' => config('rezi.company.phone', '+225 07 00 00 00 00'),
            'address' => config('rezi.company.address', 'Abidjan, Cocody\nCôte d\'Ivoire'),
            'tax_id' => config('rezi.company.tax_id', 'CI-000000000'),
        ];
    }

    /**
     * Créer une facture manuelle
     */
    public function createManualInvoice(User $user, array $data): Invoice
    {
        $invoice = Invoice::create([
            'user_id' => $user->id,
            'invoice_date' => $data['invoice_date'] ?? now(),
            'due_date' => $data['due_date'] ?? now()->addDays(30),
            'currency' => $data['currency'] ?? 'XOF',
            'status' => Invoice::STATUS_DRAFT,

            'client_name' => $data['client_name'] ?? $user->name,
            'client_email' => $data['client_email'] ?? $user->email,
            'client_phone' => $data['client_phone'] ?? $user->phone,
            'client_address' => $data['client_address'] ?? null,

            'seller_name' => $data['seller_name'] ?? config('rezi.company.name'),
            'seller_email' => $data['seller_email'] ?? config('rezi.company.email'),
            'seller_phone' => $data['seller_phone'] ?? config('rezi.company.phone'),
            'seller_address' => $data['seller_address'] ?? config('rezi.company.address'),
            'seller_tax_id' => $data['seller_tax_id'] ?? config('rezi.company.tax_id'),

            'tax_rate' => $data['tax_rate'] ?? 18,
            'discount_amount' => $data['discount_amount'] ?? 0,
            'notes' => $data['notes'] ?? null,
            'terms' => $data['terms'] ?? $this->getDefaultTerms(),
            'line_items' => [],
        ]);

        // Ajouter les lignes
        foreach ($data['line_items'] ?? [] as $item) {
            $invoice->addLineItem(
                $item['description'],
                $item['quantity'] ?? 1,
                $item['unit_price'],
                $item['reference'] ?? null,
            );
        }

        return $invoice;
    }

    /**
     * Envoyer une facture par email
     */
    public function sendByEmail(Invoice $invoice): bool
    {
        // Générer le PDF si nécessaire
        if (!$invoice->pdf_path) {
            $invoice->generatePdf();
        }

        try {
            Mail::to($invoice->client_email)
                ->send(new \App\Mail\InvoiceMail($invoice));

            $invoice->markAsSent();

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to send invoice email', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Télécharger le PDF d'une facture
     */
    public function downloadPdf(Invoice $invoice)
    {
        if (!$invoice->pdf_path || !Storage::exists('public/'.$invoice->pdf_path)) {
            $this->regeneratePdf($invoice);
        }

        return Storage::download('public/'.$invoice->pdf_path, $invoice->invoice_number.'.pdf');
    }

    /**
     * Régénérer le PDF
     */
    public function regeneratePdf(Invoice $invoice): string
    {
        return $invoice->generatePdf();
    }

    /**
     * Obtenir les factures d'un utilisateur
     */
    public function getUserInvoices(User $user, array $filters = [])
    {
        $query = Invoice::where('user_id', $user->id)
            ->with(['payment', 'booking.residence']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['from'])) {
            $query->whereDate('invoice_date', '>=', $filters['from']);
        }

        if (!empty($filters['to'])) {
            $query->whereDate('invoice_date', '<=', $filters['to']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhere('client_name', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('invoice_date', 'desc')
            ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Obtenir les statistiques des factures
     */
    public function getStatistics(?int $userId = null): array
    {
        $query = Invoice::query();

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return [
            'total_invoices' => $query->count(),
            'total_amount' => (clone $query)->paid()->sum('total'),
            'pending_amount' => (clone $query)->unpaid()->sum('total'),
            'overdue_count' => (clone $query)->overdue()->count(),
            'by_status' => [
                'draft' => (clone $query)->draft()->count(),
                'sent' => (clone $query)->sent()->count(),
                'paid' => (clone $query)->paid()->count(),
                'overdue' => (clone $query)->overdue()->count(),
            ],
        ];
    }

    /**
     * Vérifier les factures en retard
     */
    public function checkOverdueInvoices(): int
    {
        $count = 0;

        Invoice::whereIn('status', [Invoice::STATUS_DRAFT, Invoice::STATUS_SENT])
            ->where('due_date', '<', now())
            ->each(function ($invoice) use (&$count) {
                $invoice->markAsOverdue();
                $count++;

                // Envoyer un rappel
                $invoice->user->notify(new \App\Notifications\InvoiceOverdue($invoice));
            });

        return $count;
    }

    /**
     * Obtenir les conditions par défaut
     */
    protected function getDefaultTerms(): string
    {
        return "Conditions de paiement :\n".
               "- Paiement à réception de la facture\n".
               "- Tout retard de paiement entraînera des pénalités de 10% par mois\n".
               '- Les réclamations doivent être formulées dans les 7 jours suivant réception';
    }

    /**
     * Annuler une facture
     */
    public function cancelInvoice(Invoice $invoice, string $reason = ''): bool
    {
        if ($invoice->isPaid()) {
            return false;
        }

        $invoice->update([
            'status' => Invoice::STATUS_CANCELLED,
            'notes' => $invoice->notes."\n\nAnnulée le ".now()->format('d/m/Y').
                      ($reason ? " - Raison: {$reason}" : ''),
        ]);

        return true;
    }
}
