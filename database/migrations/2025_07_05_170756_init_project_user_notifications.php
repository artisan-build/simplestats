<?php

use App\Actions\CreateUserNotification;
use App\Models\Project;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Project::with(['team.users', 'team.owner'])->get()->each(function (Project $project) {
            $project->team->allUsers()->each(function ($user) use ($project) {
                app(CreateUserNotification::class)($user, $project);
            });
        });
    }
};
