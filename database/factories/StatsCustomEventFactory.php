<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\StatsCustomEvent;
use App\Models\StatsCustomEventName;
use App\Models\StatsUser;
use App\Models\StatsVisitor;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

class StatsCustomEventFactory extends Factory
{
    protected $model = StatsCustomEvent::class;

    public static int $id = 1;

    public function initialize()
    {
        self::$id = 1;

        return $this;
    }

    public function definition(): array
    {
        $date = $this->faker->dateTimeBetween(now()->subYears(1), 'now');

        return [
            'id' => self::$id++,
            'stats_user_id' => StatsUser::inRandomOrder()->first()->id,
            'stats_visitor_id' => StatsVisitor::inRandomOrder()->first()?->id,
            'stats_custom_event_name_id' => StatsCustomEventName::inRandomOrder()->first()->id,
            'team_id' => Team::inRandomOrder()->first()->id,
            'project_id' => Project::inRandomOrder()->first()->id,
            'value' => $this->faker->optional()->randomFloat(2, 0, 1000),
            'time' => $date->format('Y-m-d H:i:s'),
            'time_utc' => $date->format('Y-m-d H:i:s'),
        ];
    }
}
