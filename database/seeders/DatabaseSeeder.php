<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Actions\Fortify\CreateNewUser;
use App\Helpers\AppHelper;
use App\Models\Project;
use App\Models\StatsCustomEvent;
use App\Models\StatsCustomEventName;
use App\Models\StatsLogin;
use App\Models\StatsPayment;
use App\Models\StatsUser;
use App\Models\StatsVisitor;
use App\Models\Team;
use App\Models\User;
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
                $eventNames = collect(['signup_click', 'purchase_click', 'page_view', 'add_to_cart', 'checkout_start'])
                    ->map(fn ($name) => StatsCustomEventName::firstOrCreate([
                        'team_id' => $project->team_id,
                        'project_id' => $project->id,
                        'name' => $name,
                    ]));

                $count = $this->getUserSeedCount();
                StatsUser::factory(rand($count['from'], $count['to']))->create([
                    'project_id' => $project->id,
                ])->each(function ($statsUser) use ($project, $eventNames) {
                    // for each registration we simulate 3 to 6 visitors
                    $visitors = StatsVisitor::factory(rand(3, 6))->create([
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

                    // 40% of users fire 1-3 events
                    if (rand(0, 4) < 2) {
                        $visitor = $visitors->random();
                        for ($i = 0; $i < rand(1, 3); $i++) {
                            StatsCustomEvent::factory()->create([
                                'project_id' => $project->id,
                                'team_id' => $project->team_id,
                                'stats_user_id' => $statsUser->id,
                                'stats_visitor_id' => $visitor->id,
                                'stats_custom_event_name_id' => $eventNames->random()->id,
                                'time' => Carbon::parse($statsUser->time)->addDays(rand(0, 30)),
                            ]);
                        }
                    }
                });
            })->create();

        $this->call([
            DataImportSeeder::class,
            BlogPostSeeder::class,
        ]);

        // Add extra data for the next 30 days to show activity
        if (! app()->environment('testing')) {
            $this->seedFutureData();
        }
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

    protected function seedFutureData(): void
    {
        $projects = Project::all();

        foreach ($projects as $project) {
            // Track previous day values to ensure guaranteed growth
            $lastDayRegs = 5;
            $lastDayVisitors = 15;
            $lastDayLogins = 8;
            $lastDayPayments = 3;

            // Seed data for each day for the next 30 days with guaranteed increasing trend
            for ($day = 0; $day < 30; $day++) {
                $dayStart = now()->addDays($day)->startOfDay();
                $dayEnd = now()->addDays($day)->endOfDay();

                // Each metric increases by 0-2 per day (guaranteed not to decrease)
                $dailyRegistrations = $lastDayRegs + rand(0, 2);
                $extraVisitors = $lastDayVisitors + rand(0, 3);
                $loginCount = $lastDayLogins + rand(0, 2);
                $paymentCount = $lastDayPayments + rand(0, 1);

                // Update for next iteration
                $lastDayRegs = $dailyRegistrations;
                $lastDayVisitors = $extraVisitors;
                $lastDayLogins = $loginCount;
                $lastDayPayments = $paymentCount;

                // Create registrations
                for ($i = 0; $i < $dailyRegistrations; $i++) {
                    $registrationTime = Carbon::instance(fake()->dateTimeBetween($dayStart, $dayEnd));

                    $statsUser = StatsUser::factory()->create([
                        'project_id' => $project->id,
                        'time' => $registrationTime,
                    ]);

                    // Create 2-4 visitors for each registration
                    StatsVisitor::factory(rand(2, 4))->create([
                        'project_id' => $project->id,
                        'time' => $registrationTime,
                    ]);

                    // Each new user has 1-2 logins
                    for ($j = 0; $j < rand(1, 2); $j++) {
                        StatsLogin::factory()->create([
                            'project_id' => $project->id,
                            'stats_user_id' => $statsUser->id,
                            'time' => Carbon::instance(fake()->dateTimeBetween($registrationTime, $dayEnd)),
                        ]);
                    }

                    // 20% chance of payment for new users
                    if (rand(0, 4) === 0) {
                        StatsPayment::factory()->create([
                            'project_id' => $project->id,
                            'stats_user_id' => $statsUser->id,
                            'currency' => self::DEFAULT_CURRENCY,
                            'time' => Carbon::instance(fake()->dateTimeBetween($registrationTime, $dayEnd)),
                        ]);
                    }
                }

                // Extra visitors
                StatsVisitor::factory($extraVisitors)->create([
                    'project_id' => $project->id,
                    'time' => Carbon::instance(fake()->dateTimeBetween($dayStart, $dayEnd)),
                ]);

                // Logins from existing users
                $existingUsers = StatsUser::query()
                    ->where('project_id', $project->id)
                    ->where('time', '<', $dayStart)
                    ->inRandomOrder()
                    ->limit($loginCount)
                    ->get();

                foreach ($existingUsers as $user) {
                    StatsLogin::factory()->create([
                        'project_id' => $project->id,
                        'stats_user_id' => $user->id,
                        'time' => Carbon::instance(fake()->dateTimeBetween($dayStart, $dayEnd)),
                    ]);
                }

                // Payments from existing users
                $payingUsers = StatsUser::query()
                    ->where('project_id', $project->id)
                    ->where('time', '<', $dayStart)
                    ->inRandomOrder()
                    ->limit($paymentCount)
                    ->get();

                foreach ($payingUsers as $user) {
                    StatsPayment::factory()->create([
                        'project_id' => $project->id,
                        'stats_user_id' => $user->id,
                        'currency' => self::DEFAULT_CURRENCY,
                        'time' => Carbon::instance(fake()->dateTimeBetween($dayStart, $dayEnd)),
                    ]);
                }

                // Events from existing users
                $eventNames = StatsCustomEventName::where('project_id', $project->id)->get();
                if ($eventNames->isNotEmpty()) {
                    $eventUsers = StatsUser::query()
                        ->where('project_id', $project->id)
                        ->inRandomOrder()
                        ->limit(rand(3, 8))
                        ->get();

                    foreach ($eventUsers as $user) {
                        $visitor = StatsVisitor::where('project_id', $project->id)->inRandomOrder()->first();
                        StatsCustomEvent::factory()->create([
                            'project_id' => $project->id,
                            'team_id' => $project->team_id,
                            'stats_user_id' => $user->id,
                            'stats_visitor_id' => $visitor?->id,
                            'stats_custom_event_name_id' => $eventNames->random()->id,
                            'time' => Carbon::instance(fake()->dateTimeBetween($dayStart, $dayEnd)),
                        ]);
                    }
                }
            }
        }
    }
}
