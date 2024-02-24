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
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    const DEFAULT_CURRENCY = 'EUR';

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::withoutEvents(function () {
            $testUser = config('app.test.user');
            $user = (new CreateNewUser())->create([
                'name' => $testUser['name'],
                'email' => $testUser['email'],
                'password' => $testUser['password'],
                'password_confirmation' => $testUser['password'],
                'terms' => true,
            ]);

            $user->switchTeam($user->personalTeam());
            $user->currentTeam->update(['currency' => self::DEFAULT_CURRENCY]);

            return $user;
        });

        if (app()->isLocal()) {
            Project::factory()->count(1)->state(['name' => 'SimpleStats'])
                ->for(Team::find($user->current_team_id))
                ->has(Instance::factory()->count(1)->state(['name' => 'Local'])
                    ->afterCreating(function (Instance $instance) {
                        StatsUser::factory(1)->initialize()->create([
                            'id' => User::find(1)->id,
                            'project_id' => $instance->project_id,
                            'instance_id' => $instance->id,
                        ]);
                    }))->create();

            $this->changeEnv([
                'SIMPLESTATS_API_TOKEN' => Instance::find(1)->tokens()->first()->token_plain,
            ]);
        }

        //make sure the next project gets created at least 1 sec after the first...
        sleep(1);

        Project::factory()->count(2)
            ->state(new Sequence(
                ['name' => 'My cool SaaS'],
                ['name' => 'My great App'],
            ))
            ->for(Team::find($user->current_team_id))
            ->has(
                Instance::factory()->count(2)
                    ->state(new Sequence(
                        ['name' => 'US'],
                        ['name' => 'EU'],
                    ))
                    ->afterCreating(function (Instance $instance) {
                        StatsUser::factory(rand(100,300))->initialize()->create([
                            'project_id' => $instance->project_id,
                            'instance_id' => $instance->id,
                        ])
                        ->each(function ($statsUser) use ($instance) {
                            $loginTime = Carbon::parse($statsUser->time);
                            for ($i = 0; $i < rand(0, 20); $i++) {
                                StatsLogin::factory()->create([
                                    'project_id' => $instance->project_id,
                                    'instance_id' => $instance->id,
                                    'stats_user_id' => $statsUser->id,
                                    //The first login time is the same as registration
                                    'time' => $loginTime,
                                ]);
                                // increment a random number of days for the next login
                                $loginTime->addDays(rand(1, 28));
                            }

                            $paymentTime = Carbon::parse($statsUser->time);
                            if (rand(0, 9) === 0) {
                                for ($i = 0; $i < rand(0, 20); $i++) {
                                    StatsPayment::factory()->create([
                                        'project_id' => $instance->project_id,
                                        'instance_id' => $instance->id,
                                        'stats_user_id' => $statsUser->id,
                                        'currency' => self::DEFAULT_CURRENCY,
                                        // increment a random number of days for the next payment
                                        'time' => $paymentTime->addDays(rand(1, 28))
                                    ]);
                                }
                            }
                        });
                    })
            )
            ->create();
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
