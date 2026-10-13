<?php

namespace Database\Factories;

use App\Enums\StatsRangeType;
use App\Models\CustomStatsRangePreset;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomStatsRangePreset>
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
            'name' => 'Last '.fake()->numberBetween(1, 60).' '.fake()->randomElement($rangeTypes),
            'range_amount' => fake()->numberBetween(1, 60),
            'range_type' => fake()->randomElement($rangeTypes),
            'sort_order' => 0,
        ];
    }
}
