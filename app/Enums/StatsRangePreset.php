<?php

namespace App\Enums;

use App\Services\StatsService;
use LaracraftTech\LaravelUsefulAdditions\Traits\UsefulEnums;

enum StatsRangePreset: string
{
    use UsefulEnums;

    case TODAY = 'today';
    case YESTERDAY = 'yesterday';
    case LAST_7_DAYS = 'last_7_days';
    case LAST_30_DAYS = 'last_30_days';
    case LAST_12_WEEKS = 'last_12_weeks';
    case LAST_6_MONTHS = 'last_6_months';
    case THIS_MONTH = 'this_month';
    case LAST_MONTH = 'last_month';
    case THIS_YEAR = 'this_year';
    case LAST_YEAR = 'last_year';
    case ALL_TIME = 'all_time';
    case CUSTOM = 'custom';

    public static function toOptions(): array
    {
        return collect(self::cases())->map(function (StatsRangePreset $preset) {
            return [
                'id' => $preset->value,
                'name' => $preset->title(),
                'sortWeight' => $preset->sortWeight(),
            ];
        })->values()->toArray();
    }

    public function sortWeight(): int
    {
        return match ($this) {
            self::TODAY => 100,
            self::YESTERDAY => 200,
            self::LAST_7_DAYS => 700,
            self::LAST_30_DAYS => 3000,
            self::LAST_12_WEEKS => 8400,
            self::LAST_6_MONTHS => 18000,
            self::THIS_MONTH => 18100,
            self::LAST_MONTH => 18200,
            self::THIS_YEAR => 36500,
            self::LAST_YEAR => 36600,
            self::ALL_TIME => 99998,
            self::CUSTOM => 99999,
        };
    }

    public function title(): string
    {
        return match ($this) {
            self::TODAY => 'Today',
            self::YESTERDAY => 'Yesterday',
            self::LAST_7_DAYS => 'Last 7 Days',
            self::LAST_30_DAYS => 'Last 30 Days',
            self::LAST_12_WEEKS => 'Last 12 Weeks',
            self::LAST_6_MONTHS => 'Last 6 Months',
            self::THIS_MONTH => 'This Month',
            self::LAST_MONTH => 'Last Month',
            self::THIS_YEAR => 'This Year',
            self::LAST_YEAR => 'Last Year',
            self::ALL_TIME => 'All Time',
            self::CUSTOM => 'Custom',
        };
    }

    public function range(?int $projectId): array
    {
        return match ($this) {
            self::TODAY => [
                'rangeStart' => now()->startOfDay()->format(StatsRangeType::DATE_FORMAT),
                'rangeAmount' => now()->startOfDay()->diffInHours(now()) + 1,
                'rangeType' => StatsRangeType::HOUR->value,
            ],
            self::YESTERDAY => [
                'rangeStart' => now()->subDay()->startOfDay()->format(StatsRangeType::DATE_FORMAT),
                'rangeAmount' => 24,
                'rangeType' => StatsRangeType::HOUR->value,
            ],
            self::LAST_7_DAYS => [
                'rangeStart' => now()->format(StatsRangeType::DATE_FORMAT),
                'rangeAmount' => 7,
                'rangeType' => StatsRangeType::DAY->value,
            ],
            self::LAST_30_DAYS => [
                'rangeStart' => now()->format(StatsRangeType::DATE_FORMAT),
                'rangeAmount' => 30,
                'rangeType' => StatsRangeType::DAY->value,
            ],
            self::LAST_12_WEEKS => [
                'rangeStart' => now()->format(StatsRangeType::DATE_FORMAT),
                'rangeAmount' => 12,
                'rangeType' => StatsRangeType::WEEK->value,
            ],
            self::LAST_6_MONTHS => [
                'rangeStart' => now()->format(StatsRangeType::DATE_FORMAT),
                'rangeAmount' => now()->subMonths(6)->startOfMonth()->diffInMonths(now()),
                'rangeType' => StatsRangeType::MONTH->value,
            ],
            self::THIS_MONTH => [
                'rangeStart' => now()->format(StatsRangeType::DATE_FORMAT),
                'rangeAmount' => now()->startOfMonth()->diffInDays(now()) + 1,
                'rangeType' => StatsRangeType::DAY->value,
            ],
            self::LAST_MONTH => [
                'rangeStart' => now()->subMonth()->endOfMonth()->format(StatsRangeType::DATE_FORMAT),
                'rangeAmount' => now()->subMonth()->startOfMonth()->diffInDays(now()->subMonth()->endOfMonth()) + 1,
                'rangeType' => StatsRangeType::DAY->value,
            ],
            self::THIS_YEAR => [
                'rangeStart' => now()->format(StatsRangeType::DATE_FORMAT),
                'rangeAmount' => now()->startOfYear()->diffInMonths(now()) + 1,
                'rangeType' => StatsRangeType::MONTH->value,
            ],
            self::LAST_YEAR => [
                'rangeStart' => now()->subYear()->endOfYear()->format(StatsRangeType::DATE_FORMAT),
                'rangeAmount' => now()->subYear()->startOfYear()->diffInMonths(now()->subYear()->endOfYear()) + 1,
                'rangeType' => StatsRangeType::MONTH->value,
            ],
            self::ALL_TIME => $this->getAllTimeRange($projectId),
            self::CUSTOM => [
                'rangeStart' => null,
                'rangeAmount' => null,
                'rangeType' => null,
            ],
        };
    }

    private function getAllTimeRange(?int $projectId): array
    {
        $user = auth()->user();

        $statsStartingAt = app(StatsService::class)->getStatsStartingAt([
            'team_id' => $user->team->id,
            'id' => $projectId,
        ]) ?? now()->subYears(1)->startOfYear();

        $now = now();

        $allTimeType = StatsRangeType::MONTH;
        $allTimeRange = $statsStartingAt->diffInMonths($now) + 2;

        // If the range is more than 12 months, switch to year
        if ($allTimeRange > (12 * 4)) {
            $allTimeType = StatsRangeType::YEAR;
            $allTimeRange = $statsStartingAt->diffInYears($now) + 3;
        }

        return [
            'rangeStart' => $now->format('Y-m-d'),
            'rangeAmount' => $allTimeRange,
            'rangeType' => $allTimeType->value,
        ];
    }
}
