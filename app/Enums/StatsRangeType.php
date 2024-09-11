<?php

namespace App\Enums;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use LaracraftTech\LaravelUsefulAdditions\Traits\UsefulEnums;

enum StatsRangeType: string
{
    use UsefulEnums;

    const DATE_FORMAT = 'Y-m-d H:i:s';

    case DAY = 'days';
    case WEEK = 'weeks';
    case MONTH = 'months';
    case YEAR = 'years';

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
                'dateFormat' => self::DAY->dateFormat()['mysql'],
            ],
            self::WEEK->value => [
                'startDateStart' => $rangeStart->startOfWeek()->subWeeks($rangeAmount)->format(self::DATE_FORMAT),
                'endDateStart' => $rangeStart->startOfWeek()->format(self::DATE_FORMAT),
                'endDateEnd' => $rangeStart->endOfWeek()->format(self::DATE_FORMAT),
                'intervalType' => str(self::WEEK->value)->upper()->singular(),
                'intervalEnd' => 'DATE_ADD(ds.date, INTERVAL 6 DAY)', // the end of the current interval
                'dateFormat' => self::WEEK->dateFormat()['mysql'],
            ],
            self::MONTH->value => [
                'startDateStart' => $rangeStart->startOfMonth()->subMonths($rangeAmount)->format(self::DATE_FORMAT),
                'endDateStart' => $rangeStart->startOfMonth()->format(self::DATE_FORMAT),
                'endDateEnd' => $rangeStart->endOfMonth()->format(self::DATE_FORMAT),
                'intervalType' => str(self::MONTH->value)->upper()->singular(),
                'intervalEnd' => 'LAST_DAY(ds.date)', // the end of the current interval
                'dateFormat' => self::MONTH->dateFormat()['mysql'],
            ],
            self::YEAR->value => [
                'startDateStart' => $rangeStart->startOfYear()->subYear($rangeAmount)->format(self::DATE_FORMAT),
                'endDateStart' => $rangeStart->startOfYear()->format(self::DATE_FORMAT),
                'endDateEnd' => $rangeStart->endOfYear()->format(self::DATE_FORMAT),
                'intervalType' => str(self::YEAR->value)->upper()->singular(),
                'intervalEnd' => 'LAST_DAY(DATE_ADD(ds.date, INTERVAL 11 MONTH))', // the end of the current interval
                'dateFormat' => self::YEAR->dateFormat()['mysql'],
            ],
        ];
    }

    public function dateFormat(): array
    {
        return match($this) {
            self::DAY => [
                'mysql' => '%Y-%m-%d',
                'php' => 'Y-m-d',
            ],
            self::WEEK => [
                'mysql' => '%Y-%v',
                'php' => 'o-W',
            ],
            self::MONTH => [
                'mysql' => '%Y-%m',
                'php' => 'Y-m',
            ],
            self::YEAR => [
                'mysql' => '%Y',
                'php' => 'Y',
            ],
            default => throw new \Exception('Unexpected match value'),
        };
    }

    public function rangeStart(CarbonImmutable $date): CarbonImmutable
    {
        return match($this) {
            self::DAY => $date->startOfDay(),
            self::WEEK => $date->startOfWeek(),
            self::MONTH => $date->startOfMonth(),
            self::YEAR => $date->startOfYear(),
            default => throw new \Exception('Unexpected match value'),
        };
    }

    public function defaultRangeAmount(): int
    {
        return match($this) {
            self::DAY => 7,
            self::WEEK => 4,
            self::MONTH => 6,
            self::YEAR => 3,
            default => throw new \Exception('Unexpected match value'),
        };
    }
}
