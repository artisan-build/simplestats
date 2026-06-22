<?php

use App\DTOs\StatsFilterDto;
use App\Enums\PaymentInterval;
use App\Enums\StatsRangeType;
use App\Models\Project;
use App\Models\User;
use App\Services\MrrAggregationService;
use Carbon\CarbonImmutable;
use Database\Seeders\TestUserSeeder;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    CarbonImmutable::setTestNow('2026-06-21 12:00:00');

    $this->seed(TestUserSeeder::class);
    $this->user = User::where('email', config('app.test.user')['email'])->firstOrFail();
    $this->project = Project::first();

    $this->team = $this->project->team;
    $this->pid = $this->project->id;

    DB::table('stats_payments')->delete();
    DB::table('stats_users')->delete();

    auth()->setUser($this->user);
    Context::add('team_id', $this->team->id);

    $this->service = app(MrrAggregationService::class);
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

function insertStatsUser(string $id, int $teamId, int $projectId, ?int $sourceId = null): void
{
    DB::table('stats_users')->insert([
        'id' => $id,
        'team_id' => $teamId,
        'project_id' => $projectId,
        'stats_track_source_id' => $sourceId,
        'time' => '2026-01-01 00:00:00',
        'time_utc' => '2026-01-01 00:00:00',
    ]);
}

function insertPayment(string $id, string $userId, int $teamId, int $projectId, float $net, ?PaymentInterval $interval, string $time): void
{
    DB::table('stats_payments')->insert([
        'id' => $id,
        'stats_user_id' => $userId,
        'team_id' => $teamId,
        'project_id' => $projectId,
        'gross' => $net,
        'net' => $net,
        'currency' => 'EUR',
        'recurring_interval' => $interval?->value,
        'counter' => 1,
        'time' => $time,
        'time_utc' => $time,
    ]);
}

function dayFilter(int $projectId, string $rangeStart, int $trackSource = 0): StatsFilterDto
{
    return new StatsFilterDto(
        projectId: $projectId, rangeAmount: 1, rangeType: StatsRangeType::DAY,
        rangeStart: CarbonImmutable::parse($rangeStart), comparison: '0',
        trackSource: $trackSource,
    );
}

it('counts a monthly subscription in full and a yearly one as net/12', function () {
    insertStatsUser('u-month', $this->team->id, $this->pid);
    insertStatsUser('u-year', $this->team->id, $this->pid);
    insertPayment('p-month', 'u-month', $this->team->id, $this->pid, 50, PaymentInterval::MONTH, '2026-06-15 10:00:00');
    insertPayment('p-year', 'u-year', $this->team->id, $this->pid, 1200, PaymentInterval::YEAR, '2026-06-15 10:00:00');

    $snapshot = $this->service->getMrrSnapshot(dayFilter($this->pid, '2026-06-21'));

    expect($snapshot['mrr'])->toBe(150.0)
        ->and($snapshot['arr'])->toBe(1800.0);
});

it('deduplicates per user and only counts the latest recurring payment', function () {
    insertStatsUser('u-1', $this->team->id, $this->pid);
    insertPayment('p-old', 'u-1', $this->team->id, $this->pid, 30, PaymentInterval::MONTH, '2026-06-01 10:00:00');
    insertPayment('p-new', 'u-1', $this->team->id, $this->pid, 50, PaymentInterval::MONTH, '2026-06-15 10:00:00');

    $snapshot = $this->service->getMrrSnapshot(dayFilter($this->pid, '2026-06-21'));

    expect($snapshot['mrr'])->toBe(50.0);
});

it('drops a subscription whose last payment falls outside the interval window (churn)', function () {
    insertStatsUser('u-1', $this->team->id, $this->pid);
    // Last monthly payment in April, snapshot in June -> well past month + grace.
    insertPayment('p-1', 'u-1', $this->team->id, $this->pid, 50, PaymentInterval::MONTH, '2026-04-01 10:00:00');

    $snapshot = $this->service->getMrrSnapshot(dayFilter($this->pid, '2026-06-21'));

    expect($snapshot['mrr'])->toBe(0.0);
});

it('ignores one-time payments', function () {
    insertStatsUser('u-1', $this->team->id, $this->pid);
    insertPayment('p-1', 'u-1', $this->team->id, $this->pid, 500, null, '2026-06-20 10:00:00');

    $snapshot = $this->service->getMrrSnapshot(dayFilter($this->pid, '2026-06-21'));

    expect($snapshot['mrr'])->toBe(0.0)
        ->and($snapshot['arr'])->toBe(0.0);
});

it('reflects subscription growth between range start and range end', function () {
    insertStatsUser('u-1', $this->team->id, $this->pid);
    // First payment lands inside the month range (after start, before end).
    insertPayment('p-1', 'u-1', $this->team->id, $this->pid, 40, PaymentInterval::MONTH, '2026-06-10 10:00:00');

    $filter = new StatsFilterDto(
        projectId: $this->pid, rangeAmount: 1, rangeType: StatsRangeType::MONTH,
        rangeStart: CarbonImmutable::parse('2026-06-21'), comparison: '0',
    );

    $snapshot = $this->service->getMrrSnapshot($filter);

    // Active at range end, did not exist yet at range start.
    expect($snapshot['mrr'])->toBe(40.0)
        ->and($snapshot['mrr_prev'])->toBe(0.0);
});

it('attributes MRR per channel via the source filter', function () {
    $sourceId = DB::table('stats_track_sources')->insertGetId([
        'team_id' => $this->team->id,
        'name' => 'newsletter',
        'created_at' => '2026-06-01 12:00:00',
        'updated_at' => '2026-06-01 12:00:00',
    ]);

    insertStatsUser('u-newsletter', $this->team->id, $this->pid, $sourceId);
    insertStatsUser('u-direct', $this->team->id, $this->pid, null);
    insertPayment('p-newsletter', 'u-newsletter', $this->team->id, $this->pid, 50, PaymentInterval::MONTH, '2026-06-15 10:00:00');
    insertPayment('p-direct', 'u-direct', $this->team->id, $this->pid, 80, PaymentInterval::MONTH, '2026-06-15 10:00:00');

    $unfiltered = $this->service->getMrrSnapshot(dayFilter($this->pid, '2026-06-21'));
    $filtered = $this->service->getMrrSnapshot(dayFilter($this->pid, '2026-06-21', $sourceId));

    expect($unfiltered['mrr'])->toBe(130.0)
        ->and($filtered['mrr'])->toBe(50.0);
});

it('reports the same current MRR for day and month ranges (as-of now, not the future bucket end)', function () {
    insertStatsUser('u-1', $this->team->id, $this->pid);
    // Last monthly payment on May 20: still active today (June 21, within grace),
    // but already lapsed relative to the month-end (June 30) bucket boundary.
    insertPayment('p-1', 'u-1', $this->team->id, $this->pid, 60, PaymentInterval::MONTH, '2026-05-20 10:00:00');

    $day = $this->service->getMrrSnapshot(dayFilter($this->pid, '2026-06-21'));

    $month = $this->service->getMrrSnapshot(new StatsFilterDto(
        projectId: $this->pid, rangeAmount: 6, rangeType: StatsRangeType::MONTH,
        rangeStart: CarbonImmutable::parse('2026-06-21'), comparison: '0',
    ));

    expect($day['mrr'])->toBe(60.0)
        ->and($month['mrr'])->toBe(60.0);
});

it('caps the latest time-series bucket at now so it matches the day snapshot', function () {
    insertStatsUser('u-1', $this->team->id, $this->pid);
    insertPayment('p-1', 'u-1', $this->team->id, $this->pid, 60, PaymentInterval::MONTH, '2026-05-20 10:00:00');

    $series = $this->service->getMrrTimeSeries(new StatsFilterDto(
        projectId: $this->pid, rangeAmount: 6, rangeType: StatsRangeType::MONTH,
        rangeStart: CarbonImmutable::parse('2026-06-21'), comparison: '0',
    ));

    expect(collect($series)->first()['mrr'])->toBe(60.0);
});

it('returns zero when there are no recurring payments', function () {
    $snapshot = $this->service->getMrrSnapshot(dayFilter($this->pid, '2026-06-21'));

    expect($snapshot['mrr'])->toBe(0.0)
        ->and($snapshot['arr'])->toBe(0.0)
        ->and($snapshot['mrr_prev'])->toBe(0.0);
});

function monthFilter(int $projectId, string $rangeStart, int $rangeAmount, int $trackSource = 0): StatsFilterDto
{
    return new StatsFilterDto(
        projectId: $projectId, rangeAmount: $rangeAmount, rangeType: StatsRangeType::MONTH,
        rangeStart: CarbonImmutable::parse($rangeStart), comparison: '0',
        trackSource: $trackSource,
    );
}

it('builds one MRR snapshot per bucket and reflects churn over time', function () {
    insertStatsUser('u-1', $this->team->id, $this->pid);
    // One monthly payment in March that is never renewed -> active only in its window.
    insertPayment('p-1', 'u-1', $this->team->id, $this->pid, 30, PaymentInterval::MONTH, '2026-03-10 10:00:00');

    $series = $this->service->getMrrTimeSeries(monthFilter($this->pid, '2026-06-21', 6));

    // Buckets ordered newest first: 06, 05, 04, 03, 02, 01.
    expect(collect($series)->pluck('mrr')->all())->toBe([0.0, 0.0, 0.0, 30.0, 0.0, 0.0]);
});

it('normalises yearly to net/12 and counts monthly in full per bucket', function () {
    insertStatsUser('u-month', $this->team->id, $this->pid);
    insertStatsUser('u-year', $this->team->id, $this->pid);
    insertPayment('p-month', 'u-month', $this->team->id, $this->pid, 50, PaymentInterval::MONTH, '2026-06-15 10:00:00');
    insertPayment('p-year', 'u-year', $this->team->id, $this->pid, 1200, PaymentInterval::YEAR, '2026-06-15 10:00:00');

    $series = $this->service->getMrrTimeSeries(dayFilter($this->pid, '2026-06-21'));

    expect(collect($series)->pluck('mrr')->all())->toBe([150.0]);
});

it('attributes the MRR line per channel via the source filter', function () {
    $sourceId = DB::table('stats_track_sources')->insertGetId([
        'team_id' => $this->team->id,
        'name' => 'newsletter',
        'created_at' => '2026-06-01 12:00:00',
        'updated_at' => '2026-06-01 12:00:00',
    ]);

    insertStatsUser('u-newsletter', $this->team->id, $this->pid, $sourceId);
    insertStatsUser('u-direct', $this->team->id, $this->pid, null);
    insertPayment('p-newsletter', 'u-newsletter', $this->team->id, $this->pid, 50, PaymentInterval::MONTH, '2026-06-15 10:00:00');
    insertPayment('p-direct', 'u-direct', $this->team->id, $this->pid, 80, PaymentInterval::MONTH, '2026-06-15 10:00:00');

    $unfiltered = $this->service->getMrrTimeSeries(dayFilter($this->pid, '2026-06-21'));
    $filtered = $this->service->getMrrTimeSeries(dayFilter($this->pid, '2026-06-21', $sourceId));

    expect(collect($unfiltered)->first()['mrr'])->toBe(130.0)
        ->and(collect($filtered)->first()['mrr'])->toBe(50.0);
});

it('returns a zero line when there are no recurring payments', function () {
    $series = $this->service->getMrrTimeSeries(dayFilter($this->pid, '2026-06-21'));

    expect($series)->toHaveCount(1)
        ->and(collect($series)->pluck('mrr')->all())->toBe([0.0]);
});

it('matches the headline snapshot on the latest bucket', function () {
    insertStatsUser('u-1', $this->team->id, $this->pid);
    insertPayment('p-1', 'u-1', $this->team->id, $this->pid, 50, PaymentInterval::MONTH, '2026-06-15 10:00:00');

    $filter = dayFilter($this->pid, '2026-06-21');

    $series = $this->service->getMrrTimeSeries($filter);
    $snapshot = $this->service->getMrrSnapshot($filter);

    expect(collect($series)->first()['mrr'])->toBe($snapshot['mrr']);
});

it('counts active subscribers per bucket and drops them on churn', function () {
    insertStatsUser('u-1', $this->team->id, $this->pid);
    insertStatsUser('u-2', $this->team->id, $this->pid);
    // u-1 active in June, u-2 only paid in March -> churned by June, active in March.
    insertPayment('p-1', 'u-1', $this->team->id, $this->pid, 50, PaymentInterval::MONTH, '2026-06-15 10:00:00');
    insertPayment('p-2', 'u-2', $this->team->id, $this->pid, 30, PaymentInterval::MONTH, '2026-03-10 10:00:00');

    $series = $this->service->getMrrTimeSeries(monthFilter($this->pid, '2026-06-21', 6));

    // Buckets ordered newest first: 06, 05, 04, 03, 02, 01.
    expect(collect($series)->pluck('subscribers')->all())->toBe([1, 0, 0, 1, 0, 0]);
});

it('reports a zero subscriber count when there are no recurring payments', function () {
    $series = $this->service->getMrrTimeSeries(dayFilter($this->pid, '2026-06-21'));

    expect(collect($series)->pluck('subscribers')->all())->toBe([0]);
});

it('detects whether the project has any recurring payment', function () {
    insertStatsUser('u-1', $this->team->id, $this->pid);
    insertPayment('p-1', 'u-1', $this->team->id, $this->pid, 500, null, '2026-06-20 10:00:00');

    // Only a one-time payment so far.
    expect($this->service->hasRecurringPayments(dayFilter($this->pid, '2026-06-21')))->toBeFalse();

    insertPayment('p-2', 'u-1', $this->team->id, $this->pid, 50, PaymentInterval::MONTH, '2026-06-15 10:00:00');

    expect($this->service->hasRecurringPayments(dayFilter($this->pid, '2026-06-21')))->toBeTrue();
});

it('reports no recurring payments for an empty project', function () {
    expect($this->service->hasRecurringPayments(dayFilter($this->pid, '2026-06-21')))->toBeFalse();
});

it('classifies a new subscription and its later churn as movements', function () {
    insertStatsUser('u-1', $this->team->id, $this->pid);
    insertPayment('p-1', 'u-1', $this->team->id, $this->pid, 30, PaymentInterval::MONTH, '2026-03-10 10:00:00');

    $movements = $this->service->getMrrMovements(monthFilter($this->pid, '2026-06-21', 6));

    expect($movements)->toHaveCount(6)
        ->and(collect($movements)->sum('new'))->toBe(30.0)
        ->and(collect($movements)->sum('churn'))->toBe(30.0)
        ->and(collect($movements)->sum('reactivation'))->toBe(0.0)
        ->and(collect($movements)->firstWhere('new', 30.0)['starting_mrr'])->toBe(0.0)
        ->and(collect($movements)->firstWhere('new', 30.0)['ending_mrr'])->toBe(30.0);
});

it('classifies plan changes as expansion and contraction', function () {
    insertStatsUser('u-1', $this->team->id, $this->pid);
    insertPayment('p-1', 'u-1', $this->team->id, $this->pid, 50, PaymentInterval::MONTH, '2026-03-05 10:00:00');
    insertPayment('p-2', 'u-1', $this->team->id, $this->pid, 80, PaymentInterval::MONTH, '2026-04-05 10:00:00');
    insertPayment('p-3', 'u-1', $this->team->id, $this->pid, 60, PaymentInterval::MONTH, '2026-05-05 10:00:00');

    $movements = $this->service->getMrrMovements(monthFilter($this->pid, '2026-06-21', 6));

    expect(collect($movements)->sum('new'))->toBe(50.0)
        ->and(collect($movements)->sum('expansion'))->toBe(30.0)
        ->and(collect($movements)->sum('contraction'))->toBe(20.0)
        ->and(collect($movements)->sum('churn'))->toBe(60.0)
        ->and(collect($movements)->sum('reactivation'))->toBe(0.0);
});

it('distinguishes reactivation from new when a user returns after churn', function () {
    insertStatsUser('u-1', $this->team->id, $this->pid);
    insertPayment('p-1', 'u-1', $this->team->id, $this->pid, 50, PaymentInterval::MONTH, '2026-02-05 10:00:00');
    insertPayment('p-2', 'u-1', $this->team->id, $this->pid, 50, PaymentInterval::MONTH, '2026-05-05 10:00:00');

    $movements = $this->service->getMrrMovements(monthFilter($this->pid, '2026-06-21', 6));

    expect(collect($movements)->sum('new'))->toBe(50.0)
        ->and(collect($movements)->sum('reactivation'))->toBe(50.0)
        ->and(collect($movements)->sum('churn'))->toBe(100.0)
        ->and(collect($movements)->sum('expansion'))->toBe(0.0);
});

it('keeps starting + movements = ending per bucket', function () {
    insertStatsUser('u-1', $this->team->id, $this->pid);
    insertStatsUser('u-2', $this->team->id, $this->pid);
    insertPayment('p-1', 'u-1', $this->team->id, $this->pid, 50, PaymentInterval::MONTH, '2026-03-05 10:00:00');
    insertPayment('p-2', 'u-1', $this->team->id, $this->pid, 80, PaymentInterval::MONTH, '2026-04-05 10:00:00');
    insertPayment('p-3', 'u-2', $this->team->id, $this->pid, 120, PaymentInterval::YEAR, '2026-04-20 10:00:00');

    $movements = $this->service->getMrrMovements(monthFilter($this->pid, '2026-06-21', 6));

    foreach ($movements as $m) {
        $expected = round($m['starting_mrr'] + $m['new'] + $m['reactivation'] + $m['expansion'] - $m['contraction'] - $m['churn'], 2);
        expect($m['ending_mrr'])->toBe($expected)
            ->and($m['net_change'])->toBe(round($m['ending_mrr'] - $m['starting_mrr'], 2));
    }
});

it('returns no movements when there are no recurring payments', function () {
    $movements = $this->service->getMrrMovements(monthFilter($this->pid, '2026-06-21', 6));

    expect($movements)->toHaveCount(6)
        ->and(collect($movements)->sum('new'))->toBe(0.0)
        ->and(collect($movements)->sum('ending_mrr'))->toBe(0.0);
});

it('derives nrr, grr and quick ratio from the movement totals', function () {
    insertStatsUser('u-stable', $this->team->id, $this->pid);
    insertStatsUser('u-expand', $this->team->id, $this->pid);
    insertStatsUser('u-churn', $this->team->id, $this->pid);

    // Active at range start (April leading bucket) and renewing across the range.
    insertPayment('s-1', 'u-stable', $this->team->id, $this->pid, 100, PaymentInterval::MONTH, '2026-04-15 10:00:00');
    insertPayment('s-2', 'u-stable', $this->team->id, $this->pid, 100, PaymentInterval::MONTH, '2026-05-15 10:00:00');
    insertPayment('s-3', 'u-stable', $this->team->id, $this->pid, 100, PaymentInterval::MONTH, '2026-06-15 10:00:00');

    insertPayment('e-1', 'u-expand', $this->team->id, $this->pid, 50, PaymentInterval::MONTH, '2026-04-15 10:00:00');
    insertPayment('e-2', 'u-expand', $this->team->id, $this->pid, 50, PaymentInterval::MONTH, '2026-05-15 10:00:00');
    insertPayment('e-3', 'u-expand', $this->team->id, $this->pid, 80, PaymentInterval::MONTH, '2026-06-15 10:00:00');

    // Only active at range start, lapses inside the range.
    insertPayment('c-1', 'u-churn', $this->team->id, $this->pid, 40, PaymentInterval::MONTH, '2026-04-15 10:00:00');

    $retention = $this->service->getMrrRetention(monthFilter($this->pid, '2026-06-21', 2));

    expect($retention['starting_mrr'])->toBe(190.0)
        ->and($retention['ending_mrr'])->toBe(180.0)
        ->and($retention['expansion'])->toBe(30.0)
        ->and($retention['churn'])->toBe(40.0)
        ->and($retention['contraction'])->toBe(0.0)
        ->and($retention['new'])->toBe(0.0)
        ->and($retention['grr'])->toBe(78.9)
        ->and($retention['nrr'])->toBe(94.7)
        ->and($retention['quick_ratio'])->toBe(0.75);
});

it('returns null retention ratios when there is no starting mrr', function () {
    insertStatsUser('u-1', $this->team->id, $this->pid);
    insertPayment('p-1', 'u-1', $this->team->id, $this->pid, 50, PaymentInterval::MONTH, '2026-05-20 10:00:00');

    $retention = $this->service->getMrrRetention(monthFilter($this->pid, '2026-06-21', 2));

    expect($retention['starting_mrr'])->toBe(0.0)
        ->and($retention['nrr'])->toBeNull()
        ->and($retention['grr'])->toBeNull();
});
