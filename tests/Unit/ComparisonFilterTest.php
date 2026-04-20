<?php

use App\DTOs\StatsFilterDto;
use App\Enums\ComparisonType;
use App\Enums\StatsRangeType;
use App\Services\StatsService;
use Carbon\CarbonImmutable;
use Tests\TestCase;

uses(TestCase::class);

function makeFilter(StatsRangeType $type, int $amount, string $start, ComparisonType $comparison): StatsFilterDto
{
    return new StatsFilterDto(
        projectId: 1,
        rangeAmount: $amount,
        rangeType: $type,
        rangeStart: CarbonImmutable::parse($start),
        comparison: $comparison->value,
    );
}

function prevStart(StatsRangeType $type, int $amount, string $start, ComparisonType $comparison): string
{
    $filter = makeFilter($type, $amount, $start, $comparison);
    $prev = StatsService::createPrevFiltersForComparison($filter);

    return $prev->rangeStart->format('Y-m-d');
}

function prevRange(StatsRangeType $type, int $amount, string $start, ComparisonType $comparison): array
{
    $filter = makeFilter($type, $amount, $start, $comparison);
    $prev = StatsService::createPrevFiltersForComparison($filter);
    $conditions = StatsRangeType::conditions($prev->rangeStart, $prev->rangeAmount)[$prev->rangeType->value];

    return [
        'start' => substr($conditions['startDateStart'], 0, 10),
        'end' => substr($conditions['endDateEnd'], 0, 10),
    ];
}

// ===== "Last 6 months" with rangeStart Apr 20, 2026 =====
// Current covers Nov 2025 - Apr 2026

it('MONTH/6 PERIOD shifts prev start by 6 months', function () {
    expect(prevStart(StatsRangeType::MONTH, 6, '2026-04-20', ComparisonType::PERIOD))
        ->toBe('2025-10-20');
});

it('MONTH/6 PERIOD covers the preceding 6 months (May - Oct 2025)', function () {
    expect(prevRange(StatsRangeType::MONTH, 6, '2026-04-20', ComparisonType::PERIOD))
        ->toBe(['start' => '2025-05-01', 'end' => '2025-10-31']);
});

it('MONTH/6 CYCLE shifts prev start by 1 year', function () {
    expect(prevStart(StatsRangeType::MONTH, 6, '2026-04-20', ComparisonType::CYCLE))
        ->toBe('2025-04-20');
});

it('MONTH/6 CYCLE covers Nov 2024 - Apr 2025', function () {
    expect(prevRange(StatsRangeType::MONTH, 6, '2026-04-20', ComparisonType::CYCLE))
        ->toBe(['start' => '2024-11-01', 'end' => '2025-04-30']);
});

it('MONTH/6 CYCLE equals YEAR result for MONTH rangeType', function () {
    $cycle = prevRange(StatsRangeType::MONTH, 6, '2026-04-20', ComparisonType::CYCLE);
    $year = prevRange(StatsRangeType::MONTH, 6, '2026-04-20', ComparisonType::YEAR);

    expect($cycle)->toBe($year);
});

// ===== "Last 7 days" (default) — PERIOD and CYCLE coincide =====

it('DAY/7 PERIOD and CYCLE produce identical prev range', function () {
    $period = prevRange(StatsRangeType::DAY, 7, '2026-04-20', ComparisonType::PERIOD);
    $cycle = prevRange(StatsRangeType::DAY, 7, '2026-04-20', ComparisonType::CYCLE);

    expect($period)->toBe($cycle)
        ->and($period)->toBe(['start' => '2026-04-07', 'end' => '2026-04-13']);
});

// ===== "Last 14 days" — PERIOD and CYCLE diverge =====

it('DAY/14 PERIOD covers the preceding 14 days (no overlap)', function () {
    expect(prevRange(StatsRangeType::DAY, 14, '2026-04-20', ComparisonType::PERIOD))
        ->toBe(['start' => '2026-03-24', 'end' => '2026-04-06']);
});

it('DAY/14 CYCLE only shifts 7 days back, producing overlap with current', function () {
    // CYCLE still uses -1 week. Current covers Apr 7-20, prev covers Mar 31 - Apr 13 → overlap Apr 7-13
    expect(prevRange(StatsRangeType::DAY, 14, '2026-04-20', ComparisonType::CYCLE))
        ->toBe(['start' => '2026-03-31', 'end' => '2026-04-13']);
});

// ===== "Last 4 weeks" =====

it('WEEK/4 PERIOD shifts 4 weeks back', function () {
    expect(prevStart(StatsRangeType::WEEK, 4, '2026-04-20', ComparisonType::PERIOD))
        ->toBe('2026-03-23');
});

it('WEEK/4 CYCLE shifts 1 month back (approximation of 4-5 weeks)', function () {
    expect(prevStart(StatsRangeType::WEEK, 4, '2026-04-20', ComparisonType::CYCLE))
        ->toBe('2026-03-20');
});

// ===== "Last 24 hours" — PERIOD and CYCLE coincide =====

it('HOUR/24 PERIOD and CYCLE both shift 24 hours back', function () {
    $period = prevStart(StatsRangeType::HOUR, 24, '2026-04-20 12:00', ComparisonType::PERIOD);
    $cycle = prevStart(StatsRangeType::HOUR, 24, '2026-04-20 12:00', ComparisonType::CYCLE);

    expect($period)->toBe($cycle)->toBe('2026-04-19');
});

// ===== NO comparison returns filters unchanged =====

it('NO comparison keeps rangeStart', function () {
    expect(prevStart(StatsRangeType::DAY, 7, '2026-04-20', ComparisonType::NO))
        ->toBe('2026-04-20');
});

// ===== Report use case: single-period shifts =====

it('report MONTH/1 PERIOD shifts exactly one month back', function () {
    expect(prevStart(StatsRangeType::MONTH, 1, '2026-03-31', ComparisonType::PERIOD))
        ->toBe('2026-02-28');
});

it('report WEEK/1 PERIOD shifts exactly one week back', function () {
    expect(prevStart(StatsRangeType::WEEK, 1, '2026-04-19', ComparisonType::PERIOD))
        ->toBe('2026-04-12');
});

it('report DAY/1 PERIOD shifts exactly one day back', function () {
    expect(prevStart(StatsRangeType::DAY, 1, '2026-04-19', ComparisonType::PERIOD))
        ->toBe('2026-04-18');
});
