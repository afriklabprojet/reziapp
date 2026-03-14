<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Photo;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Policy pour les photos
 *
 * Contrôle d'accès aux photos des résidences
 */
class PhotoPolicy
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
     * Determine whether the user can view any photos.
     */
    public function viewAny(?User $user): bool
    {
        // Tout le monde peut voir les photos
        return true;
    }

    /**
     * Determine whether the user can view the photo.
     */
    public function view(?User $user, Photo $photo): bool
    {
        // Si la résidence est active, la photo est visible
        if (in_array($photo->residence->status, ['active', 'approved'])) {
            return true;
        }

        // Le propriétaire peut voir ses photos
        if ($user && $photo->residence->owner_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create photos.
     */
    public function create(User $user): bool
    {
        // Les propriétaires peuvent ajouter des photos
        return $user->isOwner() || $user->isAdmin();
    }

    /**
     * Determine whether the user can update the photo.
     */
    public function update(User $user, Photo $photo): bool
    {
        // Le propriétaire de la résidence peut modifier
        return $photo->residence->owner_id === $user->id;
    }

    /**
     * Determine whether the user can delete the photo.
     */
    public function delete(User $user, Photo $photo): bool
    {
        // Le propriétaire de la résidence peut supprimer
        return $photo->residence->owner_id === $user->id;
    }

    /**
     * Determine whether the user can set as primary.
     */
    public function setAsPrimary(User $user, Photo $photo): bool
    {
        return $photo->residence->owner_id === $user->id;
    }

    /**
     * Determine whether the user can reorder photos.
     */
    public function reorder(User $user, Photo $photo): bool
    {
        return $photo->residence->owner_id === $user->id;
    }
}
