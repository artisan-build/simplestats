<?php

namespace App\Enums;

use Carbon\CarbonImmutable;
use LaracraftTech\LaravelUsefulAdditions\Traits\UsefulEnums;

enum StatsRangeType: string
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
                self::dateFormats()[self::DAY->value]['mysql'],
            ],
            self::WEEK->value => [
                $rangeStart->startOfWeek()->subWeeks($rangeAmount)->format('Y-m-d'),
                $rangeStart->startOfWeek()->format('Y-m-d'),
                $rangeStart->endOfWeek()->format('Y-m-d'),
                str(self::WEEK->value)->upper()->singular(),
                'DATE_ADD(ds.date, INTERVAL 6 DAY)', // the end of the current interval
                self::dateFormats()[self::WEEK->value]['mysql'],
            ],
            self::MONTH->value => [
                $rangeStart->startOfMonth()->subMonths($rangeAmount)->format('Y-m-d'),
                $rangeStart->startOfMonth()->format('Y-m-d'),
                $rangeStart->endOfMonth()->format('Y-m-d'),
                str(self::MONTH->value)->upper()->singular(),
                'LAST_DAY(ds.date)', // the end of the current interval
                self::dateFormats()[self::MONTH->value]['mysql'],
            ],
        ];
    }

    public static function dateFormats(): array
    {
        return [
            self::DAY->value => [
                'mysql' => '%Y-%m-%d',
                'php' => 'Y-m-d',
            ],
            self::WEEK->value => [
                'mysql' => '%Y-%v',
                'php' => 'o-W',
            ],
            self::MONTH->value => [
                'mysql' => '%Y-%m',
                'php' => 'Y-m',
            ],
        ];
    }

    public function defaultRangeAmount(): int
    {
        return match($this) {
            self::DAY => 7,
            self::WEEK => 4,
            self::MONTH => 6,
            default => throw new \Exception('Unexpected match value'),
        };
    }
}
