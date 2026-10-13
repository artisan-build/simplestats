<?php

namespace App\Services;

use App\DTOs\StatsFilterDto;
use App\Models\StatsCustomPropertyName;
use App\Models\StatsCustomPropertyValue;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class StatsCustomPropertyService
{
    /**
     * Expand a property name to every equivalent (same name string) id of the
     * team. Lookups are project-scoped, so the "All projects" view has to
     * merge same-named properties; a selected project keeps only its own id.
     *
     * @return Collection<int, int>
     */
    public static function equivalentNameIds(StatsCustomPropertyName $name, ?int $projectId = null): Collection
    {
        return StatsCustomPropertyName::where('name', $name->name)
            ->when($projectId, fn ($query) => $query->where('project_id', $projectId))
            ->pluck('id');
    }

    /**
     * Expand a property value to every equivalent (same property name + value
     * string) id of the team.
     *
     * @return Collection<int, int>
     */
    public static function equivalentValueIds(StatsCustomPropertyValue $value, ?int $projectId = null): Collection
    {
        return StatsCustomPropertyValue::where('value', $value->value)
            ->whereIn('stats_custom_property_name_id', self::equivalentNameIds($value->name, $projectId))
            ->pluck('id');
    }

    /**
     * The custom property names available as grouping dimensions. Lookups are
     * project-scoped: a selected project shows only its own properties, the
     * "All projects" view merges same-named properties of the team into a
     * single dimension (MIN(id) as a stable representative). Dynamic, unlike
     * StatsService::$statsTypes.
     *
     * A name is returned as soon as it has ever been recorded (any pivot row),
     * regardless of the selected period. This keeps the dimension tabs stable
     * across range changes instead of appearing and disappearing as the period
     * shifts; the grouped table itself still scopes its counts to the range.
     *
     * @return Collection<int, array{id: int, name: string}>
     */
    public static function getPropertyDimensions(StatsFilterDto $filters): Collection
    {
        return StatsCustomPropertyName::query()
            ->when($filters->projectId, fn ($query) => $query->where('project_id', $filters->projectId))
            ->whereExists(fn (QueryBuilder $exists): QueryBuilder => self::propertyPivotExists($exists))
            ->selectRaw('MIN(id) as id, name')
            ->groupBy('name')
            ->orderBy('name')
            ->get()
            ->map(fn (StatsCustomPropertyName $propertyName): array => [
                'id' => $propertyName->id,
                'name' => Str::title(str_replace('_', ' ', $propertyName->name)),
            ])
            ->values();
    }

    /**
     * EXISTS: the name has at least one recorded value. The project_id
     * correlation is redundant (names are project-scoped) but lets the pivot's
     * (project_id, name_id, value_id) index drive the lookup.
     */
    private static function propertyPivotExists(QueryBuilder $exists): QueryBuilder
    {
        return $exists->selectRaw('1')
            ->from('stats_custom_properties as scp')
            ->whereColumn('scp.stats_custom_property_name_id', 'stats_custom_property_names.id')
            ->whereColumn('scp.project_id', 'stats_custom_property_names.project_id');
    }
}
