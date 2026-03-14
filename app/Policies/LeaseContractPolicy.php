<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\LeaseContract;
use App\Models\User;

class LeaseContractPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isOwner() || $user->isAdmin();
    }

    public function view(User $user, LeaseContract $contract): bool
    {
        return $user->id === $contract->owner_id
            || $user->id === $contract->tenant_id
            || $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isOwner() || $user->isAdmin();
    }

    public function update(User $user, LeaseContract $contract): bool
    {
        return $user->id === $contract->owner_id
            && $contract->status === LeaseContract::STATUS_DRAFT;
    }

    public function sign(User $user, LeaseContract $contract): bool
    {
        if ($user->id === $contract->owner_id) {
            return $contract->canBeSignedByOwner();
        }

        if ($user->id === $contract->tenant_id) {
            return $contract->canBeSignedByTenant();
        }

        return false;
    }

    public function delete(User $user, LeaseContract $contract): bool
    {
        return $user->id === $contract->owner_id
            && $contract->status === LeaseContract::STATUS_DRAFT;
    }

    public function download(User $user, LeaseContract $contract): bool
    {
        return $user->id === $contract->owner_id
            || $user->id === $contract->tenant_id
            || $user->isAdmin();
    }

    public function terminate(User $user, LeaseContract $contract): bool
    {
        return $user->id === $contract->owner_id
            && $contract->status === LeaseContract::STATUS_ACTIVE;
    }
}
