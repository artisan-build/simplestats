<?php

namespace App\Enums;

use Carbon\CarbonImmutable;
use LaracraftTech\LaravelUsefulAdditions\Traits\UsefulEnums;

enum StatsRange: string
{
    use UsefulEnums;

    case DAY = 'days';
    case WEEK = 'weeks';
    case MONTH = 'months';

    public static function conditions(CarbonImmutable $rangeStart, int $rangeAmount): array
    {
        $rangeAmount -= 1;
        return [
            self::DAY->value => [
                $rangeStart->startOfDay()->subDays($rangeAmount)->format('Y-m-d'),
                $rangeStart->startOfDay()->format('Y-m-d'),
                $rangeStart->endOfDay()->format('Y-m-d'),
                str(self::DAY->value)->upper()->singular(),
                'ds.date', // the end of the current interval
                '%Y-%m-%d'
            ],
            self::WEEK->value => [
                $rangeStart->startOfWeek()->subWeeks($rangeAmount)->format('Y-m-d'),
                $rangeStart->startOfWeek()->format('Y-m-d'),
                $rangeStart->endOfWeek()->format('Y-m-d'),
                str(self::WEEK->value)->upper()->singular(),
                'DATE_ADD(ds.date, INTERVAL 6 DAY)', // the end of the current interval
                '%Y-%v'
            ],
            self::MONTH->value => [
                $rangeStart->startOfMonth()->subMonths($rangeAmount)->format('Y-m-d'),
                $rangeStart->startOfMonth()->format('Y-m-d'),
                $rangeStart->endOfMonth()->format('Y-m-d'),
                str(self::MONTH->value)->upper()->singular(),
                'LAST_DAY(ds.date)', // the end of the current interval
                '%Y-%m'
            ],
        ];
    }

    public static function ranges()
    {
        return [
            self::DAY->value => range(1,90),
            self::WEEK->value => range(1,54),
            self::MONTH->value => range(1,12),
        ];
    }
}
