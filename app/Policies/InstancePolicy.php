<?php

namespace App\Policies;

use App\Models\Instance;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class InstancePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Instance $instance): bool
    {
        return $user->hasTeamPermission($instance->team, 'action:read');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasTeamPermission($user->currentTeam, 'action:create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Instance $instance): bool
    {
        return $user->hasTeamPermission($instance->team, 'action:update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Instance $instance): bool
    {
        return $user->hasTeamPermission($instance->team, 'action:delete');
    }

    public function regenerateInstanceToken(User $user, Instance $instance): bool
    {
        return $user->hasTeamPermission($instance->team, 'action:delete');
    }

    public function viewInstanceToken(User $user, Instance $instance): bool
    {
        return $user->hasTeamPermission($instance->team, 'action:readSensitives');
    }
}
