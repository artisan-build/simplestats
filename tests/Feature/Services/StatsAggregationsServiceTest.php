<?php

use App\DTOs\StatsFilterDto;
use App\Enums\StatsRangeType;
use App\Models\Project;
use App\Models\StatsAggregation;
use App\Models\StatsVisitor;
use App\Models\User;
use App\Observers\StatsObserver;
use App\Services\StatsAggregationsService;
use App\Services\StatsDetailsService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Database\Seeders\TestUserSeeder;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

beforeEach(function (): void {
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

afterEach(function (): void {
    StatsObserver::$enabled = true;
    Carbon::setTestNow();
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

it('builds cache from raw data when no cache exists', function (): void {
    insertVisitors($this->team->id, $this->pid, [
        '2026-03-28' => 10,
        '2026-03-29' => 20,
        '2026-03-30' => 15,
    ]);

    $filters = new StatsFilterDto(
        projectId: $this->pid, rangeAmount: 3, rangeType: StatsRangeType::DAY,
        rangeStart: CarbonImmutable::parse('2026-03-30'), comparison: '0',
    );

    $service = resolve(StatsAggregationsService::class);
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

it('returns cached data without re-querying when cache is complete', function (): void {
    insertVisitors($this->team->id, $this->pid, [
        '2026-03-28' => 10,
        '2026-03-29' => 20,
        '2026-03-30' => 15,
    ]);

    $filters = new StatsFilterDto(
        projectId: $this->pid, rangeAmount: 3, rangeType: StatsRangeType::DAY,
        rangeStart: CarbonImmutable::parse('2026-03-30'), comparison: '0',
    );

    $service = resolve(StatsAggregationsService::class);

    // Build cache
    $service->getAggregatedData($filters);

    // Add more visitors that should NOT appear (cache should be used)
    insertVisitors($this->team->id, $this->pid, ['2026-03-29' => 100]);

    $data = $service->getAggregatedData($filters);

    expect($data)->toHaveCount(3)
        ->and($data['2026-03-29']['pd_visitor'])->toBe(20);
});

it('fills missing dates at the start of the cache range', function (): void {
    insertVisitors($this->team->id, $this->pid, [
        '2026-03-24' => 5,
        '2026-03-25' => 10,
        '2026-03-26' => 15,
        '2026-03-27' => 20,
        '2026-03-28' => 8,
        '2026-03-29' => 12,
        '2026-03-30' => 18,
    ]);

    $service = resolve(StatsAggregationsService::class);

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

it('fills missing dates at the end of the cache range', function (): void {
    insertVisitors($this->team->id, $this->pid, [
        '2026-03-24' => 5,
        '2026-03-25' => 10,
        '2026-03-26' => 15,
        '2026-03-27' => 20,
        '2026-03-28' => 8,
    ]);

    $service = resolve(StatsAggregationsService::class);

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

it('detects and fills a single internal gap in cached data', function (): void {
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

    $service = resolve(StatsAggregationsService::class);

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

it('detects and fills multiple non-contiguous gaps in cached data', function (): void {
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

    $service = resolve(StatsAggregationsService::class);

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

it('detects and fills gaps in weekly aggregation data', function (): void {
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

    $service = resolve(StatsAggregationsService::class);

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

it('detects and fills gaps in hourly aggregation data', function (): void {
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

    $service = resolve(StatsAggregationsService::class);

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

it('detects and fills gaps in monthly aggregation data', function (): void {
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

    $service = resolve(StatsAggregationsService::class);

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

it('detects and fills gaps in yearly aggregation data', function (): void {
    insertVisitors($this->team->id, $this->pid, [
        '2024-06-15' => 10,
        '2025-06-15' => 15,
        '2026-06-15' => 20,
    ]);

    $filters = new StatsFilterDto(
        projectId: $this->pid, rangeAmount: 3, rangeType: StatsRangeType::YEAR,
        rangeStart: CarbonImmutable::parse('2026-06-15'), comparison: '0',
    );

    $service = resolve(StatsAggregationsService::class);

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

it('grouped DAU uses correct date range when report runs at timezone boundary', function (): void {
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

    $statsAll = current(resolve(StatsDetailsService::class)->getStats($filter));
    $globalDau = $statsAll['pd_dau'];

    $groupedStats = resolve(StatsAggregationsService::class)
        ->getAggregatedGroupedData($filter, 'track_referer', 'visitors', 10);
    $groupedDauTotal = $groupedStats->sum('dau');

    // Both should count only the 1 user from the target week (Mar 2-8), not the 3 from Feb 23-Mar 1
    expect($globalDau)->toBe(1)
        ->and($groupedDauTotal)->toBe(1)
        ->and($globalDau)->toBe($groupedDauTotal);
});

it('narrows the events group to the selected event when a custom event filter is active', function (): void {
    insertVisitors($this->team->id, $this->pid, ['2026-03-30' => 2]);

    $eventIds = [];
    foreach (['Event A', 'Event B'] as $name) {
        $eventIds[$name] = DB::table('stats_custom_event_names')->insertGetId([
            'team_id' => $this->team->id,
            'project_id' => $this->pid,
            'name' => $name,
            'created_at' => '2026-03-30 12:00:00',
            'updated_at' => '2026-03-30 12:00:00',
        ]);

        DB::table('stats_custom_events')->insert([
            'id' => Str::uuid()->toString(),
            'team_id' => $this->team->id,
            'project_id' => $this->pid,
            'stats_custom_event_name_id' => $eventIds[$name],
            'time' => '2026-03-30 12:00:00',
            'time_utc' => '2026-03-30 12:00:00',
        ]);
    }

    $filter = new StatsFilterDto(
        projectId: $this->pid, rangeAmount: 1, rangeType: StatsRangeType::DAY,
        rangeStart: CarbonImmutable::parse('2026-03-30'), comparison: '0',
        customEventName: $eventIds['Event A'],
    );

    $grouped = resolve(StatsAggregationsService::class)
        ->getAggregatedGroupedData($filter, 'custom_event_name', 'name', 10);

    // like the standard groups: filtering by an event leaves only that event in its own group
    expect($grouped->pluck('name')->all())->toBe(['Event A']);
});

it('grouped custom events do not leak across projects in the same team', function (): void {
    $otherProject = Project::factory()->create(['team_id' => $this->team->id]);

    insertVisitors($this->team->id, $this->pid, ['2026-03-30' => 3]);

    $ownEventNameId = DB::table('stats_custom_event_names')->insertGetId([
        'team_id' => $this->team->id,
        'project_id' => $this->pid,
        'name' => 'Event A (own project)',
        'created_at' => '2026-03-30 12:00:00',
        'updated_at' => '2026-03-30 12:00:00',
    ]);

    DB::table('stats_custom_event_names')->insert([
        'team_id' => $this->team->id,
        'project_id' => $otherProject->id,
        'name' => 'Event B (other project)',
        'created_at' => '2026-03-30 12:00:00',
        'updated_at' => '2026-03-30 12:00:00',
    ]);

    DB::table('stats_custom_events')->insert([
        'id' => Str::uuid()->toString(),
        'team_id' => $this->team->id,
        'project_id' => $this->pid,
        'stats_custom_event_name_id' => $ownEventNameId,
        'time' => '2026-03-30 12:00:00',
        'time_utc' => '2026-03-30 12:00:00',
    ]);

    $filter = new StatsFilterDto(
        projectId: $this->pid, rangeAmount: 1, rangeType: StatsRangeType::DAY,
        rangeStart: CarbonImmutable::parse('2026-03-30'), comparison: '0',
    );

    $grouped = resolve(StatsAggregationsService::class)
        ->getAggregatedGroupedData($filter, 'custom_event_name', 'name', 10);

    $names = $grouped->pluck('name')->all();

    expect($names)->toContain('Event A (own project)')
        ->and($names)->not->toContain('Event B (other project)');
});

it('carries the lifetime baseline forward from the cached predecessor bucket instead of rescanning history', function (): void {
    insertVisitors($this->team->id, $this->pid, [
        '2026-03-27' => 5,
        '2026-03-28' => 10,
        '2026-03-29' => 20,
    ]);

    $service = resolve(StatsAggregationsService::class);

    // Cache the three days up to and including the predecessor (2026-03-29).
    $predecessorFilters = new StatsFilterDto(
        projectId: $this->pid, rangeAmount: 3, rangeType: StatsRangeType::DAY,
        rangeStart: CarbonImmutable::parse('2026-03-29'), comparison: '0',
    );
    $cached = $service->getAggregatedData($predecessorFilters);

    // Predecessor row holds the accumulated lifetime total (5 + 10 + 20).
    expect($cached['2026-03-29']['lt_visitor'])->toBe(35);

    // Drop the raw history: a fresh scan would now find nothing before 03-30.
    // The carry-forward must instead seed the baseline from the cached 03-29 row.
    DB::table('stats_visitors')->delete();
    insertVisitors($this->team->id, $this->pid, ['2026-03-30' => 1]);

    $rolloverFilters = new StatsFilterDto(
        projectId: $this->pid, rangeAmount: 1, rangeType: StatsRangeType::DAY,
        rangeStart: CarbonImmutable::parse('2026-03-30'), comparison: '0',
    );
    $data = $service->getAggregatedData($rolloverFilters);

    // pd is the single fresh visitor, lt is the carried-forward 35 plus that 1.
    // A history rescan would yield lt_visitor = 1, which proves the carry-forward.
    expect($data['2026-03-30']['pd_visitor'])->toBe(1)
        ->and($data['2026-03-30']['lt_visitor'])->toBe(36);
});

it('falls back to a full history scan when the predecessor bucket is not cached', function (): void {
    insertVisitors($this->team->id, $this->pid, [
        '2026-03-27' => 5,
        '2026-03-28' => 10,
        '2026-03-30' => 3,
    ]);

    $service = resolve(StatsAggregationsService::class);

    // Query the 03-30 bucket cold, without ever caching its predecessor (03-29).
    $filters = new StatsFilterDto(
        projectId: $this->pid, rangeAmount: 1, rangeType: StatsRangeType::DAY,
        rangeStart: CarbonImmutable::parse('2026-03-30'), comparison: '0',
    );
    $data = $service->getAggregatedData($filters);

    // No cached predecessor -> the lt_* CTEs scan the full history (5 + 10) and
    // add the bucket's own 3.
    expect($data['2026-03-30']['pd_visitor'])->toBe(3)
        ->and($data['2026-03-30']['lt_visitor'])->toBe(18);
});

it('uses a zero baseline for the very first bucket of history (no predecessor)', function (): void {
    insertVisitors($this->team->id, $this->pid, ['2026-03-30' => 7]);

    $service = resolve(StatsAggregationsService::class);

    $filters = new StatsFilterDto(
        projectId: $this->pid, rangeAmount: 1, rangeType: StatsRangeType::DAY,
        rangeStart: CarbonImmutable::parse('2026-03-30'), comparison: '0',
    );
    $data = $service->getAggregatedData($filters);

    // Nothing before the bucket and no cached predecessor: lt equals pd.
    expect($data['2026-03-30']['pd_visitor'])->toBe(7)
        ->and($data['2026-03-30']['lt_visitor'])->toBe(7);
});

it('invalidates the carried-forward baseline when a late event lands in a past bucket', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-06-15 12:00:00'));

    insertVisitors($this->team->id, $this->pid, [
        '2026-03-28' => 10,
        '2026-03-29' => 20,
        '2026-03-30' => 4,
    ]);

    $service = resolve(StatsAggregationsService::class);

    // Cache 03-28/03-29, then roll over into 03-30 via carry-forward.
    $service->getAggregatedData(new StatsFilterDto(
        projectId: $this->pid, rangeAmount: 2, rangeType: StatsRangeType::DAY,
        rangeStart: CarbonImmutable::parse('2026-03-29'), comparison: '0',
    ));

    $rolloverFilters = new StatsFilterDto(
        projectId: $this->pid, rangeAmount: 1, rangeType: StatsRangeType::DAY,
        rangeStart: CarbonImmutable::parse('2026-03-30'), comparison: '0',
    );
    $rolled = $service->getAggregatedData($rolloverFilters);
    expect($rolled['2026-03-30']['lt_visitor'])->toBe(34); // 30 carried + 4

    // A late visitor lands in the already-closed 03-28 bucket. The observer's
    // forward-delete must drop 03-28 and everything after it (incl. the cached
    // 03-29 predecessor and 03-30), so the carry-forward cannot reuse a stale row.
    StatsObserver::$enabled = true;
    StatsVisitor::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->pid,
        'time' => '2026-03-28 09:00:00',
        'time_utc' => '2026-03-28 09:00:00',
    ]);

    $data = $service->getAggregatedData($rolloverFilters);

    // Recomputed from scratch: history before 03-30 is now 11 + 20 = 31, plus 4.
    expect($data['2026-03-30']['lt_visitor'])->toBe(35);
});

it('carries the baseline forward on the team aggregation (project_id null) chain', function (): void {
    insertVisitors($this->team->id, $this->pid, [
        '2026-03-28' => 10,
        '2026-03-29' => 20,
    ]);

    $service = resolve(StatsAggregationsService::class);

    // projectId 0 -> team-wide aggregation, stored with project_id = null.
    $predecessorFilters = new StatsFilterDto(
        projectId: 0, rangeAmount: 2, rangeType: StatsRangeType::DAY,
        rangeStart: CarbonImmutable::parse('2026-03-29'), comparison: '0',
    );
    $cached = $service->getAggregatedData($predecessorFilters);
    expect($cached['2026-03-29']['lt_visitor'])->toBe(30);

    expect(StatsAggregation::where('filter_hash', $predecessorFilters->makeHash())
        ->whereNull('project_id')->count())->toBe(2);

    DB::table('stats_visitors')->delete();
    insertVisitors($this->team->id, $this->pid, ['2026-03-30' => 1]);

    $data = $service->getAggregatedData(new StatsFilterDto(
        projectId: 0, rangeAmount: 1, rangeType: StatsRangeType::DAY,
        rangeStart: CarbonImmutable::parse('2026-03-30'), comparison: '0',
    ));

    expect($data['2026-03-30']['pd_visitor'])->toBe(1)
        ->and($data['2026-03-30']['lt_visitor'])->toBe(31);
});

it('carries the baseline forward on a filtered (dimension) hash', function (): void {
    $sourceId = DB::table('stats_track_sources')->insertGetId([
        'team_id' => $this->team->id,
        'name' => 'newsletter',
        'created_at' => '2026-03-28 12:00:00',
        'updated_at' => '2026-03-28 12:00:00',
    ]);

    $insert = function (string $date, int $count, ?int $srcId): void {
        for ($i = 0; $i < $count; $i++) {
            DB::table('stats_visitors')->insert([
                'team_id' => $this->team->id,
                'project_id' => $this->pid,
                'visitor_hash' => Str::uuid()->toString(),
                'stats_track_source_id' => $srcId,
                'time' => $date.' 12:00:00',
                'time_utc' => $date.' 12:00:00',
            ]);
        }
    };

    // History: visitors matching the filter (source = newsletter) plus noise
    // without it, so the filtered hash actually discriminates.
    $insert('2026-03-28', 4, $sourceId);
    $insert('2026-03-28', 6, null);
    $insert('2026-03-29', 8, $sourceId);
    $insert('2026-03-29', 2, null);

    $service = resolve(StatsAggregationsService::class);

    // Cache the FILTERED range up to the predecessor (03-29). The filtered
    // lifetime total counts only newsletter visitors: 4 + 8 = 12.
    $predecessorFilters = new StatsFilterDto(
        projectId: $this->pid, rangeAmount: 2, rangeType: StatsRangeType::DAY,
        rangeStart: CarbonImmutable::parse('2026-03-29'), comparison: '0',
        trackSource: $sourceId,
    );
    $cached = $service->getAggregatedData($predecessorFilters);
    expect($cached['2026-03-29']['pd_visitor'])->toBe(8)
        ->and($cached['2026-03-29']['lt_visitor'])->toBe(12);

    // Drop the raw history: a filtered rescan would now find nothing before 03-30.
    DB::table('stats_visitors')->delete();

    // Rollover-day visitors: 1 matching the filter + noise that must NOT count.
    $insert('2026-03-30', 1, $sourceId);
    $insert('2026-03-30', 5, null);

    $data = $service->getAggregatedData(new StatsFilterDto(
        projectId: $this->pid, rangeAmount: 1, rangeType: StatsRangeType::DAY,
        rangeStart: CarbonImmutable::parse('2026-03-30'), comparison: '0',
        trackSource: $sourceId,
    ));

    // pd counts only the single newsletter visitor (noise excluded by the filter),
    // lt is the carried-forward 12 plus that 1. A history rescan would yield
    // lt_visitor = 1, which proves the carry-forward works on the filtered hash.
    expect($data['2026-03-30']['pd_visitor'])->toBe(1)
        ->and($data['2026-03-30']['lt_visitor'])->toBe(13);
});

it('reconstructs a multi-bucket gap in one shot, seeding from the last cached bucket', function (): void {
    // Last time the dashboard was opened: a single cached day holding the whole
    // history up to that point (lt_visitor = 15).
    insertVisitors($this->team->id, $this->pid, ['2026-03-07' => 15]);

    $service = resolve(StatsAggregationsService::class);

    $service->getAggregatedData(new StatsFilterDto(
        projectId: $this->pid, rangeAmount: 1, rangeType: StatsRangeType::DAY,
        rangeStart: CarbonImmutable::parse('2026-03-07'), comparison: '0',
    ));

    // Drop the raw history so a scan over "before the gap" would find nothing;
    // only the cached 03-07 bucket (lt = 15) survives as the seed.
    DB::table('stats_visitors')->delete();

    // A full week passes uncached, each day collecting raw visitors.
    insertVisitors($this->team->id, $this->pid, [
        '2026-03-08' => 1, '2026-03-09' => 2, '2026-03-10' => 3, '2026-03-11' => 4,
        '2026-03-12' => 5, '2026-03-13' => 6, '2026-03-14' => 7,
    ]);

    // Coming back a week later and opening the 7-day window (all uncached).
    $data = $service->getAggregatedData(new StatsFilterDto(
        projectId: $this->pid, rangeAmount: 7, rangeType: StatsRangeType::DAY,
        rangeStart: CarbonImmutable::parse('2026-03-14'), comparison: '0',
    ));

    // The whole week is built in one call: pd from the raw 7-day window, lt rolled
    // forward from the seeded 15. A history rescan would start lt at 0 (history
    // deleted), so lt_visitor(03-08) = 16 and lt_visitor(03-14) = 43 prove the
    // carry-forward seeded the gap once and propagated across all 7 buckets.
    expect($data)->toHaveCount(7)
        ->and($data->sum('pd_visitor'))->toBe(28)
        ->and($data['2026-03-08']['pd_visitor'])->toBe(1)
        ->and($data['2026-03-08']['lt_visitor'])->toBe(16)
        ->and($data['2026-03-14']['pd_visitor'])->toBe(7)
        ->and($data['2026-03-14']['lt_visitor'])->toBe(43);
});
