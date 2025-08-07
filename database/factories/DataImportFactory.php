<?php

namespace Database\Factories;

use App\Models\DataImport;
use App\Models\Job;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DataImportFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = DataImport::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'project_id' => Project::factory(),
            'job_id' => Job::factory(),
            'user_id' => User::factory(),
            'id_from' => $this->faker->numberBetween(-10000, 10000),
            'id_to' => $this->faker->numberBetween(-10000, 10000),
            'type' => $this->faker->word(),
            'rows_imported' => $this->faker->numberBetween(-10000, 10000),
        ];
    }
}
