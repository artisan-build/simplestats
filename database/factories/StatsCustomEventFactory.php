<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\StatsCustomEvent;
use App\Models\StatsCustomEventName;
use App\Models\StatsUser;
use App\Models\StatsVisitor;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StatsCustomEvent>
 */
class StatsCustomEventFactory extends Factory
{
    protected $model = StatsCustomEvent::class;

    public static int $id = 1;

    public function initialize(): static
    {
        self::$id = 1;

        return $this;
    }

    public function definition(): array
    {
        $date = fake()->dateTimeBetween(now()->subYears(1), 'now');

        return [
            'id' => self::$id++,
            // Lazy closures so the queries are skipped whenever the seeder/test
            // overrides the key. Direct expressions would run on every create().
            'stats_user_id' => fn () => StatsUser::inRandomOrder()->first()->id,
            'stats_visitor_id' => fn () => StatsVisitor::inRandomOrder()->first()?->id,
            'stats_custom_event_name_id' => fn () => StatsCustomEventName::inRandomOrder()->first()->id,
            'team_id' => fn () => Team::inRandomOrder()->first()->id,
            'project_id' => fn () => Project::inRandomOrder()->first()->id,
            'time' => $date->format('Y-m-d H:i:s'),
            'time_utc' => $date->format('Y-m-d H:i:s'),
        ];
    }
}
