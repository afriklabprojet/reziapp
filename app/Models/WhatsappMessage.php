<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappMessage extends Model
{
    protected $fillable = [
        'user_id',
        'recipient_phone',
        'message_type',
        'template_name',
        'message_content',
        'whatsapp_message_id',
        'status',
        'sent_at',
        'delivered_at',
        'read_at',
        'failed_reason',
        'metadata',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'metadata' => 'array',
    ];

    // Message types
    public const TYPE_BOOKING_NOTIFICATION = 'booking_notification';
    public const TYPE_MESSAGE_NOTIFICATION = 'message_notification';
    public const TYPE_CHECK_IN_REMINDER = 'check_in_reminder';
    public const TYPE_PAYMENT_CONFIRMATION = 'payment_confirmation';
    public const TYPE_REVIEW_REQUEST = 'review_request';
    public const TYPE_PROMOTIONAL = 'promotional';
    public const TYPE_CUSTOM = 'custom';

    // Status
    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT = 'sent';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_READ = 'read';
    public const STATUS_FAILED = 'failed';

    // ===== RELATIONSHIPS =====

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ===== SCOPES =====

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeSent($query)
    {
        return $query->where('status', self::STATUS_SENT);
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', self::STATUS_DELIVERED);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('message_type', $type);
    }

    // ===== HELPERS =====

    public function markAsSent(string $whatsappMessageId): self
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'whatsapp_message_id' => $whatsappMessageId,
            'sent_at' => now(),
        ]);

        return $this;
    }

    public function markAsDelivered(): self
    {
        $this->update([
            'status' => self::STATUS_DELIVERED,
            'delivered_at' => now(),
        ]);

        return $this;
    }

    public function markAsRead(): self
    {
        $this->update([
            'status' => self::STATUS_READ,
            'read_at' => now(),
        ]);

        return $this;
    }

    public function markAsFailed(string $reason): self
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'failed_reason' => $reason,
        ]);

        return $this;
    }

    /**
     * Créer un enregistrement pour un message WhatsApp
     */
    public static function log(
        ?int $userId,
        string $recipientPhone,
        string $messageType,
        string $content,
        ?string $templateName = null,
        array $metadata = [],
    ): self {
        return self::create([
            'user_id' => $userId,
            'recipient_phone' => $recipientPhone,
            'message_type' => $messageType,
            'template_name' => $templateName,
            'message_content' => $content,
            'status' => self::STATUS_PENDING,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Obtenir les statistiques d'envoi
     */
    public static function getStats(?int $userId = null, int $days = 30): array
    {
        $query = self::where('created_at', '>=', now()->subDays($days));

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $total = $query->count();
        $sent = (clone $query)->where('status', '!=', self::STATUS_PENDING)->count();
        $delivered = (clone $query)->whereIn('status', [self::STATUS_DELIVERED, self::STATUS_READ])->count();
        $read = (clone $query)->where('status', self::STATUS_READ)->count();
        $failed = (clone $query)->where('status', self::STATUS_FAILED)->count();

        return [
            'total' => $total,
            'sent' => $sent,
            'delivered' => $delivered,
            'read' => $read,
            'failed' => $failed,
            'delivery_rate' => $sent > 0 ? round(($delivered / $sent) * 100, 1) : 0,
            'read_rate' => $delivered > 0 ? round(($read / $delivered) * 100, 1) : 0,
        ];
    }
}
