<?php

namespace Database\Factories;

use App\Enums\StatsRangeType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CustomStatsRangePreset>
 */
class CustomStatsRangePresetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $rangeTypes = [
            StatsRangeType::DAY->value,
            StatsRangeType::WEEK->value,
            StatsRangeType::MONTH->value,
            StatsRangeType::YEAR->value,
        ];

        return [
            'user_id' => User::factory(),
            'name' => 'Last '.$this->faker->numberBetween(1, 60).' '.$this->faker->randomElement($rangeTypes),
            'range_amount' => $this->faker->numberBetween(1, 60),
            'range_type' => $this->faker->randomElement($rangeTypes),
            'sort_order' => 0,
        ];
    }
}
