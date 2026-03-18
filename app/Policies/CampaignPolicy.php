<?php

namespace App\Policies;

use App\Models\Campaign;
use App\Models\User;

class CampaignPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Campaign $campaign): bool
    {
        return (int) $user->id === (int) $campaign->user_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Campaign $campaign): bool
    {
        return (int) $user->id === (int) $campaign->user_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Campaign $campaign): bool
    {
        return (int) $user->id === (int) $campaign->user_id || $user->isAdmin();
    }
}
