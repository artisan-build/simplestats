<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\StatsCustomEventName;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

class StatsCustomEventNameFactory extends Factory
{
    protected $model = StatsCustomEventName::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::inRandomOrder()->first()->id,
            'project_id' => Project::inRandomOrder()->first()->id,
            'name' => $this->faker->word(),
        ];
    }
}
