<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Payment extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'idempotency_key',
        'user_id',
        'booking_id',
        'payment_provider_id',
        'payment_method_id',
        'amount',
        'fee',
        'wallet_credit_used',
        'referral_credit_used',
        'total_amount',
        'currency',
        'type',
        'status',
        'reference',
        'provider_reference',
        'provider_transaction_id',
        'phone_number',
        'otp_code',
        'otp_expires_at',
        'metadata',
        'provider_response',
        'failure_reason',
        'initiated_at',
        'confirmed_at',
        'completed_at',
        'failed_at',
        'expires_at',
        'retry_count',
        'last_retry_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'wallet_credit_used' => 'decimal:2',
        'referral_credit_used' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'metadata' => 'array',
        'provider_response' => 'array',
        'otp_expires_at' => 'datetime',
        'initiated_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected $hidden = [
        'otp_code',
        'provider_response',
    ];

    // ===== CONSTANTS =====

    public const TYPE_BOOKING = 'booking';
    public const TYPE_DEPOSIT = 'deposit';
    public const TYPE_EXTENSION = 'extension';
    public const TYPE_PENALTY = 'penalty';
    public const TYPE_REFUND = 'refund';
    public const TYPE_PAYOUT = 'payout';
    public const TYPE_SUBSCRIPTION = 'subscription';
    public const TYPE_INSURANCE = 'insurance';
    public const TYPE_SERVICE = 'additional_service';
    public const TYPE_SPONSORED = 'sponsored_listing';

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REFUNDED = 'refunded';
    public const STATUS_PARTIAL_REFUND = 'partial_refund';

    // ===== BOOT =====

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            if (empty($payment->uuid)) {
                $payment->uuid = Str::uuid();
            }
            if (empty($payment->reference)) {
                $payment->reference = static::generateReference();
            }
            if (empty($payment->total_amount)) {
                $payment->total_amount = $payment->amount + ($payment->fee ?? 0);
            }
        });
    }

    // ===== RELATIONSHIPS =====

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(PaymentProvider::class, 'payment_provider_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
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

    public function scopeSuccessful($query)
    {
        return $query->whereIn('status', [self::STATUS_COMPLETED, self::STATUS_PARTIAL_REFUND]);
    }

    public function scopeForBooking($query)
    {
        return $query->where('type', self::TYPE_BOOKING);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    // ===== ACCESSORS =====

    public function getFormattedAmountAttribute(): string
    {
        return number_format((float) $this->amount, 0, ',', ' ').' '.$this->currency;
    }

    public function getFormattedTotalAttribute(): string
    {
        return number_format((float) $this->total_amount, 0, ',', ' ').' '.$this->currency;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'En attente',
            self::STATUS_PROCESSING => 'En cours',
            self::STATUS_COMPLETED => 'Complété',
            self::STATUS_FAILED => 'Échoué',
            self::STATUS_CANCELLED => 'Annulé',
            self::STATUS_REFUNDED => 'Remboursé',
            self::STATUS_PARTIAL_REFUND => 'Remboursement partiel',
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
            self::STATUS_REFUNDED => 'purple',
            self::STATUS_PARTIAL_REFUND => 'orange',
            default => 'gray',
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_BOOKING => 'Réservation',
            self::TYPE_DEPOSIT => 'Caution',
            self::TYPE_EXTENSION => 'Extension',
            self::TYPE_PENALTY => 'Pénalité',
            self::TYPE_REFUND => 'Remboursement',
            self::TYPE_PAYOUT => 'Virement',
            default => $this->type,
        };
    }

    // ===== METHODS =====

    /**
     * Générer une référence unique de façon atomique.
     * Utilise MAX(id) + 1 sur les paiements de l'année pour éviter les doublons
     * en cas de création concurrente. La contrainte UNIQUE sur `reference`
     * reste le filet de sécurité final.
     */
    public static function generateReference(): string
    {
        $year = date('Y');
        $maxId = (int) static::whereYear('created_at', $year)->max('id');

        return sprintf('PAY-%s-%06d', $year, $maxId + 1);
    }

    /**
     * Vérifier si le paiement est en attente
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Vérifier si le paiement est en cours
     */
    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    /**
     * Vérifier si le paiement est complété
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Vérifier si le paiement a échoué
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Vérifier si le paiement peut être annulé
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PROCESSING]);
    }

    /**
     * Vérifier si le paiement peut être remboursé
     */
    public function canBeRefunded(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Vérifier si l'OTP est expiré
     */
    public function isOtpExpired(): bool
    {
        return $this->otp_expires_at && $this->otp_expires_at->isPast();
    }

    /**
     * Vérifier l'OTP
     */
    public function verifyOtp(string $otp): bool
    {
        if ($this->isOtpExpired()) {
            return false;
        }

        return \Illuminate\Support\Facades\Hash::check($otp, $this->otp_code);
    }

    /**
     * Marquer comme en cours de traitement
     */
    public function markAsProcessing(?string $providerReference = null): void
    {
        $data = [
            'status' => self::STATUS_PROCESSING,
            'initiated_at' => now(),
        ];

        if ($providerReference) {
            $data['provider_reference'] = $providerReference;
        }

        $this->update($data);

        $this->logTransaction('processing', 'success');
    }

    /**
     * Marquer comme complété
     */
    public function markAsCompleted(array $providerResponse = []): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
            'confirmed_at' => now(),
            'provider_response' => array_merge($this->provider_response ?? [], $providerResponse),
        ]);

        // Mettre à jour la méthode de paiement
        if ($this->paymentMethod) {
            $this->paymentMethod->markAsUsed();
        }

        $this->logTransaction('success', 'success', $providerResponse);
    }

    /**
     * Marquer comme échoué
     */
    public function markAsFailed(string $reason, ?string $errorCode = null): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'failed_at' => now(),
            'failure_reason' => $reason,
        ]);

        $this->logTransaction('failure', 'failed', [
            'error_message' => $reason,
            'error_code' => $errorCode,
        ]);
    }

    /**
     * Marquer comme annulé
     */
    public function markAsCancelled(string $reason = 'Annulé par l\'utilisateur'): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'failure_reason' => $reason,
        ]);

        $this->logTransaction('cancelled', 'success', ['reason' => $reason]);
    }

    /**
     * Logger une transaction
     */
    public function logTransaction(string $type, string $status, array $data = []): PaymentTransaction
    {
        return $this->transactions()->create([
            'type' => $type,
            'status' => $status,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'provider_reference' => $this->provider_reference,
            'response_data' => $data,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Obtenir l'URL de callback
     */
    public function getCallbackUrl(): string
    {
        return route('payments.callback', ['uuid' => $this->uuid]);
    }

    /**
     * Obtenir l'URL de retour
     */
    public function getReturnUrl(): string
    {
        return route('payments.return', ['uuid' => $this->uuid]);
    }
}
