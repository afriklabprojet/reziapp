<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Coupon;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CouponPolicy
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

    public function view(User $user, Coupon $coupon): bool
    {
        return (int) $coupon->user_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isOwner();
    }

    public function update(User $user, Coupon $coupon): bool
    {
        return (int) $coupon->user_id === (int) $user->id;
    }

    public function delete(User $user, Coupon $coupon): bool
    {
        return (int) $coupon->user_id === (int) $user->id;
    }

    public function toggle(User $user, Coupon $coupon): bool
    {
        return (int) $coupon->user_id === (int) $user->id;
    }

    public function duplicate(User $user, Coupon $coupon): bool
    {
        return (int) $coupon->user_id === (int) $user->id;
    }
}
