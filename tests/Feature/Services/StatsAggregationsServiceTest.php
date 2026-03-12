<?php

use App\DTOs\StatsFilterDto;
use App\Enums\StatsRangeType;
use App\Models\Project;
use App\Models\User;
use App\Services\StatsAggregationsService;
use App\Services\StatsDetailsService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed();
    $this->user = User::where('email', config('app.test.user')['email'])->firstOrFail();
    $this->project = Project::first();
});

it('grouped DAU uses correct date range when report runs at timezone boundary', function () {
    $team = $this->project->team;
    $team->update(['timezone' => 'Europe/Berlin']);

    DB::table('stats_custom_events')->delete();
    DB::table('stats_payments')->delete();
    DB::table('stats_logins')->delete();
    DB::table('stats_visitors')->delete();
    DB::table('stats_users')->delete();

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
