<?php

namespace Database\Factories;

use App\Enums\DataImportType;
use App\Models\DataImport;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DataImport>
 */
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
            'created_by' => User::factory(),
            'type' => fake()->randomElement(DataImportType::cases()),
            'rows_max' => fake()->numberBetween(100, 10000),
            'rows_imported' => fake()->numberBetween(0, 100),
        ];
    }
}
