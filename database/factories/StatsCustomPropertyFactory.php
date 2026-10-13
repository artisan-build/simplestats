<?php

namespace Database\Factories;

use App\Models\StatsCustomProperty;
use App\Models\StatsCustomPropertyValue;
use App\Models\StatsUser;
use App\Models\StatsVisitor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StatsCustomProperty>
 */
class StatsCustomPropertyFactory extends Factory
{
    protected $model = StatsCustomProperty::class;

    public function definition(): array
    {
        $value = StatsCustomPropertyValue::factory()->create();

        $user = StatsUser::factory()->create([
            'team_id' => $value->team_id,
            'project_id' => $value->project_id,
        ]);

        return [
            'team_id' => $value->team_id,
            'project_id' => $value->project_id,
            'propertyable_type' => 'stats_user',
            'propertyable_id' => $user->id,
            'stats_custom_property_name_id' => $value->stats_custom_property_name_id,
            'stats_custom_property_value_id' => $value->id,
            'time' => now(),
        ];
    }

    /**
     * Attach the property to a StatsVisitor instead of a StatsUser.
     */
    public function forVisitor(): static
    {
        return $this->state(function (array $attributes): array {
            $visitor = StatsVisitor::factory()->create([
                'team_id' => $attributes['team_id'],
                'project_id' => $attributes['project_id'],
            ]);

            return [
                'propertyable_type' => 'stats_visitor',
                'propertyable_id' => $visitor->id,
            ];
        });
    }
}
