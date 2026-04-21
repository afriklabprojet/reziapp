<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageReaction extends Model
{
    protected $fillable = [
        'message_id',
        'user_id',
        'emoji',
    ];

    /**
     * Les emojis autorisés pour les réactions
     */
    public const ALLOWED_EMOJIS = ['👍', '❤️', '😂', '😮', '😢', '😡'];

    /**
     * Le message associé
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    /**
     * L'utilisateur qui a réagi
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
