<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\StatsPayment;
use App\Models\StatsUser;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

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
        $gross = $this->faker->randomFloat(2, -100, 2000);

        return [
            'id' => self::$id++,
            'stats_user_id' => StatsUser::inRandomOrder()->first()->id,
            'team_id' => Team::inRandomOrder()->first()->id,
            'project_id' => Project::inRandomOrder()->first()->id,
            'gross' => $gross,
            'net' => $gross * 0.8,
            'currency' => 'EUR',
            'time' => $date->format('Y-m-d H:i:s'),
            'time_utc' => $date->format('Y-m-d H:i:s'),
        ];
    }
}
