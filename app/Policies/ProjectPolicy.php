<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProjectPolicy
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
    public function view(User $user, Project $project): bool
    {
        return $user->belongsToProject($project);
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
    public function update(User $user, Project $project): bool
    {
        return $user->ownsTeam($project->team);
    }

    /**
     * Determine whether the user can add project members.
     */
    public function addProjectMember(User $user, Project $project): bool
    {
        return $user->ownsTeam($project->team);
    }

    /**
     * Determine whether the user can update project member permissions.
     */
    public function updateProjectMember(User $user, Project $project): bool
    {
        return $user->ownsTeam($project->team);
    }

    /**
     * Determine whether the user can remove project members.
     */
    public function removeProjectMember(User $user, Project $project): bool
    {
        return $user->ownsTeam($project->team);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Project $project): bool
    {
        return $user->ownsTeam($project->team);
    }
}
