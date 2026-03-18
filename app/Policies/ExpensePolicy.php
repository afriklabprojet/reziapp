<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ExpensePolicy
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

    public function view(User $user, Expense $expense): bool
    {
        return $user->id === $expense->owner_id;
    }

    public function create(User $user): bool
    {
        return $user->isOwner();
    }

    public function update(User $user, Expense $expense): bool
    {
        return $user->id === $expense->owner_id;
    }

    public function delete(User $user, Expense $expense): bool
    {
        return $user->id === $expense->owner_id;
    }
}
