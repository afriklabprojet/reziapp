<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'content',
        'type',
        'attachments',
        'metadata',
        'is_auto_reply',
        'template_id',
        'read_at',
        'delivered_at',
        'link_preview',
        // Sprint 3 — auto-translation
        'original_locale',
        'translated_content',
        'translated_locale',
        'translated_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'delivered_at' => 'datetime',
        'is_auto_reply' => 'boolean',
        'attachments' => 'array',
        'metadata' => 'array',
        'link_preview' => 'array',
        'translated_at' => 'datetime',
    ];

    /**
     * Types de messages
     */
    public const TYPE_TEXT = 'text';
    public const TYPE_IMAGE = 'image';
    public const TYPE_FILE = 'file';
    public const TYPE_DOCUMENT = 'document';
    public const TYPE_LOCATION = 'location';
    public const TYPE_SYSTEM = 'system';
    public const TYPE_AUTO_REPLY = 'auto_reply';
    public const TYPE_VOICE = 'voice';
    public const TYPE_GIF = 'gif';

    /**
     * Boot — déclenche la traduction async pour les messages texte (Sprint 3)
     */
    protected static function booted(): void
    {
        static::created(function (Message $message) {
            if ($message->type !== self::TYPE_TEXT || empty($message->content)) {
                return;
            }

            try {
                \App\Jobs\TranslateMessageJob::dispatch($message->id)->afterCommit();
            } catch (\Throwable $e) {
                // Ignore — traduction = bonus
            }
        });
    }

    /**
     * La conversation parente
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * L'expéditeur du message
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Template utilisé pour ce message
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(MessageTemplate::class, 'template_id');
    }

    /**
     * Les réactions sur ce message
     */
    public function reactions(): HasMany
    {
        return $this->hasMany(MessageReaction::class);
    }

    /**
     * Obtenir les réactions groupées par emoji
     */
    public function getGroupedReactions(): array
    {
        return $this->reactions
            ->groupBy('emoji')
            ->map(fn ($group) => [
                'emoji' => $group->first()->emoji,
                'count' => $group->count(),
                'users' => $group->pluck('user_id')->toArray(),
            ])
            ->values()
            ->toArray();
    }

    /**
     * Modifier le contenu du message
     */
    public function edit(string $newContent): void
    {
        $this->update([
            'content' => $newContent,
            'metadata' => array_merge($this->metadata ?? [], [
                'edited_at' => now()->toISOString(),
                'original_content' => $this->metadata['original_content'] ?? $this->content,
            ]),
        ]);
    }

    /**
     * Vérifier si le message a été modifié
     */
    public function isEdited(): bool
    {
        return isset($this->metadata['edited_at']);
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
     * Vérifier si le message est lu
     */
    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    /**
     * Vérifier si c'est un message système
     */
    public function isSystem(): bool
    {
        return $this->type === self::TYPE_SYSTEM;
    }

    /**
     * Vérifier si c'est une réponse automatique
     */
    public function isAutoReply(): bool
    {
        return $this->is_auto_reply || $this->type === self::TYPE_AUTO_REPLY;
    }

    /**
     * Ajouter une pièce jointe
     */
    public function addAttachment(array $attachment): void
    {
        $attachments = $this->attachments ?? [];
        $attachments[] = $attachment;
        $this->update(['attachments' => $attachments]);
    }

    /**
     * Vérifier si le message appartient à un utilisateur
     */
    public function belongsToUser(User $user): bool
    {
        return $this->sender_id === $user->id;
    }

    /**
     * Scope pour les messages non lus
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope pour les messages d'un type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
