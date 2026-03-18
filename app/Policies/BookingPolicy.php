<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Policy pour les réservations
 *
 * Contrôle des permissions sur les réservations
 */
class BookingPolicy
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
     * Le voyageur ou le propriétaire de la résidence peut voir la réservation
     */
    public function view(User $user, Booking $booking): bool
    {
        return (int) $booking->user_id === (int) $user->id
            || (int) ($booking->residence?->owner_id ?? 0) === (int) $user->id;
    }

    /**
     * Le voyageur peut annuler sa propre réservation (si annulable)
     */
    public function cancel(User $user, Booking $booking): bool
    {
        // Le voyageur peut annuler ses propres réservations
        if ((int) $booking->user_id === (int) $user->id) {
            return $booking->canBeCancelled();
        }

        // Le propriétaire peut annuler les réservations de sa résidence
        if ((int) ($booking->residence?->owner_id ?? 0) === (int) $user->id) {
            return $booking->canBeCancelled();
        }

        return false;
    }

    /**
     * Le voyageur peut mettre à jour sa réservation
     */
    public function update(User $user, Booking $booking): bool
    {
        return (int) $booking->user_id === (int) $user->id;
    }

    /**
     * Le propriétaire de la résidence peut gérer la réservation
     * (voir détails, confirmer, annuler, etc.)
     */
    public function manageAsOwner(User $user, Booking $booking): bool
    {
        return (int) ($booking->residence?->owner_id ?? 0) === (int) $user->id;
    }
}
