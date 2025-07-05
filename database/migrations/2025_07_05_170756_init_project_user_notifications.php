<?php

use App\Actions\CreateUserNotification;
use App\Models\Project;
use App\Models\UserNotification;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
