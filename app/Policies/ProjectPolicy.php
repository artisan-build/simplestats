<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use App\Services\SubscriptionService;
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
        return $user->hasTeamPermission($project->team, 'action:read');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        $allowedProjects = $user->currentTeam->owner->sparkPlan()->options[Project::class];

        return $user->hasTeamPermission($user->currentTeam, 'action:create')
            && (Project::count() < $allowedProjects || $allowedProjects === SubscriptionService::UNLIMITED_AMOUNT);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Project $project): bool
    {
        return $user->hasTeamPermission($project->team, 'action:update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Project $project): bool
    {
        return $user->hasTeamPermission($project->team, 'action:delete');
    }
}
