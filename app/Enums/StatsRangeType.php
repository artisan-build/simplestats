<?php

namespace App\Enums;

use App\Builder\Drivers\AggregationBuilderType;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use LaracraftTech\LaravelUsefulAdditions\Traits\UsefulEnums;

enum StatsRangeType: string
{
    use UsefulEnums;

    const DATE_FORMAT = 'Y-m-d H:i:s';

    case HOUR = 'hours';
    case DAY = 'days';
    case WEEK = 'weeks';
    case MONTH = 'months';
    case YEAR = 'years';

    public static function conditions(CarbonInterface $rangeStart, int $rangeAmount): array
    {
        $rangeAmount -= 1;

        return [
            self::HOUR->value => [
                'startDateStart' => $rangeStart->startOfHour()->subHours($rangeAmount)->format(self::DATE_FORMAT),
                'endDateStart' => $rangeStart->startOfHour()->format(self::DATE_FORMAT),
                'endDateEnd' => $rangeStart->endOfHour()->format(self::DATE_FORMAT),
                'intervalType' => str(self::HOUR->value)->singular(),
                'dateFormat' => self::HOUR->dateFormat()['db'],
                'dateFormatClear' => self::HOUR->dateFormatClear()['db'],
            ],
            self::DAY->value => [
                'startDateStart' => $rangeStart->startOfDay()->subDays($rangeAmount)->format(self::DATE_FORMAT),
                'endDateStart' => $rangeStart->startOfDay()->format(self::DATE_FORMAT),
                'endDateEnd' => $rangeStart->endOfDay()->format(self::DATE_FORMAT),
                'intervalType' => str(self::DAY->value)->singular(),
                'dateFormat' => self::DAY->dateFormat()['db'],
                'dateFormatClear' => self::DAY->dateFormatClear()['db'],
            ],
            self::WEEK->value => [
                'startDateStart' => $rangeStart->startOfWeek()->subWeeks($rangeAmount)->format(self::DATE_FORMAT),
                'endDateStart' => $rangeStart->startOfWeek()->format(self::DATE_FORMAT),
                'endDateEnd' => $rangeStart->endOfWeek()->format(self::DATE_FORMAT),
                'intervalType' => str(self::WEEK->value)->singular(),
                'dateFormat' => self::WEEK->dateFormat()['db'],
                'dateFormatClear' => self::WEEK->dateFormatClear()['db'],
            ],
            self::MONTH->value => [
                'startDateStart' => $rangeStart->startOfMonth()->subMonths($rangeAmount)->format(self::DATE_FORMAT),
                'endDateStart' => $rangeStart->startOfMonth()->format(self::DATE_FORMAT),
                'endDateEnd' => $rangeStart->endOfMonth()->format(self::DATE_FORMAT),
                'intervalType' => str(self::MONTH->value)->singular(),
                'dateFormat' => self::MONTH->dateFormat()['db'],
                'dateFormatClear' => self::MONTH->dateFormatClear()['db'],
            ],
            self::YEAR->value => [
                'startDateStart' => $rangeStart->startOfYear()->subYear($rangeAmount)->format(self::DATE_FORMAT),
                'endDateStart' => $rangeStart->startOfYear()->format(self::DATE_FORMAT),
                'endDateEnd' => $rangeStart->endOfYear()->format(self::DATE_FORMAT),
                'intervalType' => str(self::YEAR->value)->singular(),
                'dateFormat' => self::YEAR->dateFormat()['db'],
                'dateFormatClear' => self::YEAR->dateFormatClear()['db'],
            ],
        ];
    }

    public function dateFormat(): array
    {
        $dbDateFormats = app(AggregationBuilderType::class)->getDateFormats();

        return match ($this) {
            self::HOUR =>   ['db' => $dbDateFormats[self::HOUR->value], 'php' => 'Y-m-d H:00'],
            self::DAY =>    ['db' => $dbDateFormats[self::DAY->value], 'php' => 'Y-m-d'],
            self::WEEK =>   ['db' => $dbDateFormats[self::WEEK->value], 'php' => 'o-W'],
            self::MONTH =>  ['db' => $dbDateFormats[self::MONTH->value], 'php' => 'Y-m'],
            self::YEAR =>   ['db' => $dbDateFormats[self::YEAR->value], 'php' => 'Y'],
            default => throw new \Exception('Unexpected match value'),
        };
    }

    public function dateFormatClear(): array
    {
        $dbDateFormatsClear = app(AggregationBuilderType::class)->getDateFormatsClear();

        return match ($this) {
            self::HOUR =>   ['db' => $dbDateFormatsClear[self::HOUR->value], 'php' => 'Y-m-d H:00'],
            self::DAY =>    ['db' => $dbDateFormatsClear[self::DAY->value], 'php' => 'Y-m-d'],
            self::WEEK =>   ['db' => $dbDateFormatsClear[self::WEEK->value], 'php' => 'Y-m-d '],
            self::MONTH =>  ['db' => $dbDateFormatsClear[self::MONTH->value], 'php' => 'Y-m-d '],
            self::YEAR =>   ['db' => $dbDateFormatsClear[self::YEAR->value], 'php' => 'Y-m-d '],
            default => throw new \Exception('Unexpected match value'),
        };
    }

    public function rangeStart(CarbonInterface $date): CarbonInterface
    {
        return match ($this) {
            self::HOUR => $date->startOfHour(),
            self::DAY => $date->startOfDay(),
            self::WEEK => $date->startOfWeek(),
            self::MONTH => $date->startOfMonth(),
            self::YEAR => $date->startOfYear(),
            default => throw new \Exception('Unexpected match value'),
        };
    }

    public function defaultRangeAmount(): int
    {
        return match ($this) {
            self::HOUR => 24,
            self::DAY => 7,
            self::WEEK => 4,
            self::MONTH => 6,
            self::YEAR => 3,
            default => throw new \Exception('Unexpected match value'),
        };
    }

    public function getPrevStartForComparison(CarbonInterface $rangeStart): CarbonInterface
    {
        return match ($this) {
            self::HOUR => $rangeStart->subDay(),
            self::DAY => $rangeStart->subWeek(),
            self::WEEK => $rangeStart->subMonth(),
            self::MONTH => $rangeStart->subYear(),
//            self::YEAR => $date->startOfYear(),
            default => throw new \Exception('Unexpected match value'),
        };
    }

    public function getDAUDates(CarbonInterface $endDateStart): array
    {
        return match ($this) {
            self::HOUR => $endDateStart->greaterThanOrEqualTo(now()->startOfHour()) ? [
                'start' => now()->subHour()->startOfHour()->format(self::DATE_FORMAT),
                'end' => now()->subHour()->endOfHour()->format(self::DATE_FORMAT),
            ] : [],
            self::DAY => $endDateStart->greaterThanOrEqualTo(now()->startOfDay()) ? [
                'start' => now()->subDay()->startOfDay()->format(self::DATE_FORMAT),
                'end' => now()->subDay()->endOfDay()->format(self::DATE_FORMAT),
            ] : [],
            self::WEEK => $endDateStart->greaterThanOrEqualTo(now()->startOfWeek()) ? [
                'start' => now()->subWeek()->startOfWeek()->format(self::DATE_FORMAT),
                'end' => now()->subWeek()->endOfWeek()->format(self::DATE_FORMAT),
            ] : [],
            self::MONTH => $endDateStart->greaterThanOrEqualTo(now()->startOfMonth()) ? [
                'start' => now()->subMonth()->startOfMonth()->format(self::DATE_FORMAT),
                'end' => now()->subMonth()->endOfMonth()->format(self::DATE_FORMAT),
            ] : [],
            self::YEAR => $endDateStart->greaterThanOrEqualTo(now()->startOfYear()) ? [
                'start' => now()->subYear()->startOfYear()->format(self::DATE_FORMAT),
                'end' => now()->subYear()->endOfYear()->format(self::DATE_FORMAT),
            ] : [],
        };
    }
}
