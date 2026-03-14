<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SecurityDeposit;
use App\Models\User;

class SecurityDepositPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isOwner() || $user->isAdmin();
    }

    public function view(User $user, SecurityDeposit $deposit): bool
    {
        return $user->id === $deposit->owner_id
            || $user->id === $deposit->tenant_id
            || $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isOwner() || $user->isAdmin();
    }

    public function update(User $user, SecurityDeposit $deposit): bool
    {
        return $user->id === $deposit->owner_id
            && in_array($deposit->status, [SecurityDeposit::STATUS_PENDING, SecurityDeposit::STATUS_HELD]);
    }

    public function return(User $user, SecurityDeposit $deposit): bool
    {
        return $user->id === $deposit->owner_id
            && in_array($deposit->status, [SecurityDeposit::STATUS_HELD, SecurityDeposit::STATUS_PARTIAL_RETURN]);
    }

    public function delete(User $user, SecurityDeposit $deposit): bool
    {
        return $user->id === $deposit->owner_id
            && $deposit->status === SecurityDeposit::STATUS_PENDING
            && $user->isAdmin();
    }
}
