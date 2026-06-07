<?php

declare(strict_types=1);

namespace App\Models;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Invoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'user_id',
        'payment_id',
        'booking_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'subtotal',
        'tax_amount',
        'tax_rate',
        'discount_amount',
        'total',
        'currency',
        'status',
        'client_name',
        'client_email',
        'client_phone',
        'client_address',
        'seller_name',
        'seller_email',
        'seller_phone',
        'seller_address',
        'seller_tax_id',
        'line_items',
        'pdf_path',
        'pdf_generated_at',
        'notes',
        'terms',
        'sent_at',
        'paid_at',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'line_items' => 'array',
        'pdf_generated_at' => 'datetime',
        'sent_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    // ===== CONSTANTS =====

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SENT = 'sent';
    public const STATUS_PAID = 'paid';
    public const STATUS_OVERDUE = 'overdue';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REFUNDED = 'refunded';

    // ===== BOOT =====

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            if (empty($invoice->uuid)) {
                $invoice->uuid = Str::uuid();
            }
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = static::generateInvoiceNumber();
            }
            if (empty($invoice->invoice_date)) {
                $invoice->invoice_date = now();
            }
        });
    }

    // ===== RELATIONSHIPS =====

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    // ===== SCOPES =====

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeSent($query)
    {
        return $query->where('status', self::STATUS_SENT);
    }

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', self::STATUS_OVERDUE);
    }

    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', [self::STATUS_DRAFT, self::STATUS_SENT, self::STATUS_OVERDUE]);
    }

    // ===== ACCESSORS =====

    public function getFormattedSubtotalAttribute(): string
    {
        return number_format((float) $this->subtotal, 0, ',', ' ').' '.$this->currency;
    }

    public function getFormattedTotalAttribute(): string
    {
        return number_format((float) $this->total, 0, ',', ' ').' '.$this->currency;
    }

    public function getFormattedTaxAttribute(): string
    {
        return number_format((float) $this->tax_amount, 0, ',', ' ').' '.$this->currency;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Brouillon',
            self::STATUS_SENT => 'Envoyée',
            self::STATUS_PAID => 'Payée',
            self::STATUS_OVERDUE => 'En retard',
            self::STATUS_CANCELLED => 'Annulée',
            self::STATUS_REFUNDED => 'Remboursée',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'gray',
            self::STATUS_SENT => 'blue',
            self::STATUS_PAID => 'green',
            self::STATUS_OVERDUE => 'red',
            self::STATUS_CANCELLED => 'gray',
            self::STATUS_REFUNDED => 'purple',
            default => 'gray',
        };
    }

    public function getPdfUrlAttribute(): ?string
    {
        if (!$this->pdf_path) {
            return null;
        }

        return Storage::url($this->pdf_path);
    }

    // ===== METHODS =====

    /**
     * Générer un numéro de facture unique
     */
    public static function generateInvoiceNumber(): string
    {
        $year = date('Y');
        $lastInvoice = static::whereYear('invoice_date', $year)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastInvoice
            ? ((int) substr($lastInvoice->invoice_number, -6)) + 1
            : 1;

        return sprintf('REZI-%s-%06d', $year, $sequence);
    }

    /**
     * Calculer les totaux
     */
    public function calculateTotals(): void
    {
        $subtotal = 0;

        foreach ($this->line_items ?? [] as $item) {
            $subtotal += ($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0);
        }

        $this->subtotal = $subtotal;
        $this->tax_amount = $subtotal * ($this->tax_rate / 100);
        $this->total = $subtotal + $this->tax_amount - ($this->discount_amount ?? 0);
    }

    /**
     * Ajouter une ligne de facture
     */
    public function addLineItem(string $description, int $quantity, float $unitPrice, ?string $reference = null): void
    {
        $items = $this->line_items ?? [];

        $items[] = [
            'description' => $description,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total' => $quantity * $unitPrice,
            'reference' => $reference,
        ];

        $this->line_items = $items;
        $this->calculateTotals();
    }

    /**
     * Générer le PDF
     */
    public function generatePdf(): string
    {
        $pdf = Pdf::loadView('invoices.pdf', [
            'invoice' => $this,
        ]);

        $filename = 'invoices/'.$this->invoice_number.'.pdf';
        Storage::put('public/'.$filename, $pdf->output());

        $this->update([
            'pdf_path' => $filename,
            'pdf_generated_at' => now(),
        ]);

        return $filename;
    }

    /**
     * Télécharger le PDF
     */
    public function downloadPdf()
    {
        if (!$this->pdf_path || !Storage::exists('public/'.$this->pdf_path)) {
            $this->generatePdf();
        }

        return Storage::download('public/'.$this->pdf_path, $this->invoice_number.'.pdf');
    }

    /**
     * Marquer comme envoyée
     */
    public function markAsSent(): void
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
        ]);
    }

    /**
     * Marquer comme payée
     */
    public function markAsPaid(): void
    {
        $this->update([
            'status' => self::STATUS_PAID,
            'paid_at' => now(),
        ]);
    }

    /**
     * Marquer comme en retard
     */
    public function markAsOverdue(): void
    {
        $this->update([
            'status' => self::STATUS_OVERDUE,
        ]);
    }

    /**
     * Vérifier si la facture est payée
     */
    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    /**
     * Vérifier si la facture est en retard
     */
    public function isOverdue(): bool
    {
        if ($this->status === self::STATUS_OVERDUE) {
            return true;
        }

        if ($this->due_date && $this->due_date->isPast() && !$this->isPaid()) {
            return true;
        }

        return false;
    }

    /**
     * Créer une facture à partir d'un paiement
     */
    public static function createFromPayment(Payment $payment, array $sellerInfo = []): self
    {
        $booking = $payment->booking;
        $user = $payment->user;

        $invoice = new self([
            'user_id' => $user->id,
            'payment_id' => $payment->id,
            'booking_id' => $booking?->id,
            'invoice_date' => now(),
            'due_date' => now(),
            'currency' => $payment->currency,
            'status' => self::STATUS_PAID,
            'paid_at' => $payment->completed_at ?? now(),

            // Client
            'client_name' => $user->name,
            'client_email' => $user->email,
            'client_phone' => $user->phone,
            'client_address' => $user->address,

            // Vendeur
            'seller_name' => $sellerInfo['name'] ?? config('rezi.company.name', 'Rezi Studio Meublé Faya SAS'),
            'seller_email' => $sellerInfo['email'] ?? config('rezi.company.email', 'contact@rezi.ci'),
            'seller_phone' => $sellerInfo['phone'] ?? config('rezi.company.phone', '+225 07 00 00 00 00'),
            'seller_address' => $sellerInfo['address'] ?? config('rezi.company.address', 'Abidjan, Côte d\'Ivoire'),
            'seller_tax_id' => $sellerInfo['tax_id'] ?? config('rezi.company.tax_id'),

            'tax_rate' => 18, // TVA CI
            'line_items' => [],
        ]);

        // Ajouter les lignes
        if ($booking) {
            $residence = $booking->residence;
            $nights = $booking->check_in->diffInDays($booking->check_out);

            $invoice->addLineItem(
                "Séjour - {$residence->title}",
                $nights,
                $booking->price_per_night ?? ($payment->amount / $nights),
                $booking->reference ?? null,
            );

            if ($booking->cleaning_fee > 0) {
                $invoice->addLineItem('Frais de ménage', 1, $booking->cleaning_fee);
            }

            if ($booking->service_fee > 0) {
                $invoice->addLineItem('Frais de service Rezi Studio Meublé Faya', 1, $booking->service_fee);
            }
        } else {
            $invoice->addLineItem(
                $payment->type_label,
                1,
                $payment->amount,
                $payment->reference,
            );
        }

        // Frais de paiement
        if ($payment->fee > 0) {
            $invoice->addLineItem('Frais de transaction', 1, $payment->fee);
        }

        $invoice->calculateTotals();
        $invoice->save();

        return $invoice;
    }
}
