<?php

use App\DTOs\StatsFilterDto;
use App\Enums\StatsRangeType;
use App\Models\Project;
use App\Models\StatsAggregation;
use App\Models\User;
use App\Services\StatsAggregationsService;
use App\Services\StatsDetailsService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Database\Seeders\TestUserSeeder;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->seed(TestUserSeeder::class);
    $this->user = User::where('email', config('app.test.user')['email'])->firstOrFail();
    $this->project = Project::first();

    $this->team = $this->project->team;
    $this->pid = $this->project->id;

    DB::table('stats_custom_events')->delete();
    DB::table('stats_payments')->delete();
    DB::table('stats_logins')->delete();
    DB::table('stats_visitors')->delete();
    DB::table('stats_users')->delete();

    auth()->setUser($this->user);
    Context::add('team_id', $this->team->id);
    config(['app.stats_aggregation_enabled' => true]);
});

function insertVisitors(int $teamId, int $projectId, array $visitorCounts): void
{
    foreach ($visitorCounts as $date => $count) {
        $timestamp = str_contains($date, ':') ? $date : $date.' 12:00:00';
        for ($i = 0; $i < $count; $i++) {
            DB::table('stats_visitors')->insert([
                'team_id' => $teamId,
                'project_id' => $projectId,
                'visitor_hash' => Str::uuid()->toString(),
                'time' => $timestamp,
                'time_utc' => $timestamp,
            ]);
        }
    }
}

it('builds cache from raw data when no cache exists', function () {
    insertVisitors($this->team->id, $this->pid, [
        '2026-03-28' => 10,
        '2026-03-29' => 20,
        '2026-03-30' => 15,
    ]);

    $filters = new StatsFilterDto(
        projectId: $this->pid, rangeAmount: 3, rangeType: StatsRangeType::DAY,
        rangeStart: CarbonImmutable::parse('2026-03-30'), comparison: '0',
    );

    $service = app(StatsAggregationsService::class);
    $data = $service->getAggregatedData($filters);

    expect($data)->toHaveCount(3)
        ->and($data->sum('pd_visitor'))->toBe(45)
        ->and($data['2026-03-28']['pd_visitor'])->toBe(10)
        ->and($data['2026-03-29']['pd_visitor'])->toBe(20)
        ->and($data['2026-03-30']['pd_visitor'])->toBe(15);

    // Verify data was cached
    $cachedCount = StatsAggregation::where('filter_hash', $filters->makeHash())
        ->where('type', 'days')->count();
    expect($cachedCount)->toBe(3);
});

it('returns cached data without re-querying when cache is complete', function () {
    insertVisitors($this->team->id, $this->pid, [
        '2026-03-28' => 10,
        '2026-03-29' => 20,
        '2026-03-30' => 15,
    ]);

    $filters = new StatsFilterDto(
        projectId: $this->pid, rangeAmount: 3, rangeType: StatsRangeType::DAY,
        rangeStart: CarbonImmutable::parse('2026-03-30'), comparison: '0',
    );

    $service = app(StatsAggregationsService::class);

    // Build cache
    $service->getAggregatedData($filters);

    // Add more visitors that should NOT appear (cache should be used)
    insertVisitors($this->team->id, $this->pid, ['2026-03-29' => 100]);

    $data = $service->getAggregatedData($filters);

    expect($data)->toHaveCount(3)
        ->and($data['2026-03-29']['pd_visitor'])->toBe(20);
});

it('fills missing dates at the start of the cache range', function () {
    insertVisitors($this->team->id, $this->pid, [
        '2026-03-24' => 5,
        '2026-03-25' => 10,
        '2026-03-26' => 15,
        '2026-03-27' => 20,
        '2026-03-28' => 8,
        '2026-03-29' => 12,
        '2026-03-30' => 18,
    ]);

    $service = app(StatsAggregationsService::class);

    // Cache only the last 3 days
    $filtersSmall = new StatsFilterDto(
        projectId: $this->pid, rangeAmount: 3, rangeType: StatsRangeType::DAY,
        rangeStart: CarbonImmutable::parse('2026-03-30'), comparison: '0',
    );
    $service->getAggregatedData($filtersSmall);

    // Now request 7 days, the first 4 should be fetched from raw
    $filtersFull = new StatsFilterDto(
        projectId: $this->pid, rangeAmount: 7, rangeType: StatsRangeType::DAY,
        rangeStart: CarbonImmutable::parse('2026-03-30'), comparison: '0',
    );
    $data = $service->getAggregatedData($filtersFull);

    expect($data)->toHaveCount(7)
        ->and($data->sum('pd_visitor'))->toBe(88)
        ->and($data['2026-03-24']['pd_visitor'])->toBe(5)
        ->and($data['2026-03-25']['pd_visitor'])->toBe(10);
});

it('fills missing dates at the end of the cache range', function () {
    insertVisitors($this->team->id, $this->pid, [
        '2026-03-24' => 5,
        '2026-03-25' => 10,
        '2026-03-26' => 15,
        '2026-03-27' => 20,
        '2026-03-28' => 8,
    ]);

    $service = app(StatsAggregationsService::class);

    // Cache days 24-26
    $filtersSmall = new StatsFilterDto(
        projectId: $this->pid, rangeAmount: 3, rangeType: StatsRangeType::DAY,
        rangeStart: CarbonImmutable::parse('2026-03-26'), comparison: '0',
    );
    $service->getAggregatedData($filtersSmall);

    // Now request 24-28, days 27-28 should be fetched
    $filtersFull = new StatsFilterDto(
        projectId: $this->pid, rangeAmount: 5, rangeType: StatsRangeType::DAY,
        rangeStart: CarbonImmutable::parse('2026-03-28'), comparison: '0',
    );
    $data = $service->getAggregatedData($filtersFull);

    expect($data)->toHaveCount(5)
        ->and($data->sum('pd_visitor'))->toBe(58)
        ->and($data['2026-03-27']['pd_visitor'])->toBe(20)
        ->and($data['2026-03-28']['pd_visitor'])->toBe(8);
});

it('detects and fills a single internal gap in cached data', function () {
    insertVisitors($this->team->id, $this->pid, [
        '2026-03-24' => 10,
        '2026-03-25' => 15,
        '2026-03-26' => 20,
        '2026-03-27' => 12,
        '2026-03-28' => 8,
        '2026-03-29' => 25,
        '2026-03-30' => 18,
    ]);

    $filters = new StatsFilterDto(
        projectId: $this->pid, rangeAmount: 7, rangeType: StatsRangeType::DAY,
        rangeStart: CarbonImmutable::parse('2026-03-30'), comparison: '0',
    );

    $service = app(StatsAggregationsService::class);

    // Build full cache
    $service->getAggregatedData($filters);

    // Delete March 26-27 to create internal gap
    StatsAggregation::where('filter_hash', $filters->makeHash())
        ->where('type', 'days')
        ->whereBetween('time', ['2026-03-26', '2026-03-27'])
        ->delete();

    $data = $service->getAggregatedData($filters);

    expect($data)->toHaveCount(7)
        ->and($data->sum('pd_visitor'))->toBe(108)
        ->and($data['2026-03-26']['pd_visitor'])->toBe(20)
        ->and($data['2026-03-27']['pd_visitor'])->toBe(12);

    expect(StatsAggregation::where('filter_hash', $filters->makeHash())
        ->where('type', 'days')->count())->toBe(7);
});

it('detects and fills multiple non-contiguous gaps in cached data', function () {
    insertVisitors($this->team->id, $this->pid, [
        '2026-03-24' => 10,
        '2026-03-25' => 15,
        '2026-03-26' => 20,
        '2026-03-27' => 12,
        '2026-03-28' => 8,
        '2026-03-29' => 25,
        '2026-03-30' => 18,
    ]);

    $filters = new StatsFilterDto(
        projectId: $this->pid, rangeAmount: 7, rangeType: StatsRangeType::DAY,
        rangeStart: CarbonImmutable::parse('2026-03-30'), comparison: '0',
    );

    $service = app(StatsAggregationsService::class);

    // Build full cache
    $service->getAggregatedData($filters);

    // Delete March 25 AND March 28-29 to create two separate gaps
    StatsAggregation::where('filter_hash', $filters->makeHash())
        ->where('type', 'days')
        ->where('time', '2026-03-25')
        ->delete();
    StatsAggregation::where('filter_hash', $filters->makeHash())
        ->where('type', 'days')
        ->whereBetween('time', ['2026-03-28', '2026-03-29'])
        ->delete();

    $data = $service->getAggregatedData($filters);

    expect($data)->toHaveCount(7)
        ->and($data->sum('pd_visitor'))->toBe(108)
        ->and($data['2026-03-25']['pd_visitor'])->toBe(15)
        ->and($data['2026-03-28']['pd_visitor'])->toBe(8)
        ->and($data['2026-03-29']['pd_visitor'])->toBe(25);
});

it('detects and fills gaps in weekly aggregation data', function () {
    // Insert visitors across 4 weeks
    insertVisitors($this->team->id, $this->pid, [
        '2026-03-02' => 10, // Week of March 2
        '2026-03-09' => 15, // Week of March 9
        '2026-03-16' => 20, // Week of March 16
        '2026-03-23' => 25, // Week of March 23
    ]);

    $filters = new StatsFilterDto(
        projectId: $this->pid, rangeAmount: 4, rangeType: StatsRangeType::WEEK,
        rangeStart: CarbonImmutable::parse('2026-03-23'), comparison: '0',
    );

    $service = app(StatsAggregationsService::class);

    // Build full cache
    $data = $service->getAggregatedData($filters);
    expect($data)->toHaveCount(4)
        ->and($data->sum('pd_visitor'))->toBe(70);

    // Delete week of March 9 and March 16 to create internal gap
    StatsAggregation::where('filter_hash', $filters->makeHash())
        ->where('type', 'weeks')
        ->whereBetween('time', ['2026-03-09', '2026-03-16'])
        ->delete();

    $data = $service->getAggregatedData($filters);

    expect($data)->toHaveCount(4)
        ->and($data->sum('pd_visitor'))->toBe(70);
});

it('detects and fills gaps in hourly aggregation data', function () {
    insertVisitors($this->team->id, $this->pid, [
        '2026-03-30 10:00:00' => 5,
        '2026-03-30 11:00:00' => 8,
        '2026-03-30 12:00:00' => 12,
        '2026-03-30 13:00:00' => 6,
    ]);

    $filters = new StatsFilterDto(
        projectId: $this->pid, rangeAmount: 4, rangeType: StatsRangeType::HOUR,
        rangeStart: CarbonImmutable::parse('2026-03-30 13:30:00'), comparison: '0',
    );

    $service = app(StatsAggregationsService::class);

    $data = $service->getAggregatedData($filters);
    expect($data)->toHaveCount(4)
        ->and($data->sum('pd_visitor'))->toBe(31);

    // Delete hour 11 and 12 to create internal gap
    StatsAggregation::where('filter_hash', $filters->makeHash())
        ->where('type', 'hours')
        ->whereBetween('time', ['2026-03-30 11:00:00', '2026-03-30 12:00:00'])
        ->delete();

    $data = $service->getAggregatedData($filters);

    expect($data)->toHaveCount(4)
        ->and($data->sum('pd_visitor'))->toBe(31);
});

it('detects and fills gaps in monthly aggregation data', function () {
    insertVisitors($this->team->id, $this->pid, [
        '2026-01-15' => 10,
        '2026-02-15' => 15,
        '2026-03-15' => 20,
        '2026-04-15' => 25,
    ]);

    $filters = new StatsFilterDto(
        projectId: $this->pid, rangeAmount: 4, rangeType: StatsRangeType::MONTH,
        rangeStart: CarbonImmutable::parse('2026-04-15'), comparison: '0',
    );

    $service = app(StatsAggregationsService::class);

    $data = $service->getAggregatedData($filters);
    expect($data)->toHaveCount(4)
        ->and($data->sum('pd_visitor'))->toBe(70);

    // Delete February and March to create internal gap
    StatsAggregation::where('filter_hash', $filters->makeHash())
        ->where('type', 'months')
        ->whereBetween('time', ['2026-02-01', '2026-03-31'])
        ->delete();

    $data = $service->getAggregatedData($filters);

    expect($data)->toHaveCount(4)
        ->and($data->sum('pd_visitor'))->toBe(70);
});

it('detects and fills gaps in yearly aggregation data', function () {
    insertVisitors($this->team->id, $this->pid, [
        '2024-06-15' => 10,
        '2025-06-15' => 15,
        '2026-06-15' => 20,
    ]);

    $filters = new StatsFilterDto(
        projectId: $this->pid, rangeAmount: 3, rangeType: StatsRangeType::YEAR,
        rangeStart: CarbonImmutable::parse('2026-06-15'), comparison: '0',
    );

    $service = app(StatsAggregationsService::class);

    $data = $service->getAggregatedData($filters);
    expect($data)->toHaveCount(3)
        ->and($data->sum('pd_visitor'))->toBe(45);

    // Delete 2025 to create internal gap
    StatsAggregation::where('filter_hash', $filters->makeHash())
        ->where('type', 'years')
        ->whereBetween('time', ['2025-01-01', '2025-12-31'])
        ->delete();

    $data = $service->getAggregatedData($filters);

    expect($data)->toHaveCount(3)
        ->and($data->sum('pd_visitor'))->toBe(45);
});

it('grouped DAU uses correct date range when report runs at timezone boundary', function () {
    $team = $this->project->team;
    $team->update(['timezone' => 'Europe/Berlin']);

    $targetWeekStart = CarbonImmutable::parse('2026-03-02 00:00:00');
    $previousWeekStart = CarbonImmutable::parse('2026-02-23 00:00:00');
    $pid = $this->project->id;

    for ($i = 0; $i < 3; $i++) {
        DB::table('stats_users')->insert([
            'id' => 'tz-prev-'.$i, 'team_id' => $team->id, 'project_id' => $pid,
            'time' => '2026-01-01 00:00:00', 'time_utc' => '2026-01-01 00:00:00',
        ]);
        DB::table('stats_logins')->insert([
            'stats_user_id' => 'tz-prev-'.$i, 'team_id' => $team->id, 'project_id' => $pid,
            'time' => $previousWeekStart->copy()->addDays($i)->format('Y-m-d H:i:s'),
            'time_utc' => $previousWeekStart->copy()->addDays($i)->format('Y-m-d H:i:s'),
            'counter' => 2,
        ]);
    }

    DB::table('stats_users')->insert([
        'id' => 'tz-target', 'team_id' => $team->id, 'project_id' => $pid,
        'time' => '2026-01-01 00:00:00', 'time_utc' => '2026-01-01 00:00:00',
    ]);
    DB::table('stats_logins')->insert([
        'stats_user_id' => 'tz-target', 'team_id' => $team->id, 'project_id' => $pid,
        'time' => $targetWeekStart->copy()->addDay()->format('Y-m-d H:i:s'),
        'time_utc' => $targetWeekStart->copy()->addDay()->format('Y-m-d H:i:s'),
        'counter' => 2,
    ]);

    // Simulate the exact production context: SendProjectReports sets auth user
    // Set auth user and team context but keep timezone as UTC
    // to test that DAU grouping is not affected by timezone.
    auth()->setUser($this->user);
    Context::add('team_id', $team->id);
    config(['app.timezone' => 'UTC']);
    date_default_timezone_set('UTC');

    // Monday 00:00 Berlin = Sunday 23:00 UTC
    Carbon::setTestNow(Carbon::parse('2026-03-08 23:00:00'));

    $filter = new StatsFilterDto(
        projectId: $pid, rangeAmount: 1, rangeType: StatsRangeType::WEEK,
        rangeStart: $targetWeekStart, comparison: '0'
    );

    $statsAll = current(app(StatsDetailsService::class)->getStats($filter));
    $globalDau = $statsAll['pd_dau'];

    $groupedStats = app(StatsAggregationsService::class)
        ->getAggregatedGroupedData($filter, 'track_referer', 'visitors', 10);
    $groupedDauTotal = $groupedStats->sum('dau');

    // Both should count only the 1 user from the target week (Mar 2-8), not the 3 from Feb 23-Mar 1
    expect($globalDau)->toBe(1)
        ->and($groupedDauTotal)->toBe(1)
        ->and($globalDau)->toBe($groupedDauTotal);
});
