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
                Instance::factory()->count(3)
                    ->afterCreating(function (Instance $instance) {
                        StatsUser::factory(rand(25, 100))->create([
                            'project_id' => $instance->project_id,
                            'instance_id' => $instance->id,
                        ])
                        ->each(function ($statsUser) use ($instance) {
                            // Basiszeit definieren
                            $baseTime = now()->subYears(2)->addDays(rand(0, 730));

                            // Logins erstellen
                            for ($i = 0; $i < rand(0, 20); $i++) {
                                StatsLogin::factory()->create([
                                    'project_id' => $instance->project_id,
                                    'instance_id' => $instance->id,
                                    'stats_user_id' => $statsUser->id,
                                    'time' => $baseTime->addDays(rand(1, 28)) // Zeit inkrementieren
                                ]);
                            }

                            // Payments erstellen
                            if (rand(0, 9) === 0) {
                                for ($i = 0; $i < rand(0, 20); $i++) {
                                    StatsPayment::factory()->create([
                                        'project_id' => $instance->project_id,
                                        'instance_id' => $instance->id,
                                        'stats_user_id' => $statsUser->id,
                                        'time' => $baseTime->addDays(rand(1, 28)) // Zeit inkrementieren
                                    ]);
                                }
                            }
                        });
                    })
            )
            ->create();

        $this->changeEnv([
            'SIMPLESTATS_API_TOKEN' => Instance::find(1)->tokens()->first()->token_plain,
        ]);
    }

    protected function changeEnv($data = [])
    {
        if (count($data) > 0) {
            // Pfad zur .env-Datei
            $env = file_get_contents(base_path() . '/.env');

            foreach ($data as $dataKey => $dataValue) {
                // Ersetzen des aktuellen Werts mit dem neuen Wert
                $env = preg_replace('/^' . $dataKey . '=.*/m', $dataKey . '=' . $dataValue, $env);
            }

            // Speichern der neuen .env-Datei
            file_put_contents(base_path() . '/.env', $env);
        }
    }
}
