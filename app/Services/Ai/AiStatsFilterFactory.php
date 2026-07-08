<?php

namespace App\Services\Ai;

use App\DTOs\StatsFilterDto;
use App\Enums\StatsRangePreset;
use App\Enums\StatsRangeType;
use App\Helpers\AppHelper;
use App\Models\Project;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class AiStatsFilterFactory
{
    const MAX_BUCKETS = 120;

    public function make(?string $preset, ?string $startDate, ?string $endDate, int $projectId = 0): StatsFilterDto
    {
        // Date parsing below must happen in the team's timezone, which HasTeamScope
        // would only switch to lazily on the first scoped query.
        AppHelper::ensureTeamTimezone();

        $projectId = $this->resolveProjectId($projectId);
        $presetEnum = null;

        if ($preset) {
            $presetEnum = StatsRangePreset::from($preset);
            $range = $presetEnum->range($projectId);
            $rangeType = StatsRangeType::from($range['range_type']);
            $rangeAmount = (int) $range['range_amount'];
            $rangeStart = CarbonImmutable::parse($range['range_start']);
        } else {
            [$rangeType, $rangeAmount, $rangeStart] = $this->rangeFromDates($startDate, $endDate);
        }

        return new StatsFilterDto(
            projectId: $projectId,
            rangeAmount: min($rangeAmount, self::MAX_BUCKETS),
            rangeType: $rangeType,
            rangeStart: $this->normalizeRangeStart($rangeStart),
            comparison: '0',
            preset: $presetEnum,
        );
    }

    /**
     * @return array{0: StatsRangeType, 1: int, 2: CarbonImmutable}
     */
    private function rangeFromDates(?string $startDate, ?string $endDate): array
    {
        $start = CarbonImmutable::parse($startDate)->startOfDay();
        $end = CarbonImmutable::parse($endDate)->endOfDay();

        $spanDays = (int) $start->diffInDays($end) + 1;

        // Buckets run backwards from rangeStart, so rangeStart is the END of the range.
        if ($spanDays <= 45) {
            return [StatsRangeType::DAY, $spanDays, $end];
        }

        if ($spanDays <= 550) {
            return [StatsRangeType::WEEK, (int) $start->startOfWeek()->diffInWeeks($end->startOfWeek()) + 1, $end];
        }

        return [StatsRangeType::MONTH, (int) $start->startOfMonth()->diffInMonths($end->startOfMonth()) + 1, $end];
    }

    private function normalizeRangeStart(CarbonInterface $rangeStart): CarbonInterface
    {
        // Mirrors StatsFilterDto::fromRequest(): an end date of today must not
        // extend into the future, any other day covers its full last day.
        return $rangeStart->isToday() ? now() : $rangeStart->endOfDay();
    }

    private function resolveProjectId(int $projectId): int
    {
        if ($projectId > 0 && Project::whereKey($projectId)->exists()) {
            return $projectId;
        }

        return StatsFilterDto::TRACK_ALL;
    }
}
