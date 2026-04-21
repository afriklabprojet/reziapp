<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'channel',
        'type',
        'title',
        'body',
        'data',
        'status',
        'error_message',
        'external_id',
        'sent_at',
        'delivered_at',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    /**
     * Statuts de notification
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_READ = 'read';
    public const STATUS_BOUNCED = 'bounced';

    /**
     * Types de notifications
     */
    public const TYPE_MESSAGE = 'message';
    public const TYPE_VISIT_REQUEST = 'visit_request';
    public const TYPE_VISIT_CONFIRMED = 'visit_confirmed';
    public const TYPE_PAYMENT = 'payment';
    public const TYPE_REVIEW = 'review';
    public const TYPE_PROMOTION = 'promotion';
    public const TYPE_SECURITY = 'security';
    public const TYPE_SYSTEM = 'system';

    /**
     * Canaux
     */
    public const CHANNEL_EMAIL = 'email';
    public const CHANNEL_PUSH = 'push';
    public const CHANNEL_SMS = 'sms';
    public const CHANNEL_DATABASE = 'database';
    public const CHANNEL_BROADCAST = 'broadcast';

    /**
     * L'utilisateur destinataire
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Marquer comme envoyé
     */
    public function markAsSent(): void
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
        ]);
    }

    /**
     * Marquer comme échoué
     */
    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $error,
        ]);
    }

    /**
     * Marquer comme délivré
     */
    public function markAsDelivered(): void
    {
        $this->update([
            'status' => self::STATUS_DELIVERED,
            'delivered_at' => now(),
        ]);
    }

    /**
     * Marquer comme lu
     */
    public function markAsRead(): void
    {
        $this->update([
            'status' => self::STATUS_READ,
            'read_at' => now(),
        ]);
    }

    /**
     * Vérifier si la notification a été lue
     */
    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    /**
     * Vérifier si la notification a été délivrée
     */
    public function isDelivered(): bool
    {
        return $this->delivered_at !== null;
    }

    /**
     * Créer un log de notification
     */
    public static function log(
        int $userId,
        string $channel,
        string $type,
        string $title,
        string $body,
        array $data = [],
    ): self {
        return self::create([
            'user_id' => $userId,
            'channel' => $channel,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data,
            'status' => self::STATUS_PENDING,
        ]);
    }

    /**
     * Scope par utilisateur
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope par canal
     */
    public function scopeChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    /**
     * Scope par type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope par statut
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope notifications non lues
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope notifications récentes
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope pour les échecs
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Obtenir les statistiques
     */
    public static function getStats(?int $userId = null, int $days = 30): array
    {
        $query = self::where('created_at', '>=', now()->subDays($days));

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return [
            'total' => $query->count(),
            'sent' => (clone $query)->where('status', self::STATUS_SENT)->count(),
            'failed' => (clone $query)->where('status', self::STATUS_FAILED)->count(),
            'read' => (clone $query)->whereNotNull('read_at')->count(),
            'delivered' => (clone $query)->whereNotNull('delivered_at')->count(),
            'by_channel' => [
                'email' => (clone $query)->where('channel', self::CHANNEL_EMAIL)->count(),
                'push' => (clone $query)->where('channel', self::CHANNEL_PUSH)->count(),
                'sms' => (clone $query)->where('channel', self::CHANNEL_SMS)->count(),
            ],
        ];
    }

    /**
     * Liste des types disponibles
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_MESSAGE => 'Message',
            self::TYPE_VISIT_REQUEST => 'Demande de visite',
            self::TYPE_VISIT_CONFIRMED => 'Visite confirmée',
            self::TYPE_PAYMENT => 'Paiement',
            self::TYPE_REVIEW => 'Avis',
            self::TYPE_PROMOTION => 'Promotion',
            self::TYPE_SECURITY => 'Sécurité',
            self::TYPE_SYSTEM => 'Système',
        ];
    }
}
