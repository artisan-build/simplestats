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
        return $user->belongsToInstance($instance);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Instance $instance): bool
    {
        return $user->ownsTeam($instance->team);
    }

    /**
     * Determine whether the user can add instance members.
     */
    public function addInstanceMember(User $user, Instance $instance): bool
    {
        return $user->ownsTeam($instance->team);
    }

    /**
     * Determine whether the user can update instance member permissions.
     */
    public function updateInstanceMember(User $user, Instance $instance): bool
    {
        return $user->ownsTeam($instance->team);
    }

    /**
     * Determine whether the user can remove instance members.
     */
    public function removeInstanceMember(User $user, Instance $instance): bool
    {
        return $user->ownsTeam($instance->team);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Instance $instance): bool
    {
        return $user->ownsTeam($instance->team);
    }
}
