<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Actions\Fortify\CreateNewUser;
use App\Helpers\AppHelper;
use App\Models\Project;
use App\Models\StatsLogin;
use App\Models\StatsPayment;
use App\Models\StatsUser;
use App\Models\StatsVisitor;
use App\Models\Team;
use App\Models\User;
use App\Services\StatsService;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class DatabaseSeeder extends Seeder
{
    const DEFAULT_CURRENCY = 'EUR';

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Cache::clear();

        $user = User::withoutEvents(function () {
            $isDemo = app()->environment('demo');
            $testUser = config('app.test.user');
            $user = app(CreateNewUser::class)->create([
                'name' => $testUser['name'],
                'email' => $testUser['email'],
                'password' => $testUser['password'],
                'password_confirmation' => $testUser['password'],
                'terms' => true,
                'settings' => [
                    'showMenu' => ! $isDemo,
                ],
            ]);

            if (! $isDemo) {
                $user->makeAdmin();
            }

            $user->markEmailAsVerified();

            $user->switchTeam($user->personalTeam());
            $user->currentTeam->update([
                'currency' => self::DEFAULT_CURRENCY,
                'timezone' => 'Europe/Berlin',
            ]);

            return $user;
        });

        auth()->setUser($user);
        AppHelper::setTeamContextFromAuth($user);

        if (app()->isLocal()) {
            Project::factory()->count(1)->state(['name' => 'SimpleStats'])
                ->for(Team::find($user->current_team_id))
                ->afterCreating(function ($project) {
                    StatsUser::factory()->create([
                        'id' => User::find(1)->id,
                        'project_id' => $project->id,
                    ]);
                })->create();
        }

        //make sure the next project gets created at least 1 sec after the first...
        //        sleep(1);

        $projects = [
            //['name' => 'My cool SaaS'],
            ['name' => 'My great App'],
        ];

        Project::factory()->count(count($projects))
            ->state(new Sequence(...$projects))
            ->for(Team::find($user->current_team_id))
            ->afterCreating(function ($project) {
                $count = $this->getUserSeedCount();
                StatsUser::factory(rand($count['from'], $count['to']))->create([
                    'project_id' => $project->id,
                ])->each(function ($statsUser) use ($project) {
                    // for each registration we simulate 3 to 6 visitors
                    StatsVisitor::factory(rand(3, 6))->create([
                        'project_id' => $project->id,
                        'time' => $statsUser->time,
                    ]);

                    $loginTime = Carbon::parse($statsUser->time);
                    for ($i = 0; $i < rand(0, 20); $i++) {
                        StatsLogin::factory()->create([
                            'project_id' => $project->id,
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
                                'project_id' => $project->id,
                                'stats_user_id' => $statsUser->id,
                                'currency' => self::DEFAULT_CURRENCY,
                                // increment a random number of days for the next payment
                                'time' => $paymentTime->addDays(rand(1, 28)),
                            ]);
                        }
                    }
                });
            })->create();

        $this->call([
            DataImportSeeder::class,
        ]);

        // make sure no data greater than now is seeded
        collect(StatsService::getStatsModels())->each(function ($statsModel) {
            $statsModel::where('time', '>', now())->delete();
        });
    }

    protected function changeEnv($data = [])
    {
        if (count($data) > 0) {
            // Pfad zur .env-Datei
            $env = file_get_contents(base_path().'/.env');

            foreach ($data as $dataKey => $dataValue) {
                // Ersetzen des aktuellen Werts mit dem neuen Wert
                $env = preg_replace('/^'.$dataKey.'=.*/m', $dataKey.'='.$dataValue, $env);
            }

            // Speichern der neuen .env-Datei
            file_put_contents(base_path().'/.env', $env);
        }
    }

    protected function getUserSeedCount(): array
    {
        return [
            'from' => match (true) {
                app()->environment('demo') => 500,
                app()->environment('testing') => 5,
                default => 500,
            },
            'to' => match (true) {
                app()->environment('demo') => 1000,
                app()->environment('testing') => 10,
                default => 1000,
            },
        ];
    }
}
