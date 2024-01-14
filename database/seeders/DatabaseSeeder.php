<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Actions\Fortify\CreateNewUser;
use App\Models\Instance;
use App\Models\Project;
use App\Models\StatsLogin;
use App\Models\StatsPayment;
use App\Models\StatsUser;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::withoutEvents(function () {
            $user = (new CreateNewUser())->create([
                'name' => 'Zacharias Creutznacher',
                'email' => 'zacharias.creutznacher@gmail.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'terms' => true,
            ]);

            $user->switchTeam($user->personalTeam());

            return $user;
        });

        Project::factory()->count(2)
            ->for(Team::find($user->current_team_id))
            ->has(
                Instance::factory()->count(2)
                    ->afterCreating(function (Instance $instance) {
                        StatsUser::factory(rand(25, 100))->create([
                            'project_id' => $instance->project_id,
                            'instance_id' => $instance->id,
                        ])
                        ->each(function ($statsUser) use ($instance) {
                            StatsLogin::factory(rand(0, 20))->create([
                                'project_id' => $instance->project_id,
                                'instance_id' => $instance->id,
                                'stats_user_id' => $statsUser->id]
                            );

                            StatsPayment::factory(rand(0, 20))->create([
                                'project_id' => $instance->project_id,
                                'instance_id' => $instance->id,
                                'stats_user_id' => $statsUser->id
                            ]);
                        });
                    })
            )
            ->create();

        $this->command->info(Instance::find(1)->tokens()->first()->token_plain);
    }
}
