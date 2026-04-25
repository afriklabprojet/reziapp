<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Refund extends Model
{
    use HasFactory;

    protected $fillable = [
        'cancellation_id',
        'booking_id',
        'user_id',
        'amount',
        'currency',
        'method',
        'status',
        'transaction_id',
        'payment_gateway_response',
        'processed_at',
        'error_message',
        'admin_notes',
        'requested_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_gateway_response' => 'array',
        'processed_at' => 'datetime',
    ];

    // ===== RELATIONSHIPS =====

    /**
     * Cancellation this refund is for
     */
    public function cancellation()
    {
        return $this->belongsTo(Cancellation::class);
    }

    /**
     * Booking this refund is for
     */
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * User receiving the refund
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ===== SCOPES =====

    /**
     * Pending refunds
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Processing refunds
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    /**
     * Completed refunds
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Failed refunds
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Needs processing
     */
    public function scopeNeedsProcessing($query)
    {
        return $query->whereIn('status', ['pending', 'processing']);
    }

    // ===== ACCESSORS =====

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'En attente',
            'processing' => 'En cours',
            'completed' => 'Effectué',
            'failed' => 'Échoué',
            'cancelled' => 'Annulé',
            default => $this->status,
        };
    }

    /**
     * Get status color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'yellow',
            'processing' => 'blue',
            'completed' => 'green',
            'failed' => 'red',
            'cancelled' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Get method label
     */
    public function getMethodLabelAttribute(): string
    {
        return match($this->method) {
            'original_payment' => 'Moyen de paiement original',
            'credit' => 'Crédit REZI',
            'bank_transfer' => 'Virement bancaire',
            'mobile_money' => 'Mobile Money',
            default => $this->method,
        };
    }

    /**
     * Get formatted amount
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format((float) $this->amount, 0, ',', ' ').' '.($this->currency ?? 'FCFA');
    }

    // ===== METHODS =====

    /**
     * Check if pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if processing
     */
    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    /**
     * Check if completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if can be retried
     */
    public function canBeRetried(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Start processing
     */
    public function startProcessing(): self
    {
        $this->update(['status' => 'processing']);

        return $this;
    }

    /**
     * Mark as completed
     */
    public function markCompleted(?string $transactionId = null): self
    {
        $this->update([
            'status' => 'completed',
            'processed_at' => now(),
            'transaction_id' => $transactionId ?? $this->transaction_id,
        ]);

        return $this;
    }

    /**
     * Mark as failed
     */
    public function markFailed(string $errorMessage): self
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);

        return $this;
    }

    /**
     * Cancel refund
     */
    public function cancel(?string $reason = null): self
    {
        $this->update([
            'status' => 'cancelled',
            'admin_notes' => $reason,
        ]);

        return $this;
    }

    /**
     * Retry failed refund
     */
    public function retry(): self
    {
        if ($this->canBeRetried()) {
            $this->update([
                'status' => 'pending',
                'error_message' => null,
            ]);
        }

        return $this;
    }

    // ===== STATIC HELPERS =====

    /**
     * Get available refund methods
     */
    public static function getAvailableMethods(): array
    {
        return [
            'original_payment' => 'Moyen de paiement original',
            'credit' => 'Crédit REZI (instantané)',
            'bank_transfer' => 'Virement bancaire (3-5 jours)',
            'mobile_money' => 'Mobile Money (24-48h)',
        ];
    }

    /**
     * Generate transaction ID
     */
    public static function generateTransactionId(): string
    {
        return 'REF-'.strtoupper(substr(md5(uniqid((string) mt_rand(), true)), 0, 12));
    }
}
