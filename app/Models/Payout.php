<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Payout extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'user_id',
        'payment_provider_id',
        'gross_amount',
        'platform_fee',
        'transfer_fee',
        'net_amount',
        'currency',
        'status',
        'reference',
        'provider_reference',
        'payout_method',
        'phone_number',
        'bank_name',
        'bank_account',
        'bank_iban',
        'period_start',
        'period_end',
        'booking_ids',
        'metadata',
        'failure_reason',
        'requested_at',
        'processed_at',
        'completed_at',
    ];

    protected $casts = [
        'gross_amount' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'transfer_fee' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'period_start' => 'date',
        'period_end' => 'date',
        'booking_ids' => 'array',
        'metadata' => 'array',
        'requested_at' => 'datetime',
        'processed_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // ===== CONSTANTS =====

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    public const METHOD_MOBILE_MONEY = 'mobile_money';
    public const METHOD_BANK_TRANSFER = 'bank_transfer';

    // ===== BOOT =====

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payout) {
            if (empty($payout->uuid)) {
                $payout->uuid = Str::uuid();
            }
            if (empty($payout->reference)) {
                $payout->reference = static::generateReference();
            }
            if (empty($payout->net_amount)) {
                $payout->net_amount = $payout->gross_amount - $payout->platform_fee - $payout->transfer_fee;
            }
        });
    }

    // ===== RELATIONSHIPS =====

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(PaymentProvider::class, 'payment_provider_id');
    }

    // ===== SCOPES =====

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', self::STATUS_PROCESSING);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    // ===== ACCESSORS =====

    public function getFormattedGrossAttribute(): string
    {
        return number_format($this->gross_amount, 0, ',', ' ').' '.$this->currency;
    }

    public function getFormattedNetAttribute(): string
    {
        return number_format($this->net_amount, 0, ',', ' ').' '.$this->currency;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'En attente',
            self::STATUS_PROCESSING => 'En cours',
            self::STATUS_COMPLETED => 'Complété',
            self::STATUS_FAILED => 'Échoué',
            self::STATUS_CANCELLED => 'Annulé',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'yellow',
            self::STATUS_PROCESSING => 'blue',
            self::STATUS_COMPLETED => 'green',
            self::STATUS_FAILED => 'red',
            self::STATUS_CANCELLED => 'gray',
            default => 'gray',
        };
    }

    public function getMethodLabelAttribute(): string
    {
        return match ($this->payout_method) {
            self::METHOD_MOBILE_MONEY => 'Mobile Money',
            self::METHOD_BANK_TRANSFER => 'Virement bancaire',
            default => $this->payout_method,
        };
    }

    public function getDestinationAttribute(): string
    {
        if ($this->payout_method === self::METHOD_MOBILE_MONEY) {
            return $this->phone_number ?? 'Non défini';
        }

        return ($this->bank_name ?? 'Banque').' - '.($this->bank_account ?? $this->bank_iban ?? 'N/A');
    }

    // ===== METHODS =====

    /**
     * Générer une référence unique
     */
    public static function generateReference(): string
    {
        $year = date('Y');
        $lastPayout = static::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastPayout
            ? ((int) substr($lastPayout->reference, -6)) + 1
            : 1;

        return sprintf('PAYOUT-%s-%06d', $year, $sequence);
    }

    /**
     * Vérifier si le payout est en attente
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Vérifier si le payout est complété
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Marquer comme en cours de traitement
     */
    public function markAsProcessing(): void
    {
        $this->update([
            'status' => self::STATUS_PROCESSING,
            'processed_at' => now(),
        ]);
    }

    /**
     * Marquer comme complété
     */
    public function markAsCompleted(?string $providerReference = null): void
    {
        $data = [
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
        ];

        if ($providerReference) {
            $data['provider_reference'] = $providerReference;
        }

        $this->update($data);

        // Mettre à jour le solde du propriétaire
        $this->updateOwnerBalance();

        // Notifier le propriétaire du paiement complété
        $this->user->notify(new \App\Notifications\PayoutCompleted(
            $this->net_amount,
            $this->payout_method,
            $this->reference,
        ));

        // Notification in-app
        \App\Models\Notification::send(
            $this->user,
            'system',
            'Versement effectué',
            'Votre versement de '.number_format($this->net_amount, 0, ',', ' ').' FCFA a été traité.',
            route('owner.earnings.index'),
            ['payout_id' => $this->id, 'amount' => $this->net_amount],
        );
    }

    /**
     * Marquer comme échoué
     */
    public function markAsFailed(string $reason): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'failure_reason' => $reason,
        ]);
    }

    /**
     * Mettre à jour le solde du propriétaire après le payout
     */
    private function updateOwnerBalance(): void
    {
        $balance = OwnerBalance::firstOrCreate(
            ['user_id' => $this->user_id],
            ['currency' => $this->currency],
        );

        $balance->decrement('available_balance', $this->gross_amount);
        $balance->increment('total_withdrawn', $this->net_amount);
        $balance->update(['last_payout_at' => now()]);
    }
}
