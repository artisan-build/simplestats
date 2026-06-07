<?php

namespace App\Services;

use App\DTOs\StatsFilterDto;
use App\Enums\StatsRangeType;
use App\Models\StatsCustomPropertyName;
use App\Models\StatsCustomPropertyValue;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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
     * Only names with data in the selected period are returned, mirroring what
     * the grouped table can show: a visitor or registration tracked in range,
     * or revenue paid in range by a property-bound user. Names without any of
     * these (e.g. one-off test properties) produce no tab.
     *
     * @return Collection<int, array{id: int, name: string}>
     */
    public static function getPropertyDimensions(StatsFilterDto $filters): Collection
    {
        [
            'startDateStart' => $startDate,
            'endDateEnd' => $endDate,
        ] = StatsRangeType::conditions($filters->rangeStart, $filters->rangeAmount)[$filters->rangeType->value];

        return StatsCustomPropertyName::query()
            ->when($filters->projectId, fn ($query) => $query->where('project_id', $filters->projectId))
            ->where(function ($query) use ($startDate, $endDate) {
                $query
                    ->whereExists(fn ($exists) => self::propertyVisitorsInRange($exists, $startDate, $endDate))
                    ->orWhereExists(fn ($exists) => self::propertyUsersInRange($exists, $startDate, $endDate))
                    ->orWhereExists(fn ($exists) => self::propertyPaymentsInRange($exists, $startDate, $endDate));
            })
            ->selectRaw('MIN(id) as id, name')
            ->groupBy('name')
            ->orderBy('name')
            ->get()
            ->map(fn (StatsCustomPropertyName $propertyName) => [
                'id' => $propertyName->id,
                'name' => Str::title(str_replace('_', ' ', $propertyName->name)),
            ])
            ->values();
    }

    /**
     * EXISTS: a visitor tracked in range carries the property. The project_id
     * correlation is redundant (names are project-scoped) but lets the pivot's
     * (project_id, name_id, value_id) index drive the lookup.
     */
    private static function propertyVisitorsInRange(QueryBuilder $exists, string $startDate, string $endDate): QueryBuilder
    {
        return self::propertyPivotInRange($exists)
            ->join('stats_visitors as sv', function (JoinClause $join) {
                $join->on(DB::raw('CAST(sv.id AS varchar)'), '=', 'scp.propertyable_id')
                    ->on('sv.project_id', '=', 'scp.project_id');
            })
            ->where('scp.propertyable_type', 'stats_visitor')
            ->whereBetween('sv.time', [$startDate, $endDate]);
    }

    /**
     * EXISTS: a user registered in range carries the property.
     */
    private static function propertyUsersInRange(QueryBuilder $exists, string $startDate, string $endDate): QueryBuilder
    {
        return self::propertyPivotInRange($exists)
            ->join('stats_users as su', function (JoinClause $join) {
                $join->on('su.id', '=', 'scp.propertyable_id')
                    ->on('su.project_id', '=', 'scp.project_id');
            })
            ->where('scp.propertyable_type', 'stats_user')
            ->whereBetween('su.time', [$startDate, $endDate]);
    }

    /**
     * EXISTS: a property-bound user (whenever registered) paid in range, so the
     * grouped table would show revenue for this property.
     */
    private static function propertyPaymentsInRange(QueryBuilder $exists, string $startDate, string $endDate): QueryBuilder
    {
        return self::propertyPivotInRange($exists)
            ->join('stats_users as su', function (JoinClause $join) {
                $join->on('su.id', '=', 'scp.propertyable_id')
                    ->on('su.project_id', '=', 'scp.project_id');
            })
            ->join('stats_payments as pay', function (JoinClause $join) {
                $join->on('pay.stats_user_id', '=', 'su.id')
                    ->on('pay.project_id', '=', 'su.project_id');
            })
            ->where('scp.propertyable_type', 'stats_user')
            ->whereBetween('pay.time', [$startDate, $endDate]);
    }

    private static function propertyPivotInRange(QueryBuilder $exists): QueryBuilder
    {
        return $exists->selectRaw('1')
            ->from('stats_custom_properties as scp')
            ->whereColumn('scp.stats_custom_property_name_id', 'stats_custom_property_names.id')
            ->whereColumn('scp.project_id', 'stats_custom_property_names.project_id');
    }
}
