<?php

namespace Database\Factories;

use App\Models\Instance;
use App\Models\Project;
use App\Models\StatsLogin;
use App\Models\StatsUser;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

class StatsLoginFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = StatsLogin::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $date = $this->faker->dateTimeBetween(now()->subYears(1), 'now');

        return [
            'stats_user_id' => StatsUser::inRandomOrder()->first()->id,
            'team_id' => Team::inRandomOrder()->first()->id,
            'project_id' => Project::inRandomOrder()->first()->id,
            'instance_id' => Instance::inRandomOrder()->first()->id,
            'time' => $date->format('Y-m-d H:i:s'),
            'time_utc' => $date->format('Y-m-d H:i:s'),
        ];
    }
}
