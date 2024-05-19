<?php

namespace App\Enums;

use Carbon\CarbonImmutable;
use LaracraftTech\LaravelUsefulAdditions\Traits\UsefulEnums;

enum StatsRangeType: string
{
    use UsefulEnums;

    const DATE_FORMAT = 'Y-m-d';

    case DAY = 'days';
    case WEEK = 'weeks';
    case MONTH = 'months';

    public static function conditions(CarbonImmutable $rangeStart, int $rangeAmount): array
    {
        $rangeAmount -= 1;

        return [
            self::DAY->value => [
                'startDateStart' => $rangeStart->startOfDay()->subDays($rangeAmount)->format(self::DATE_FORMAT),
                'endDateStart' => $rangeStart->startOfDay()->format(self::DATE_FORMAT),
                'endDateEnd' => $rangeStart->endOfDay()->format(self::DATE_FORMAT),
                'intervalType' => str(self::DAY->value)->upper()->singular(),
                'intervalEnd' => 'ds.date', // the end of the current interval
                'dateFormat' => self::dateFormats()[self::DAY->value]['mysql'],
            ],
            self::WEEK->value => [
                'startDateStart' => $rangeStart->startOfWeek()->subWeeks($rangeAmount)->format(self::DATE_FORMAT),
                'endDateStart' => $rangeStart->startOfWeek()->format(self::DATE_FORMAT),
                'endDateEnd' => $rangeStart->endOfWeek()->format(self::DATE_FORMAT),
                'intervalType' => str(self::WEEK->value)->upper()->singular(),
                'intervalEnd' => 'DATE_ADD(ds.date, INTERVAL 6 DAY)', // the end of the current interval
                'dateFormat' => self::dateFormats()[self::WEEK->value]['mysql'],
            ],
            self::MONTH->value => [
                'startDateStart' => $rangeStart->startOfMonth()->subMonths($rangeAmount)->format(self::DATE_FORMAT),
                'endDateStart' => $rangeStart->startOfMonth()->format(self::DATE_FORMAT),
                'endDateEnd' => $rangeStart->endOfMonth()->format(self::DATE_FORMAT),
                'intervalType' => str(self::MONTH->value)->upper()->singular(),
                'intervalEnd' => 'LAST_DAY(ds.date)', // the end of the current interval
                'dateFormat' => self::dateFormats()[self::MONTH->value]['mysql'],
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
