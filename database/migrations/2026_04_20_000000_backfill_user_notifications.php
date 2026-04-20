<?php

use App\Actions\CreateUserNotification;
use App\Models\Team;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Team::with(['users', 'owner', 'projects'])->chunk(100, function ($teams) {
            foreach ($teams as $team) {
                foreach ($team->allUsers() as $user) {
                    if ($user === null) {
                        continue;
                    }
                    foreach ($team->projects as $project) {
                        app(CreateUserNotification::class)($user, $project);
                    }
                }
            }
        });
    }

    public function down(): void
    {
        //
    }
};
