<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\RentReceipt;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RentReceiptPolicy
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

    public function view(User $user, RentReceipt $rentReceipt): bool
    {
        return $user->id === $rentReceipt->owner_id
            || $user->id === $rentReceipt->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->isOwner();
    }

    public function update(User $user, RentReceipt $rentReceipt): bool
    {
        return $user->id === $rentReceipt->owner_id;
    }

    public function delete(User $user, RentReceipt $rentReceipt): bool
    {
        return $user->id === $rentReceipt->owner_id;
    }
}
