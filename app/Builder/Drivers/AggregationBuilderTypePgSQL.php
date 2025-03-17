<?php

namespace App\Builder\Drivers;

use App\Enums\StatsRangeType;

class AggregationBuilderTypePgSQL implements AggregationBuilderType
{
    public function getDateSelect(): string
    {
        return "':startDateStart'::timestamp";
    }

    public function getDateInterval(string $calc, string $date, int $amount, string $interval): string
    {
        $func = ($calc === 'add') ? '+' : '-';
        return "$date $func INTERVAL '$amount $interval'";
    }

    public function getDateFormat(string $date, string $format): string
    {
        return "TO_CHAR($date, '$format')";
    }

    public function getDateFormats(): array
    {
        return [
            StatsRangeType::HOUR->value => 'HH24:00',
            StatsRangeType::DAY->value => 'YYYY-MM-DD',
            StatsRangeType::WEEK->value => 'IYYY-IW',
            StatsRangeType::MONTH->value => 'YYYY-MM',
            StatsRangeType::YEAR->value => 'YYYY',
        ];
    }

    public function getDateFormatsClear(): array
    {
        return [
            StatsRangeType::HOUR->value => 'YYYY-MM-DD HH24:00',
            StatsRangeType::DAY->value => 'YYYY-MM-DD',
            StatsRangeType::WEEK->value => 'YYYY-MM-DD',
            StatsRangeType::MONTH->value => 'YYYY-MM-DD',
            StatsRangeType::YEAR->value => 'YYYY-MM-DD',
        ];
    }
}
