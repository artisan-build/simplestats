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
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    const DEFAULT_CURRENCY = 'EUR';

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Cache::clear();

        DB::transaction(function () {
            $this->seedData();
        });
    }

    protected function seedData(): void
    {

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

        // Add extra data for current week to show activity
        $this->seedCurrentWeekData();
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

    protected function seedCurrentWeekData(): void
    {
        $projects = Project::all();

        foreach ($projects as $project) {
            $sevenDaysAgo = now()->subDays(7);

            // Extra registrations for last 7 days (5-15 new users)
            $newUsersCount = rand(5, 15);
            for ($i = 0; $i < $newUsersCount; $i++) {
                $registrationTime = Carbon::instance(fake()->dateTimeBetween($sevenDaysAgo, now()));

                $statsUser = StatsUser::factory()->create([
                    'project_id' => $project->id,
                    'time' => $registrationTime,
                ]);

                // For each registration, create 2-5 visitors around the same time
                StatsVisitor::factory(rand(2, 5))->create([
                    'project_id' => $project->id,
                    'time' => $registrationTime,
                ]);

                // Create 1-3 logins for each new user during the last 7 days
                $loginCount = rand(1, 3);
                for ($j = 0; $j < $loginCount; $j++) {
                    StatsLogin::factory()->create([
                        'project_id' => $project->id,
                        'stats_user_id' => $statsUser->id,
                        'time' => Carbon::instance(fake()->dateTimeBetween($registrationTime, now())),
                    ]);
                }

                // 20% chance of payment for new users
                if (rand(0, 4) === 0) {
                    StatsPayment::factory()->create([
                        'project_id' => $project->id,
                        'stats_user_id' => $statsUser->id,
                        'currency' => self::DEFAULT_CURRENCY,
                        'time' => Carbon::instance(fake()->dateTimeBetween($registrationTime, now())),
                    ]);
                }
            }

            // Extra visitors for last 7 days (20-40 additional visits)
            $extraVisitors = rand(20, 40);
            StatsVisitor::factory($extraVisitors)->create([
                'project_id' => $project->id,
                'time' => Carbon::instance(fake()->dateTimeBetween($sevenDaysAgo, now())),
            ]);

            // Extra logins from existing users (10-25 logins)
            $existingUsers = StatsUser::query()
                ->where('project_id', $project->id)
                ->where('time', '<', $sevenDaysAgo)
                ->inRandomOrder()
                ->limit(rand(10, 25))
                ->get();

            foreach ($existingUsers as $existingUser) {
                $loginTime = Carbon::instance(fake()->dateTimeBetween($sevenDaysAgo, now()));
                StatsLogin::factory()->create([
                    'project_id' => $project->id,
                    'stats_user_id' => $existingUser->id,
                    'time' => $loginTime,
                ]);
            }

            // Extra payments from existing users (3-8 payments)
            $payingUsers = StatsUser::query()
                ->where('project_id', $project->id)
                ->where('time', '<', $sevenDaysAgo)
                ->inRandomOrder()
                ->limit(rand(3, 8))
                ->get();

            foreach ($payingUsers as $payingUser) {
                StatsPayment::factory()->create([
                    'project_id' => $project->id,
                    'stats_user_id' => $payingUser->id,
                    'currency' => self::DEFAULT_CURRENCY,
                    'time' => Carbon::instance(fake()->dateTimeBetween($sevenDaysAgo, now())),
                ]);
            }

            // Extra data specifically for TODAY to ensure visible activity
            $todayStart = now()->startOfDay();
            $todayNow = now();

            // Today: 3-8 new registrations
            $todayRegistrations = rand(3, 8);
            for ($i = 0; $i < $todayRegistrations; $i++) {
                $registrationTime = Carbon::instance(fake()->dateTimeBetween($todayStart, $todayNow));

                $statsUser = StatsUser::factory()->create([
                    'project_id' => $project->id,
                    'time' => $registrationTime,
                ]);

                // Create 2-4 visitors for each registration today
                StatsVisitor::factory(rand(2, 4))->create([
                    'project_id' => $project->id,
                    'time' => $registrationTime,
                ]);

                // Each new user has 1-2 logins today
                for ($j = 0; $j < rand(1, 2); $j++) {
                    StatsLogin::factory()->create([
                        'project_id' => $project->id,
                        'stats_user_id' => $statsUser->id,
                        'time' => Carbon::instance(fake()->dateTimeBetween($registrationTime, $todayNow)),
                    ]);
                }
            }

            // Today: 10-20 extra visitors
            StatsVisitor::factory(rand(10, 20))->create([
                'project_id' => $project->id,
                'time' => Carbon::instance(fake()->dateTimeBetween($todayStart, $todayNow)),
            ]);

            // Today: 5-12 logins from existing users
            $todayLoginUsers = StatsUser::query()
                ->where('project_id', $project->id)
                ->where('time', '<', $todayStart)
                ->inRandomOrder()
                ->limit(rand(5, 12))
                ->get();

            foreach ($todayLoginUsers as $user) {
                StatsLogin::factory()->create([
                    'project_id' => $project->id,
                    'stats_user_id' => $user->id,
                    'time' => Carbon::instance(fake()->dateTimeBetween($todayStart, $todayNow)),
                ]);
            }

            // Today: 2-5 payments from existing users
            $todayPayingUsers = StatsUser::query()
                ->where('project_id', $project->id)
                ->where('time', '<', $todayStart)
                ->inRandomOrder()
                ->limit(rand(2, 5))
                ->get();

            foreach ($todayPayingUsers as $user) {
                StatsPayment::factory()->create([
                    'project_id' => $project->id,
                    'stats_user_id' => $user->id,
                    'currency' => self::DEFAULT_CURRENCY,
                    'time' => Carbon::instance(fake()->dateTimeBetween($todayStart, $todayNow)),
                ]);
            }
        }
    }
}
