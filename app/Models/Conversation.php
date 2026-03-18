<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Conversation extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'residence_id',
        'user_id',
        'owner_id',
        'status',
        'pinned_at',
        'muted_at',
        'last_message_at',
        'unread_user_count',
        'unread_owner_count',
        'user_typing',
        'owner_typing',
        'user_last_seen_at',
        'owner_last_seen_at',
        'theme_color',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'user_last_seen_at' => 'datetime',
        'owner_last_seen_at' => 'datetime',
        'pinned_at' => 'datetime',
        'muted_at' => 'datetime',
        'muted_until' => 'datetime',
        'user_typing' => 'boolean',
        'owner_typing' => 'boolean',
    ];

    /**
     * Statuts de conversation
     */
    public const STATUS_ACTIVE = 'active';
    public const STATUS_ARCHIVED = 'archived';
    public const STATUS_BLOCKED = 'blocked';

    /**
     * Thèmes de couleur disponibles (style Messenger)
     */
    public const THEME_COLORS = [
        'orange' => ['bg' => 'bg-orange-500', 'text' => 'text-white', 'hex' => '#f97316'],
        'blue' => ['bg' => 'bg-blue-500', 'text' => 'text-white', 'hex' => '#3b82f6'],
        'green' => ['bg' => 'bg-emerald-500', 'text' => 'text-white', 'hex' => '#10b981'],
        'purple' => ['bg' => 'bg-purple-500', 'text' => 'text-white', 'hex' => '#8b5cf6'],
        'pink' => ['bg' => 'bg-pink-500', 'text' => 'text-white', 'hex' => '#ec4899'],
        'red' => ['bg' => 'bg-red-500', 'text' => 'text-white', 'hex' => '#ef4444'],
        'yellow' => ['bg' => 'bg-amber-500', 'text' => 'text-white', 'hex' => '#f59e0b'],
        'teal' => ['bg' => 'bg-teal-500', 'text' => 'text-white', 'hex' => '#14b8a6'],
    ];

    /**
     * La résidence concernée
     */
    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    /**
     * L'utilisateur (chercheur)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Le propriétaire
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Les messages de la conversation
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at', 'asc');
    }

    /**
     * Les documents partagés dans la conversation
     */
    public function sharedDocuments(): HasMany
    {
        return $this->hasMany(SharedDocument::class);
    }

    /**
     * Le dernier message
     */
    public function lastMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    /**
     * Messages non lus pour un utilisateur
     */
    public function unreadMessagesFor(User|int $user): int
    {
        $userId = $user instanceof User ? $user->id : $user;

        return $this->messages()
            ->whereNull('read_at')
            ->where('sender_id', '!=', $userId)
            ->count();
    }

    /**
     * L'autre participant de la conversation
     */
    public function getOtherParticipant(User $user): User
    {
        return $user->id === $this->user_id ? $this->owner : $this->user;
    }

    /**
     * Archiver la conversation
     */
    public function archive(): void
    {
        $this->update([
            'status' => self::STATUS_ARCHIVED,
        ]);
    }

    /**
     * Désarchiver la conversation
     */
    public function unarchive(): void
    {
        $this->update([
            'status' => self::STATUS_ACTIVE,
        ]);
    }

    /**
     * Bloquer la conversation
     */
    public function block(): void
    {
        $this->update([
            'status' => self::STATUS_BLOCKED,
        ]);
    }

    /**
     * Débloquer la conversation
     */
    public function unblock(): void
    {
        $this->update([
            'status' => self::STATUS_ACTIVE,
        ]);
    }

    /**
     * Vérifier si la conversation est archivée
     */
    public function isArchived(): bool
    {
        return $this->status === self::STATUS_ARCHIVED;
    }

    /**
     * Vérifier si la conversation est bloquée
     */
    public function isBlocked(): bool
    {
        return $this->status === self::STATUS_BLOCKED;
    }

    /**
     * Vérifier si la conversation est épinglée
     */
    public function isPinned(): bool
    {
        return $this->pinned_at !== null;
    }

    /**
     * Vérifier si la conversation est en sourdine
     */
    public function isMuted(): bool
    {
        return $this->muted_at !== null;
    }

    /**
     * Épingler la conversation
     */
    public function pin(): void
    {
        $this->update(['pinned_at' => now()]);
    }

    /**
     * Désépingler la conversation
     */
    public function unpin(): void
    {
        $this->update(['pinned_at' => null]);
    }

    /**
     * Mettre en sourdine la conversation
     */
    public function mute(\DateTimeInterface|null $until = null): void
    {
        $this->update([
            'muted_at' => now(),
            'muted_until' => $until,
        ]);
    }

    /**
     * Rétablir le son de la conversation
     */
    public function unmute(): void
    {
        $this->update(['muted_at' => null]);
    }

    /**
     * Scope pour les conversations d'un utilisateur
     */
    public function scopeForUser($query, User $user)
    {
        return $query->where(function ($q) use ($user) {
            $q->where('user_id', $user->id)
              ->orWhere('owner_id', $user->id);
        });
    }

    /**
     * Scope pour les conversations actives
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope pour les conversations archivées
     */
    public function scopeArchived($query)
    {
        return $query->where('status', self::STATUS_ARCHIVED);
    }

    /**
     * Scope pour les conversations bloquées
     */
    public function scopeBlocked($query)
    {
        return $query->where('status', self::STATUS_BLOCKED);
    }

    /**
     * Scope avec tri par dernier message
     */
    public function scopeOrdered($query)
    {
        return $query->orderByDesc('last_message_at');
    }
}
