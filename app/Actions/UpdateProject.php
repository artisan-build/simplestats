<?php

namespace App\Actions;

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class UpdateProject
{
    public function __invoke(User $user, Project $project, array $input): Project
    {
        Gate::forUser($user)->authorize('update', $project);

        return tap($project)->update($input);
    }
}
