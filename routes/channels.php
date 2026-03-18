<?php

use App\Models\Conversation;
use Illuminate\Support\Facades\Broadcast;

/**
 * Register your broadcast channels here.
 */

// Canal privé pour les conversations
Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    $conversation = Conversation::find($conversationId);

    if (!$conversation) {
        return false;
    }

    return $user->id === $conversation->user_id || $user->id === $conversation->owner_id;
});

// Canal de présence pour les conversations (statut en ligne)
Broadcast::channel('presence.conversation.{conversationId}', function ($user, $conversationId) {
    $conversation = Conversation::find($conversationId);

    if (!$conversation) {
        return false;
    }

    if ($user->id === $conversation->user_id || $user->id === $conversation->owner_id) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'avatar' => $user->getAvatarUrl(),
        ];
    }

    return false;
});

// Canal privé pour les notifications utilisateur
Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// Canal de présence pour savoir qui est en ligne
Broadcast::channel('online', function ($user) {
    return [
        'id' => $user->id,
        'name' => $user->name,
    ];
});
