<?php

namespace App\Actions;

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class CreateProject
{
    public function __invoke(User $user, array $input): Project
    {
        Gate::forUser($user)->authorize('create', Project::class);

        return Project::create($input);
    }
}
