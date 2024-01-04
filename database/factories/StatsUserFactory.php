<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Instance;
use App\Models\Project;
use App\Models\StatsUser;
use App\Models\Team;

class StatsUserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = StatsUser::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'project_id' => Project::factory(),
            'instance_id' => Instance::factory(),
            'track_source' => $this->faker->word(),
            'track_medium' => $this->faker->word(),
            'track_campaign' => $this->faker->word(),
            'track_term' => $this->faker->word(),
            'track_content' => $this->faker->word(),
        ];
    }
}
