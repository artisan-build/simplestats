<?php

namespace Database\Factories;

use App\Models\AdSpend;
use App\Models\Project;
use App\Models\StatsTrackCampaign;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

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
            'team_id' => fn () => Team::inRandomOrder()->first()?->id ?? Team::factory(),
            'project_id' => fn () => Project::inRandomOrder()->first()?->id ?? Project::factory(),
            'stats_track_campaign_id' => fn () => StatsTrackCampaign::inRandomOrder()->first()?->id,
            'amount' => $this->faker->numberBetween(1000, 500000),
            'currency' => 'EUR',
            'starts_on' => now()->subDays(30)->format('Y-m-d'),
            'ends_on' => now()->format('Y-m-d'),
        ];
    }
}
