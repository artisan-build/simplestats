<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\StatsCustomEvent;
use App\Models\StatsCustomEventName;
use App\Models\StatsLogin;
use App\Models\StatsPayment;
use App\Models\StatsUser;
use App\Models\StatsVisitor;
use App\Services\StatsService;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class HistoricalStatsSeeder extends Seeder
{
    const DEFAULT_CURRENCY = 'EUR';

    /**
     * Seed historical stats data (users, visitors, logins, payments and
     * custom events) for every existing project.
     */
    public function run(): void
    {
        $timezone = auth()->user()->team->timezone;

        // One transaction per project so the thousands of individual inserts are
        // committed in a single batch instead of one fsync per row.
        Project::all()->each(fn ($project) => DB::transaction(function () use ($project, $timezone) {
            $eventNames = collect(['signup_click', 'purchase_click', 'page_view', 'add_to_cart', 'checkout_start'])
                ->map(fn ($name) => StatsCustomEventName::firstOrCreate([
                    'team_id' => $project->team_id,
                    'project_id' => $project->id,
                    'name' => $name,
                ]));

            $tracksSubscription = (bool) ($project->usage & StatsService::TRACKING_USAGE_BITS_SUBSCRIPTION);

            $count = $this->getUserSeedCount();

            // Logins have no lookup columns and their counter is just the loop
            // index, so we collect plain rows and bulk insert them per project
            // instead of paying the full model/event cost for each of the many
            // thousands of logins (the dominant volume here).
            $loginRows = [];

            StatsUser::factory(rand($count['from'], $count['to']))->create([
                'team_id' => $project->team_id,
                'project_id' => $project->id,
            ])->each(function ($statsUser) use ($project, $eventNames, $timezone, $tracksSubscription, &$loginRows) {
                $visitorCount = app()->environment('testing') ? rand(1, 2) : rand(3, 6);
                $visitors = StatsVisitor::factory($visitorCount)->create([
                    'team_id' => $project->team_id,
                    'project_id' => $project->id,
                    'time' => $statsUser->time,
                ]);

                $loginTime = Carbon::parse($statsUser->time);
                $loginCount = app()->environment('testing') ? rand(0, 3) : rand(0, 20);
                for ($i = 0; $i < $loginCount; $i++) {
                    $loginRows[] = [
                        'team_id' => $project->team_id,
                        'project_id' => $project->id,
                        'stats_user_id' => $statsUser->id,
                        // counter is the per-user sequence number (1st, 2nd, ... login)
                        'counter' => $i + 1,
                        ...$this->timeColumns($loginTime, $timezone),
                    ];
                    // increment a random number of days for the next login
                    $loginTime->addDays(rand(1, 28));
                }

                if ($tracksSubscription && rand(1, 100) <= 12) {
                    $this->seedSubscription($project, $statsUser);
                } elseif (rand(0, 9) === 0) {
                    $paymentTime = Carbon::parse($statsUser->time);
                    $paymentCount = app()->environment('testing') ? rand(0, 3) : rand(0, 20);
                    for ($i = 0; $i < $paymentCount; $i++) {
                        StatsPayment::factory()->create([
                            'team_id' => $project->team_id,
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

            collect($loginRows)->chunk(1000)->each(fn ($chunk) => StatsLogin::insert($chunk->all()));
        }));
    }

    /**
     * Seed a recurring subscription as a chain of renewal payments at the chosen
     * interval, starting from the user's first-seen time until now (some chains
     * stop early, which the MRR aggregation reads as churn). The price stays
     * stable across renewals apart from the occasional plan change, so the MRR
     * line is clean while still producing realistic expansion/contraction.
     */
    protected function seedSubscription(Project $project, StatsUser $statsUser): void
    {
        $yearly = rand(1, 5) === 1;
        $now = now();
        $time = Carbon::parse($statsUser->time);

        $amount = $yearly ? rand(8000, 120000) / 100 : rand(900, 9900) / 100;

        $maxRenewals = $yearly ? 3 : 24;
        $renewals = app()->environment('testing') ? rand(1, 3) : rand(1, $maxRenewals);

        for ($i = 0; $i < $renewals && $time->lessThanOrEqualTo($now); $i++) {
            if ($i > 0 && rand(1, 100) <= 12) {
                $amount = round($amount * (rand(0, 1) === 1 ? rand(110, 150) : rand(60, 90)) / 100, 2);
            }

            $factory = $yearly
                ? StatsPayment::factory()->subscriptionYearly()
                : StatsPayment::factory()->subscriptionMonthly();

            $factory->create([
                'team_id' => $project->team_id,
                'project_id' => $project->id,
                'stats_user_id' => $statsUser->id,
                'currency' => self::DEFAULT_CURRENCY,
                'gross' => $amount,
                'net' => round($amount * 0.8, 2),
                'time' => $time->copy(),
            ]);

            // Jitter keeps renewals within the MRR grace window of the interval.
            $time = $yearly
                ? $time->copy()->addYear()->addDays(rand(-2, 2))
                : $time->copy()->addMonth()->addDays(rand(-2, 2));
        }
    }

    /**
     * Build the team-timezone-aware time columns the BaseStat time mutator would
     * otherwise produce, for use in bulk inserts that bypass the model.
     *
     * @return array{time: string, time_utc: string}
     */
    protected function timeColumns(CarbonInterface $time, string $timezone): array
    {
        return [
            'time' => $time->copy()->tz($timezone)->format('Y-m-d H:i:s'),
            'time_utc' => $time->copy()->tz('UTC')->format('Y-m-d H:i:s'),
        ];
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
}
