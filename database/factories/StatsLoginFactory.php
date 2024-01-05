<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Instance;
use App\Models\Project;
use App\Models\StatsLogin;
use App\Models\StatsUser;
use App\Models\Team;

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
        return [
            'stats_user_id' => StatsUser::factory(),
            'team_id' => Team::factory(),
            'project_id' => Project::factory(),
            'instance_id' => Instance::factory(),
            'created_at' => $this->faker->date(),
        ];
    }
}
