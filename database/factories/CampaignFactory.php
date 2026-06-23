<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;
use RuntimeException;

/**
 * @extends Factory<Campaign>
 */
class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => fn () => Project::factory(),
            'team_id' => fn (array $attributes) => Project::find($attributes['project_id'])?->team_id ?? throw new RuntimeException('Campaign needs a project'),
            'name' => $this->faker->unique()->words(2, true),
        ];
    }
}
