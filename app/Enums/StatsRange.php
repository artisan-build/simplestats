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
        return [
            self::DAY->value => [
                $rangeStart->startOfDay()->subDays($rangeAmount-1)->format('Y-m-d'),
                $rangeStart->endOfDay()->format('Y-m-d'),
                str(self::DAY->value)->upper()->singular(),
                '%Y-%m-%d'
            ],
            self::WEEK->value => [
                $rangeStart->startOfWeek()->subWeeks($rangeAmount-2)->format('Y-m-d'),
                $rangeStart->endOfWeek()->format('Y-m-d'),
                str(self::WEEK->value)->upper()->singular(),
                '%Y-%v'
            ],
            self::MONTH->value => [
                $rangeStart->startOfMonth()->subMonths($rangeAmount-2)->format('Y-m-d'),
                $rangeStart->endOfMonth()->format('Y-m-d'),
                str(self::MONTH->value)->upper()->singular(),
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
