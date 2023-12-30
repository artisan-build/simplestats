<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Actions\Fortify\CreateNewUser;
use App\Models\Instance;
use App\Models\Project;
use App\Models\StatsUser;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = (new CreateNewUser())->create([
            'name' => 'Zacharias Creutznacher',
            'email' => 'zacharias.creutznacher@gmail.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => true,
        ]);

        $user->switchTeam($user->personalTeam());

        Project::factory()->count(2)->create([
            'team_id' => $user->current_team_id,
        ])->each(function (Project $project) use ($user) {
            Instance::factory()->count(2)->create([
                'project_id' => $project->id,
                'team_id' => $user->current_team_id,
            ])->each(function (Instance $instance) use ($project, $user) {
                StatsUser::factory()->count(500)->create([
                    'team_id' => $user->current_team_id,
                    'project_id' => $project->id,
                    'instance_id' => $instance->id,
                ]);
            });
        });
    }
}
