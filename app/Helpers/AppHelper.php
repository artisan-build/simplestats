<?php

namespace App\Helpers;

use App\DTOs\StatsFilterDto;
use App\Enums\ComparisonTypes;
use Exception;
use Illuminate\Support\Facades\Config;

class AppHelper
{
    public static function ensureTeamTimezone(): void
    {
        $teamTimezone = auth()->user()->team->timezone;

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

        if ($filters->comparison === ComparisonTypes::PERIOD) {
            $prevFilters->rangeStart = $prevFilters->rangeType->getPrevStartForComparison($prevFilters->rangeStart);
        } else {
            $prevFilters->rangeStart = $prevFilters->rangeStart->subYear();
        }

        return $prevFilters;
    }
}
