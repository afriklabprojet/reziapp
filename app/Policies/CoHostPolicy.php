<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CoHost;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CoHostPolicy
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

    public function view(User $user, CoHost $coHost): bool
    {
        return $coHost->owner_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isOwner();
    }

    public function update(User $user, CoHost $coHost): bool
    {
        return $coHost->owner_id === $user->id;
    }

    public function delete(User $user, CoHost $coHost): bool
    {
        return $coHost->owner_id === $user->id;
    }

    public function revoke(User $user, CoHost $coHost): bool
    {
        return $coHost->owner_id === $user->id && $coHost->isActive();
    }

    public function resend(User $user, CoHost $coHost): bool
    {
        return $coHost->owner_id === $user->id && $coHost->status === 'pending';
    }
}
