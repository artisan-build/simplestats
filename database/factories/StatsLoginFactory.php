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
        $date = $this->faker->dateTimeBetween(now()->subYears(1), 'now');

        // Sommerzeitumstellung - Vermeiden von 02:00 - 02:59 am letzten Sonntag im März
        if ($date->format('m-d') == '03-26' && $date->format('H') == '02') {
            $date->modify('+1 hour');
        }

        // Winterzeitumstellung - Vermeiden von 02:00 - 02:59 am letzten Sonntag im Oktober
        if ($date->format('m-d') == '10-26' && $date->format('H') == '02') {
            $date->modify('+1 hour');
        }

        return [
            'stats_user_id' => StatsUser::inRandomOrder()->first()->id,
            'team_id' => Team::inRandomOrder()->first()->id,
            'project_id' => Project::inRandomOrder()->first()->id,
            'instance_id' => Instance::inRandomOrder()->first()->id,
            'created_at' => $date->format('Y-m-d H:i:s'),
        ];
    }
}
