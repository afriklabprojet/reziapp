<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\MessageSequence;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MessageSequencePolicy
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

    public function view(User $user, MessageSequence $sequence): bool
    {
        return (int) $sequence->user_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isOwner();
    }

    public function update(User $user, MessageSequence $sequence): bool
    {
        return (int) $sequence->user_id === (int) $user->id;
    }

    public function delete(User $user, MessageSequence $sequence): bool
    {
        return (int) $sequence->user_id === (int) $user->id;
    }

    public function toggle(User $user, MessageSequence $sequence): bool
    {
        return (int) $sequence->user_id === (int) $user->id;
    }
}
