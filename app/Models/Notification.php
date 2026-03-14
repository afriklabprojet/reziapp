<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'body',
        'icon',
        'action_url',
        'data',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    /**
     * L'utilisateur destinataire
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Marquer comme lu
     */
    public function markAsRead(): void
    {
        if (!$this->read_at) {
            $this->update(['read_at' => now()]);
        }
    }

    /**
     * Vérifier si la notification est lue
     */
    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    /**
     * Scope pour les notifications non lues
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope par type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Créer une notification
     */
    public static function send(User $user, string $type, string $title, string $body, ?string $actionUrl = null, ?array $data = null): self
    {
        return self::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'icon' => self::getIconForType($type),
            'action_url' => $actionUrl,
            'data' => $data,
        ]);
    }

    /**
     * Obtenir l'icône pour un type de notification
     */
    protected static function getIconForType(string $type): string
    {
        return match($type) {
            'message' => 'chat',
            'review' => 'star',
            'residence' => 'home',
            'contact' => 'phone',
            'favorite' => 'heart',
            'system' => 'bell',
            default => 'bell',
        };
    }
}
