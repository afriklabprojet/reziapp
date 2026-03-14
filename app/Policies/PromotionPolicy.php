<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Promotion;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PromotionPolicy
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

    public function view(User $user, Promotion $promotion): bool
    {
        return $promotion->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isOwner();
    }

    public function update(User $user, Promotion $promotion): bool
    {
        return $promotion->user_id === $user->id;
    }

    public function delete(User $user, Promotion $promotion): bool
    {
        return $promotion->user_id === $user->id;
    }

    public function toggle(User $user, Promotion $promotion): bool
    {
        return $promotion->user_id === $user->id;
    }
}
