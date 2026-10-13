<?php

namespace Database\Factories;

use App\Models\AdSpend;
use App\Models\Campaign;
use Illuminate\Database\Eloquent\Factories\Factory;
use RuntimeException;

/**
 * @extends Factory<AdSpend>
 */
class AdSpendFactory extends Factory
{
    protected $model = AdSpend::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'campaign_id' => fn () => Campaign::factory(),
            'team_id' => fn (array $attributes) => Campaign::find($attributes['campaign_id'])?->team_id ?? throw new RuntimeException('AdSpend needs a campaign'),
            'amount' => fake()->numberBetween(1000, 500000),
            'currency' => 'EUR',
            'starts_on' => now()->subDays(30)->format('Y-m-d'),
            'ends_on' => now()->format('Y-m-d'),
        ];
    }
}
