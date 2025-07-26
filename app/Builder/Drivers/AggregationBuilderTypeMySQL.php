<?php

namespace App\Builder\Drivers;

use App\Enums\StatsRangeType;
use App\Enums\TrackablePerson;

class AggregationBuilderTypeMySQL implements AggregationBuilderType
{
    public function getDateSelect(): string
    {
        return "CAST(':startDateStart' AS DATETIME)";
    }

    public function getDateInterval(string $calc, string $date, int $amount, string $interval): string
    {
        $func = ($calc === 'add') ? 'DATE_ADD' : 'DATE_SUB';

        return "$func($date, INTERVAL $amount $interval)";
    }

    public function getDistinctFields(array $fields): string
    {
        return implode(', ', $fields);
    }

    public function getDateFormat(string $date, string $format): string
    {
        return "DATE_FORMAT($date, '$format')";
    }

    public function getDateFormats(): array
    {
        return [
            StatsRangeType::HOUR->value => '%H:00',
            StatsRangeType::DAY->value => '%Y-%m-%d',
            StatsRangeType::WEEK->value => '%Y-%v',
            StatsRangeType::MONTH->value => '%Y-%m',
            StatsRangeType::YEAR->value => '%Y',
        ];
    }

    public function getDateFormatsClear(): array
    {
        return [
            StatsRangeType::HOUR->value => '%Y-%m-%d %H:00',
            StatsRangeType::DAY->value => '%Y-%m-%d',
            StatsRangeType::WEEK->value => '%Y-%m-%d',
            StatsRangeType::MONTH->value => '%Y-%m-%d',
            StatsRangeType::YEAR->value => '%Y-%m-%d',
        ];
    }

    public function getGroupedOrderBy(string $sort, TrackablePerson $trackablePerson): string
    {
        $oder = "ORDER BY $sort IS NULL, $sort DESC, net IS NULL, net DESC";

        if ($trackablePerson === TrackablePerson::USER) {
            $oder .= ', reg IS NULL, reg DESC';
        }

        $oder .= ', visitors DESC;';

        return $oder;
    }
}
