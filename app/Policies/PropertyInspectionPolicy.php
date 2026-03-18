<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PropertyInspection;
use App\Models\User;

class PropertyInspectionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isOwner() || $user->isAdmin();
    }

    public function view(User $user, PropertyInspection $inspection): bool
    {
        return (int) $user->id === (int) $inspection->owner_id
            || (int) $user->id === (int) $inspection->tenant_id
            || $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isOwner() || $user->isAdmin();
    }

    public function update(User $user, PropertyInspection $inspection): bool
    {
        return (int) $user->id === (int) $inspection->owner_id
            && in_array($inspection->status, [
                PropertyInspection::STATUS_DRAFT,
                PropertyInspection::STATUS_IN_PROGRESS,
            ]);
    }

    public function sign(User $user, PropertyInspection $inspection): bool
    {
        return ((int) $user->id === (int) $inspection->owner_id && ! $inspection->owner_signed_at)
            || ((int) $user->id === (int) $inspection->tenant_id && ! $inspection->tenant_signed_at);
    }

    public function download(User $user, PropertyInspection $inspection): bool
    {
        return (int) $user->id === (int) $inspection->owner_id
            || (int) $user->id === (int) $inspection->tenant_id
            || $user->isAdmin();
    }

    public function delete(User $user, PropertyInspection $inspection): bool
    {
        return (int) $user->id === (int) $inspection->owner_id
            && $inspection->status === PropertyInspection::STATUS_DRAFT;
    }
}
