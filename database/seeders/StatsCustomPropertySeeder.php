<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\StatsCustomProperty;
use App\Models\StatsCustomPropertyName;
use App\Models\StatsCustomPropertyValue;
use App\Models\StatsUser;
use App\Models\StatsVisitor;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class StatsCustomPropertySeeder extends Seeder
{
    /**
     * Seed custom properties as demo data: user-bound `subscription` + `company`,
     * and visitor-bound `ab_test`. This exercises both the user and the visitor
     * side of the polymorphic stats_custom_properties pivot.
     *
     * Runs last so it covers future data too.
     */
    public function run(): void
    {
        $userSchema = [
            // weighted so "free" dominates, like a real funnel
            'subscription' => ['free', 'free', 'free', 'free', 'pro', 'pro', 'enterprise'],
            'company' => ['Acme Inc', 'Globex', 'Initech', 'Umbrella Corp', 'Hooli', 'Stark Industries'],
        ];
        $visitorSchema = [
            'ab_test' => ['A', 'B'],
        ];

        Project::all()->each(function (Project $project) use ($userSchema, $visitorSchema) {
            $now = now();
            $lookups = $this->seedPropertyLookups($project, [...$userSchema, ...$visitorSchema]);

            // Every user gets a subscription; ~50% also get a company.
            $rows = [];
            StatsUser::where('project_id', $project->id)->pluck('id')
                ->each(function ($entityId) use (&$rows, $project, $userSchema, $lookups, $now) {
                    $rows[] = $this->propertyRow($project, 'stats_user', $entityId, 'subscription', Arr::random($userSchema['subscription']), $lookups, $now);

                    if (rand(0, 1) === 1) {
                        $rows[] = $this->propertyRow($project, 'stats_user', $entityId, 'company', Arr::random($userSchema['company']), $lookups, $now);
                    }
                });

            // Every visitor gets an A/B test variant.
            StatsVisitor::where('project_id', $project->id)->pluck('id')
                ->each(function ($entityId) use (&$rows, $project, $visitorSchema, $lookups, $now) {
                    $rows[] = $this->propertyRow($project, 'stats_visitor', $entityId, 'ab_test', Arr::random($visitorSchema['ab_test']), $lookups, $now);
                });

            collect($rows)->chunk(1000)->each(fn ($chunk) => StatsCustomProperty::insert($chunk->all()));
        });
    }

    /**
     * Create the property name + value lookup rows for a project.
     *
     * @param  array<string, list<string>>  $schema
     * @return array<string, array{id: int, values: array<string, int>}>
     */
    protected function seedPropertyLookups(Project $project, array $schema): array
    {
        $lookups = [];

        foreach ($schema as $name => $values) {
            $propertyName = StatsCustomPropertyName::firstOrCreate([
                'team_id' => $project->team_id,
                'project_id' => $project->id,
                'name' => $name,
            ]);

            $lookups[$name] = ['id' => $propertyName->id, 'values' => []];

            foreach (array_unique($values) as $value) {
                $propertyValue = StatsCustomPropertyValue::firstOrCreate([
                    'team_id' => $project->team_id,
                    'project_id' => $project->id,
                    'stats_custom_property_name_id' => $propertyName->id,
                    'value' => $value,
                ]);

                $lookups[$name]['values'][$value] = $propertyValue->id;
            }
        }

        return $lookups;
    }

    /**
     * Build a single stats_custom_properties insert row.
     *
     * @param  array<string, array{id: int, values: array<string, int>}>  $lookups
     * @return array<string, mixed>
     */
    protected function propertyRow(Project $project, string $propertyableType, int|string $propertyableId, string $name, string $value, array $lookups, CarbonInterface $now): array
    {
        return [
            'team_id' => $project->team_id,
            'project_id' => $project->id,
            'propertyable_type' => $propertyableType,
            'propertyable_id' => $propertyableId,
            'stats_custom_property_name_id' => $lookups[$name]['id'],
            'stats_custom_property_value_id' => $lookups[$name]['values'][$value],
            'time' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
}
