<?php

namespace App\Actions;

use App\Models\Instance;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class CreateInstance
{
    public function __invoke(User $user, Project $project, array $input): Instance
    {
        Gate::forUser($user)->authorize('create', Instance::class);

        return $project->instances()->create($input);
    }
}
