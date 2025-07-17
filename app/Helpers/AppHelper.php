<?php

namespace App\Helpers;

use App\DTOs\StatsFilterDto;
use App\Enums\ComparisonType;
use Exception;
use Illuminate\Support\Facades\Config;

class AppHelper
{
    public static function ensureTeamTimezone(): void
    {
        $teamTimezone = auth()->user()->team->timezone ?: 'UTC';

        if (config('app.timezone') !== $teamTimezone) {
            Config::set('app.timezone', $teamTimezone);
            date_default_timezone_set($teamTimezone);
        }
    }

    /**
     * @throws Exception
     */
    public static function createPrevFiltersForComparison(StatsFilterDto $filters): StatsFilterDto
    {
        $prevFilters = clone $filters;

        if ($filters->comparison === ComparisonType::PERIOD->value) {
            $prevFilters->rangeStart = $prevFilters->rangeType->getPrevStartForComparison($prevFilters->rangeStart);
        } else {
            $prevFilters->rangeStart = $prevFilters->rangeStart->subYear();
        }

        return $prevFilters;
    }

    public static function getDBDriver(): string
    {
        $connectionName = config('database.default');
        return config("database.connections.$connectionName.driver");
    }
}
