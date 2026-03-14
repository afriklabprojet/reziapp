<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Residence;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Policy pour les résidences
 *
 * Contrôle granulaire des permissions CRUD sur les résidences
 */
class ResidencePolicy
{
    use HandlesAuthorization;

    /**
     * Perform pre-authorization checks.
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
     * Determine whether the user can view any residences.
     */
    public function viewAny(?User $user): bool
    {
        // Tout le monde peut voir la liste des résidences approuvées
        return true;
    }

    /**
     * Determine whether the user can view the residence.
     */
    public function view(?User $user, Residence $residence): bool
    {
        // Résidences actives visibles par tous
        if (in_array($residence->status, ['active', 'approved'])) {
            return true;
        }

        // Propriétaire peut voir ses propres résidences
        if ($user && $residence->owner_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create residences.
     *
     * Propriétaires non vérifiés : limités à 1 annonce
     * Propriétaires vérifiés : pas de limite
     */
    public function create(User $user): bool
    {
        // Seuls les owners/admins peuvent créer
        if (!$user->isOwner() && !$user->isAdmin()) {
            return false;
        }

        // Propriétaires non vérifiés : maximum 1 résidence
        if (!$user->identity_verified) {
            $existingCount = $user->residences()->count();
            if ($existingCount >= 1) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine whether the user can update the residence.
     */
    public function update(User $user, Residence $residence): bool
    {
        // Le propriétaire peut modifier sa résidence
        return $residence->owner_id === $user->id;
    }

    /**
     * Determine whether the user can delete the residence.
     */
    public function delete(User $user, Residence $residence): bool
    {
        // Le propriétaire peut supprimer sa résidence
        return $residence->owner_id === $user->id;
    }

    /**
     * Determine whether the user can restore the residence.
     */
    public function restore(User $user, Residence $residence): bool
    {
        return $residence->owner_id === $user->id;
    }

    /**
     * Determine whether the user can permanently delete the residence.
     */
    public function forceDelete(User $user, Residence $residence): bool
    {
        // Seul l'admin peut supprimer définitivement
        return false;
    }

    /**
     * Determine whether the user can upload photos.
     */
    public function uploadPhotos(User $user, Residence $residence): bool
    {
        return $residence->owner_id === $user->id;
    }

    /**
     * Determine whether the user can manage amenities.
     */
    public function manageAmenities(User $user, Residence $residence): bool
    {
        return $residence->owner_id === $user->id;
    }

    /**
     * Determine whether the user can approve/reject residences.
     */
    public function moderate(User $user, Residence $residence): bool
    {
        // Seul l'admin peut modérer (géré par before())
        return false;
    }

    /**
     * Determine whether the user can view statistics.
     */
    public function viewStatistics(User $user, Residence $residence): bool
    {
        return $residence->owner_id === $user->id;
    }
}
