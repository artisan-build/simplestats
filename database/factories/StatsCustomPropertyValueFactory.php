<?php

namespace Database\Factories;

use App\Models\StatsCustomPropertyName;
use App\Models\StatsCustomPropertyValue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StatsCustomPropertyValue>
 */
class StatsCustomPropertyValueFactory extends Factory
{
    protected $model = StatsCustomPropertyValue::class;

    public function definition(): array
    {
        $name = StatsCustomPropertyName::factory()->create();

        return [
            'team_id' => $name->team_id,
            'project_id' => $name->project_id,
            'stats_custom_property_name_id' => $name->id,
            'value' => $this->faker->word(),
        ];
    }
}
