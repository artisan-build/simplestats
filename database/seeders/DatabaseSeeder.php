<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\StatsCustomEvent;
use App\Models\StatsCustomEventName;
use App\Models\StatsLogin;
use App\Models\StatsPayment;
use App\Models\StatsUser;
use App\Models\StatsVisitor;
use App\Models\Team;
use App\Models\User;
use App\Observers\StatsObserver;
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
        $this->call(TestUserSeeder::class);

        $user = User::where('email', config('app.test.user')['email'])->firstOrFail();

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

        StatsObserver::$enabled = false;

        Project::all()->each(function ($project) {
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
                $visitorCount = app()->environment('testing') ? rand(1, 2) : rand(3, 6);
                $visitors = StatsVisitor::factory($visitorCount)->create([
                    'project_id' => $project->id,
                    'time' => $statsUser->time,
                ]);

                $loginTime = Carbon::parse($statsUser->time);
                $loginCount = app()->environment('testing') ? rand(0, 3) : rand(0, 20);
                for ($i = 0; $i < $loginCount; $i++) {
                    StatsLogin::factory()->create([
                        'project_id' => $project->id,
                        'stats_user_id' => $statsUser->id,
                        // The first login time is the same as registration
                        'time' => $loginTime,
                    ]);
                    // increment a random number of days for the next login
                    $loginTime->addDays(rand(1, 28));
                }

                $paymentTime = Carbon::parse($statsUser->time);
                if (rand(0, 9) === 0) {
                    $paymentCount = app()->environment('testing') ? rand(0, 3) : rand(0, 20);
                    for ($i = 0; $i < $paymentCount; $i++) {
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
        });

        StatsObserver::$enabled = true;

        $this->call([
            DataImportSeeder::class,
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
                app()->environment('testing') => 2,
                default => 500,
            },
            'to' => match (true) {
                app()->environment('demo') => 1000,
                app()->environment('testing') => 3,
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
