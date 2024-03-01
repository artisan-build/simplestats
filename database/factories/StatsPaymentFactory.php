<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Instance;
use App\Models\Project;
use App\Models\StatsPayment;
use App\Models\StatsUser;
use App\Models\Team;

class StatsPaymentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = StatsPayment::class;

    public static int $id = 1;

    public function initialize()
    {
        self::$id = 1;
        return $this;
    }

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $date = $this->faker->dateTimeBetween(now()->subYears(1), 'now');
        $gross = $this->faker->randomFloat(2, -10, 200);

        return [
            'id' => self::$id++,
            'stats_user_id' => StatsUser::inRandomOrder()->first()->id,
            'team_id' => Team::inRandomOrder()->first()->id,
            'project_id' => Project::inRandomOrder()->first()->id,
            'instance_id' => Instance::inRandomOrder()->first()->id,
            'gross' => $gross,
            'net' => $gross*0.8,
            'currency' => 'EUR',
            'time' => $date->format('Y-m-d H:i:s'),
        ];
    }
}
