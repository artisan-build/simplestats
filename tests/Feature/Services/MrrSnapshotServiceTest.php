<?php

use App\DTOs\StatsFilterDto;
use App\Enums\PaymentInterval;
use App\Enums\StatsRangeType;
use App\Models\Project;
use App\Models\User;
use App\Services\StatsAggregationsService;
use Carbon\CarbonImmutable;
use Database\Seeders\TestUserSeeder;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed(TestUserSeeder::class);
    $this->user = User::where('email', config('app.test.user')['email'])->firstOrFail();
    $this->project = Project::first();

    $this->team = $this->project->team;
    $this->pid = $this->project->id;

    DB::table('stats_payments')->delete();
    DB::table('stats_users')->delete();

    auth()->setUser($this->user);
    Context::add('team_id', $this->team->id);

    $this->service = app(StatsAggregationsService::class);
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

it('returns zero when there are no recurring payments', function () {
    $snapshot = $this->service->getMrrSnapshot(dayFilter($this->pid, '2026-06-21'));

    expect($snapshot['mrr'])->toBe(0.0)
        ->and($snapshot['arr'])->toBe(0.0)
        ->and($snapshot['mrr_prev'])->toBe(0.0);
});
