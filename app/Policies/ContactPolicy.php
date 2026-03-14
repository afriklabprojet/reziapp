<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Policy pour les contacts
 *
 * Contrôle d'accès aux demandes de contact
 */
class ContactPolicy
{
    use HandlesAuthorization;

    /**
     * Perform pre-authorization checks.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

    /**
     * Determine whether the user can view any contacts.
     */
    public function viewAny(User $user): bool
    {
        // Les propriétaires peuvent voir leurs contacts reçus
        // Les utilisateurs peuvent voir leurs contacts envoyés
        return true;
    }

    /**
     * Determine whether the user can view the contact.
     */
    public function view(User $user, Contact $contact): bool
    {
        // Le propriétaire ou l'expéditeur peut voir le contact
        return $contact->owner_id === $user->id || $contact->user_id === $user->id;
    }

    /**
     * Determine whether the user can create contacts.
     */
    public function create(User $user): bool
    {
        // Tout utilisateur connecté peut créer un contact
        return true;
    }

    /**
     * Determine whether the user can update the contact.
     */
    public function update(User $user, Contact $contact): bool
    {
        // Seul le propriétaire peut mettre à jour le statut
        return $contact->owner_id === $user->id;
    }

    /**
     * Determine whether the user can delete the contact.
     */
    public function delete(User $user, Contact $contact): bool
    {
        // L'expéditeur ou le propriétaire peut supprimer
        return $contact->user_id === $user->id || $contact->owner_id === $user->id;
    }

    /**
     * Determine whether the user can respond to the contact.
     */
    public function respond(User $user, Contact $contact): bool
    {
        // Seul le propriétaire peut répondre
        return $contact->owner_id === $user->id;
    }

    /**
     * Determine whether the user can mark as viewed.
     */
    public function markAsViewed(User $user, Contact $contact): bool
    {
        return $contact->owner_id === $user->id;
    }
}
