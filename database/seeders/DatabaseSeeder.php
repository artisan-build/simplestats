<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Actions\Fortify\CreateNewUser;
use App\Models\Instance;
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
            $testUser = config('app.test.user');
            $user = app(CreateNewUser::class)->create([
                'name' => $testUser['name'],
                'email' => $testUser['email'],
                'password' => $testUser['password'],
                'password_confirmation' => $testUser['password'],
                'terms' => true,
            ]);

            $user->makeAdmin();
            $user->markEmailAsVerified();

            $user->switchTeam($user->personalTeam());
            $user->currentTeam->update([
                'currency' => self::DEFAULT_CURRENCY,
                'timezone' => 'Europe/Berlin',
            ]);

            return $user;
        });

        auth()->setUser($user);

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

        $projects = [
            //['name' => 'My cool SaaS'],
            ['name' => 'My great App'],
        ];

        $instances = [
            ['name' => 'Default'],
            //['name' => 'US'],
            //['name' => 'EU'],
        ];

        Project::factory()->count(count($projects))
            ->state(new Sequence(...$projects))
            ->for(Team::find($user->current_team_id))
            ->has(
                Instance::factory()->count(count($instances))
                    ->state(new Sequence(...$instances))
                    ->afterCreating(function (Instance $instance) {
                        // our ids are not auto increment, as they will be sent by the client,
                        // so for tests we need to put a self-auto-incremented value here...
                        StatsUser::factory()->initialize();
                        StatsPayment::factory()->initialize();

                        $count = $this->getUserSeedCount();
                        StatsUser::factory(rand($count['from'], $count['to']))->create([
                            'project_id' => $instance->project_id,
                            'instance_id' => $instance->id,
                        ])
                            ->each(function ($statsUser) use ($instance) {
                                // for each registration we simulate 3 to 6 visitors
                                StatsVisitor::factory(rand(3, 6))->create([
                                    'project_id' => $instance->project_id,
                                    'instance_id' => $instance->id,
                                    'time' => $statsUser->time,
                                ]);

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
                                            'time' => $paymentTime->addDays(rand(1, 28)),
                                        ]);
                                    }
                                }
                            });
                    })
            )
            ->create();

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
            'from' => app()->isLocal() ? 1100 : 500,
            'to' => app()->isLocal() ? 1300 : 1000,
        ];
    }
}
