<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\StatsCustomPropertyName;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StatsCustomPropertyName>
 */
class StatsCustomPropertyNameFactory extends Factory
{
    protected $model = StatsCustomPropertyName::class;

    public function definition(): array
    {
        $project = Project::inRandomOrder()->first();

        return [
            'team_id' => $project->team_id,
            'project_id' => $project->id,
            'name' => fake()->unique()->word(),
        ];
    }
}
