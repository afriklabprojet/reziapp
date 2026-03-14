<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
    protected $fillable = [
        'payment_id',
        'type',
        'status',
        'amount',
        'currency',
        'provider_reference',
        'request_data',
        'response_data',
        'ip_address',
        'user_agent',
        'error_message',
        'error_code',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'request_data' => 'array',
        'response_data' => 'array',
    ];

    // ===== CONSTANTS =====

    public const TYPE_INITIATE = 'initiate';
    public const TYPE_OTP_SENT = 'otp_sent';
    public const TYPE_OTP_VERIFIED = 'otp_verified';
    public const TYPE_PROCESSING = 'processing';
    public const TYPE_SUCCESS = 'success';
    public const TYPE_FAILURE = 'failure';
    public const TYPE_WEBHOOK = 'webhook';
    public const TYPE_REFUND = 'refund';
    public const TYPE_CANCELLED = 'cancelled';

    public const STATUS_PENDING = 'pending';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';

    // ===== RELATIONSHIPS =====

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    // ===== SCOPES =====

    public function scopeSuccessful($query)
    {
        return $query->where('status', self::STATUS_SUCCESS);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // ===== ACCESSORS =====

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_INITIATE => 'Initiation',
            self::TYPE_OTP_SENT => 'OTP envoyé',
            self::TYPE_OTP_VERIFIED => 'OTP vérifié',
            self::TYPE_PROCESSING => 'En traitement',
            self::TYPE_SUCCESS => 'Succès',
            self::TYPE_FAILURE => 'Échec',
            self::TYPE_WEBHOOK => 'Webhook',
            self::TYPE_REFUND => 'Remboursement',
            self::TYPE_CANCELLED => 'Annulation',
            default => $this->type,
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'En attente',
            self::STATUS_SUCCESS => 'Succès',
            self::STATUS_FAILED => 'Échec',
            default => $this->status,
        };
    }

    // ===== METHODS =====

    public function isSuccessful(): bool
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }
}
