<?php

use App\DTOs\StatsFilterDto;
use App\Enums\StatsRangeType;
use App\Models\Project;
use App\Models\User;
use App\Services\FunnelTimingService;
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
    DB::table('stats_visitors')->delete();

    auth()->setUser($this->user);
    Context::add('team_id', $this->team->id);

    $this->service = app(FunnelTimingService::class);
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

function insertFtUser(string $id, int $teamId, int $projectId, string $time, ?int $visitorId = null, ?int $sourceId = null): void
{
    DB::table('stats_users')->insert([
        'id' => $id,
        'team_id' => $teamId,
        'project_id' => $projectId,
        'stats_visitor_id' => $visitorId,
        'stats_track_source_id' => $sourceId,
        'registered' => true,
        'time' => $time,
        'time_utc' => $time,
    ]);
}

function insertFtPayment(string $id, string $userId, int $teamId, int $projectId, int $counter, string $time): void
{
    DB::table('stats_payments')->insert([
        'id' => $id,
        'stats_user_id' => $userId,
        'team_id' => $teamId,
        'project_id' => $projectId,
        'gross' => 100,
        'net' => 100,
        'currency' => 'EUR',
        'counter' => $counter,
        'time' => $time,
        'time_utc' => $time,
    ]);
}

function insertFtVisitor(int $teamId, int $projectId, string $hash, string $time): int
{
    return DB::table('stats_visitors')->insertGetId([
        'team_id' => $teamId,
        'project_id' => $projectId,
        'visitor_hash' => $hash,
        'time' => $time,
        'time_utc' => $time,
    ]);
}

function ftMonthFilter(int $projectId, string $rangeStart, int $rangeAmount, int $trackSource = 0): StatsFilterDto
{
    return new StatsFilterDto(
        projectId: $projectId, rangeAmount: $rangeAmount, rangeType: StatsRangeType::MONTH,
        rangeStart: CarbonImmutable::parse($rangeStart), comparison: '0',
        trackSource: $trackSource,
    );
}

it('returns the median days from registration to first payment', function () {
    insertFtUser('u1', $this->team->id, $this->pid, '2026-06-01 10:00:00');
    insertFtUser('u2', $this->team->id, $this->pid, '2026-06-02 10:00:00');
    insertFtUser('u3', $this->team->id, $this->pid, '2026-06-03 10:00:00');

    insertFtPayment('p1', 'u1', $this->team->id, $this->pid, 1, '2026-06-06 10:00:00'); // 5 days
    insertFtPayment('p2', 'u2', $this->team->id, $this->pid, 1, '2026-06-12 10:00:00'); // 10 days
    insertFtPayment('p3', 'u3', $this->team->id, $this->pid, 1, '2026-06-06 10:00:00'); // 3 days

    // median of [3, 5, 10] = 5
    expect($this->service->getTimeToPay(ftMonthFilter($this->pid, '2026-06-21', 1)))->toBe(5.0);
});

it('only counts the first payment (counter = 1) for time to pay', function () {
    insertFtUser('u1', $this->team->id, $this->pid, '2026-06-01 10:00:00');

    insertFtPayment('p1', 'u1', $this->team->id, $this->pid, 1, '2026-06-05 10:00:00'); // first: 4 days
    insertFtPayment('p2', 'u1', $this->team->id, $this->pid, 2, '2026-06-20 10:00:00'); // renewal, ignored

    expect($this->service->getTimeToPay(ftMonthFilter($this->pid, '2026-06-21', 1)))->toBe(4.0);
});

it('returns null for time to pay when no first payment falls in the range', function () {
    insertFtUser('u1', $this->team->id, $this->pid, '2026-06-01 10:00:00');

    expect($this->service->getTimeToPay(ftMonthFilter($this->pid, '2026-06-21', 1)))->toBeNull();
});

it('attributes time to pay per channel via the source filter', function () {
    $sourceId = DB::table('stats_track_sources')->insertGetId([
        'team_id' => $this->team->id,
        'name' => 'newsletter',
        'created_at' => '2026-06-01 12:00:00',
        'updated_at' => '2026-06-01 12:00:00',
    ]);

    insertFtUser('u-nl', $this->team->id, $this->pid, '2026-06-01 10:00:00', sourceId: $sourceId);
    insertFtUser('u-direct', $this->team->id, $this->pid, '2026-06-01 10:00:00');

    insertFtPayment('p-nl', 'u-nl', $this->team->id, $this->pid, 1, '2026-06-11 10:00:00'); // 10 days
    insertFtPayment('p-direct', 'u-direct', $this->team->id, $this->pid, 1, '2026-06-03 10:00:00'); // 2 days

    // unfiltered median of [2, 10] = 6; newsletter-only [10] = 10
    expect($this->service->getTimeToPay(ftMonthFilter($this->pid, '2026-06-21', 1)))->toBe(6.0)
        ->and($this->service->getTimeToPay(ftMonthFilter($this->pid, '2026-06-21', 1, $sourceId)))->toBe(10.0);
});

it('returns the per-bucket median days from first visit to registration', function () {
    $v1 = insertFtVisitor($this->team->id, $this->pid, 'h1', '2026-06-01 10:00:00');
    $v2 = insertFtVisitor($this->team->id, $this->pid, 'h2', '2026-06-01 10:00:00');

    insertFtUser('u1', $this->team->id, $this->pid, '2026-06-04 10:00:00', visitorId: $v1); // 3 days
    insertFtUser('u2', $this->team->id, $this->pid, '2026-06-08 10:00:00', visitorId: $v2); // 7 days

    $series = $this->service->getTimeToConvertSeries(ftMonthFilter($this->pid, '2026-06-21', 1));

    // one June bucket, median of [3, 7] = 5
    expect($series)->toHaveCount(1)
        ->and($series[0]['time_to_convert'])->toBe(5.0);
});

it('returns an empty time to convert series when registrations have no linked visitor', function () {
    insertFtUser('u1', $this->team->id, $this->pid, '2026-06-04 10:00:00');

    expect($this->service->getTimeToConvertSeries(ftMonthFilter($this->pid, '2026-06-21', 1)))->toBe([]);
});

it('attributes time to convert per channel via the source filter', function () {
    $sourceId = DB::table('stats_track_sources')->insertGetId([
        'team_id' => $this->team->id,
        'name' => 'newsletter',
        'created_at' => '2026-06-01 12:00:00',
        'updated_at' => '2026-06-01 12:00:00',
    ]);

    $vNl = insertFtVisitor($this->team->id, $this->pid, 'h-nl', '2026-06-01 10:00:00');
    $vDirect = insertFtVisitor($this->team->id, $this->pid, 'h-direct', '2026-06-01 10:00:00');

    insertFtUser('u-nl', $this->team->id, $this->pid, '2026-06-11 10:00:00', visitorId: $vNl, sourceId: $sourceId); // 10 days
    insertFtUser('u-direct', $this->team->id, $this->pid, '2026-06-03 10:00:00', visitorId: $vDirect); // 2 days

    $unfiltered = $this->service->getTimeToConvertSeries(ftMonthFilter($this->pid, '2026-06-21', 1));
    $newsletterOnly = $this->service->getTimeToConvertSeries(ftMonthFilter($this->pid, '2026-06-21', 1, $sourceId));

    // unfiltered June median of [2, 10] = 6; newsletter-only [10] = 10
    expect($unfiltered[0]['time_to_convert'])->toBe(6.0)
        ->and($newsletterOnly[0]['time_to_convert'])->toBe(10.0);
});
