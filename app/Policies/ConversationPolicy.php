<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Policy pour les conversations de messagerie
 *
 * Contrôle l'accès aux conversations entre utilisateurs et propriétaires
 */
class ConversationPolicy
{
    use HandlesAuthorization;

    /**
     * Les admins peuvent tout faire
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

    /**
     * Voir la liste des conversations
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Voir une conversation spécifique
     */
    public function view(User $user, Conversation $conversation): bool
    {
        return $this->isParticipant($user, $conversation);
    }

    /**
     * Envoyer un message dans la conversation
     */
    public function sendMessage(User $user, Conversation $conversation): bool
    {
        return $this->isParticipant($user, $conversation)
            && !$conversation->isBlocked();
    }

    /**
     * Archiver / désarchiver une conversation
     */
    public function archive(User $user, Conversation $conversation): bool
    {
        return $this->isParticipant($user, $conversation);
    }

    /**
     * Épingler / désépingler une conversation
     */
    public function pin(User $user, Conversation $conversation): bool
    {
        return $this->isParticipant($user, $conversation);
    }

    /**
     * Mettre en sourdine / réactiver
     */
    public function mute(User $user, Conversation $conversation): bool
    {
        return $this->isParticipant($user, $conversation);
    }

    /**
     * Bloquer une conversation
     */
    public function block(User $user, Conversation $conversation): bool
    {
        return $this->isParticipant($user, $conversation);
    }

    /**
     * Supprimer une conversation
     */
    public function delete(User $user, Conversation $conversation): bool
    {
        return $this->isParticipant($user, $conversation);
    }

    /**
     * Vérifier si l'utilisateur est participant à la conversation
     */
    protected function isParticipant(User $user, Conversation $conversation): bool
    {
        return $conversation->user_id === $user->id
            || $conversation->owner_id === $user->id;
    }
}
