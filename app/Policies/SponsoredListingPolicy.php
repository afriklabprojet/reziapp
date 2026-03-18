<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SponsoredListing;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SponsoredListingPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): ?bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isOwner();
    }

    public function view(User $user, SponsoredListing $sponsoredListing): bool
    {
        return (int) $sponsoredListing->user_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isOwner();
    }

    public function update(User $user, SponsoredListing $sponsoredListing): bool
    {
        return (int) $sponsoredListing->user_id === (int) $user->id;
    }

    public function delete(User $user, SponsoredListing $sponsoredListing): bool
    {
        return (int) $sponsoredListing->user_id === (int) $user->id;
    }

    public function pause(User $user, SponsoredListing $sponsoredListing): bool
    {
        return (int) $sponsoredListing->user_id === (int) $user->id && $sponsoredListing->isActive();
    }

    public function resume(User $user, SponsoredListing $sponsoredListing): bool
    {
        return (int) $sponsoredListing->user_id === (int) $user->id && $sponsoredListing->status === 'paused';
    }

    public function cancel(User $user, SponsoredListing $sponsoredListing): bool
    {
        return (int) $sponsoredListing->user_id === (int) $user->id
            && in_array($sponsoredListing->status, ['active', 'paused', 'pending']);
    }
}
