<?php

use App\DTOs\StatsFilterDto;
use App\Enums\StatsRangeType;
use App\Models\Project;
use App\Models\StatsPayment;
use App\Models\User;
use App\Services\StatsAggregationsService;
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

    DB::table('stats_payments')->delete();
    DB::table('stats_logins')->delete();
    DB::table('stats_visitors')->delete();
    DB::table('stats_users')->delete();

    auth()->setUser($this->user);
    Context::add('team_id', $this->team->id);
});

afterEach(function () {
    Carbon::setTestNow();
});

function makeSource(int $teamId, string $name): int
{
    return DB::table('stats_track_sources')->insertGetId([
        'team_id' => $teamId,
        'name' => $name,
        'created_at' => '2026-01-01 00:00:00',
        'updated_at' => '2026-01-01 00:00:00',
    ]);
}

function insertSourceVisitors(int $teamId, int $projectId, int $sourceId, string $time, int $count): void
{
    for ($i = 0; $i < $count; $i++) {
        DB::table('stats_visitors')->insert([
            'team_id' => $teamId,
            'project_id' => $projectId,
            'visitor_hash' => Str::uuid()->toString(),
            'stats_track_source_id' => $sourceId,
            'time' => $time,
            'time_utc' => $time,
        ]);
    }
}

function insertSourceVisitorReturningId(int $teamId, int $projectId, int $sourceId, string $time): int
{
    return DB::table('stats_visitors')->insertGetId([
        'team_id' => $teamId,
        'project_id' => $projectId,
        'visitor_hash' => Str::uuid()->toString(),
        'stats_track_source_id' => $sourceId,
        'time' => $time,
        'time_utc' => $time,
    ]);
}

function insertSourceUser(int $teamId, int $projectId, int $sourceId, string $id, string $time, ?int $visitorId = null): void
{
    DB::table('stats_users')->insert([
        'id' => $id,
        'team_id' => $teamId,
        'project_id' => $projectId,
        'stats_track_source_id' => $sourceId,
        'stats_visitor_id' => $visitorId,
        'time' => $time,
        'time_utc' => $time,
        'registered' => true,
    ]);
}

it('computes per-dimension revenue ratios (arpv/arpu) in the grouped breakdown', function () {
    $sourceId = makeSource($this->team->id, 'newsletter');

    insertSourceVisitors($this->team->id, $this->pid, $sourceId, '2026-03-30 12:00:00', 10);
    insertSourceUser($this->team->id, $this->pid, $sourceId, 'u1', '2026-03-30 12:00:00');
    insertSourceUser($this->team->id, $this->pid, $sourceId, 'u2', '2026-03-30 12:00:00');

    StatsPayment::factory()->create([
        'stats_user_id' => 'u1',
        'stats_visitor_id' => null,
        'team_id' => $this->team->id,
        'project_id' => $this->pid,
        'gross' => 100,
        'net' => 100,
        'time' => '2026-03-30 13:00:00',
        'time_utc' => '2026-03-30 13:00:00',
    ]);

    $filter = new StatsFilterDto(
        projectId: $this->pid, rangeAmount: 1, rangeType: StatsRangeType::DAY,
        rangeStart: CarbonImmutable::parse('2026-03-30'), comparison: '0',
    );

    $grouped = app(StatsAggregationsService::class)
        ->getAggregatedGroupedData($filter, 'track_source', 'arpv', 10);

    $row = $grouped->firstWhere('name', 'newsletter');

    expect($row)->not->toBeNull()
        ->and((int) $row['visitors'])->toBe(10)
        ->and((int) $row['reg'])->toBe(2)
        ->and((float) $row['net'])->toBe(100.0)
        ->and((float) $row['arpv'])->toBe(10.0)  // 100 / 10 visitors
        ->and((float) $row['arpu'])->toBe(50.0)  // 100 / 2 regs
        // no paying-day logins, so the DAU-derived ratios safely coalesce to 0
        ->and((float) $row['arpdau'])->toBe(0.0)
        ->and((float) $row['reg2dau'])->toBe(0.0);
});

it('counts the registration login (counter 1) in the grouped DAU, matching the headline DAU definition', function () {
    Carbon::setTestNow(Carbon::parse('2026-04-05 12:00:00'));

    $sourceId = makeSource($this->team->id, 'newsletter');
    insertSourceUser($this->team->id, $this->pid, $sourceId, 'u1', '2026-03-30 12:00:00');

    // The registration login carries counter 1. The DAU definition is "logged in OR
    // registered", so this user is active on 2026-03-30 exactly like the headline pd_dau counts them.
    DB::table('stats_logins')->insert([
        'stats_user_id' => 'u1',
        'team_id' => $this->team->id,
        'project_id' => $this->pid,
        'time' => '2026-03-30 12:00:00',
        'time_utc' => '2026-03-30 12:00:00',
        'counter' => 1,
    ]);

    $filter = new StatsFilterDto(
        projectId: $this->pid, rangeAmount: 1, rangeType: StatsRangeType::DAY,
        rangeStart: CarbonImmutable::parse('2026-03-30'), comparison: '0',
    );

    $row = app(StatsAggregationsService::class)
        ->getAggregatedGroupedData($filter, 'track_source', 'dau', 10)
        ->firstWhere('name', 'newsletter');

    expect($row)->not->toBeNull()
        ->and((int) $row['reg'])->toBe(1)
        ->and((int) $row['dau'])->toBe(1)
        ->and((float) $row['reg2dau'])->toBe(100.0);
});

it('computes a per-dimension MRR snapshot when withMrr is enabled', function () {
    Carbon::setTestNow(Carbon::parse('2026-06-15 12:00:00'));

    $this->project->update(['usage' => $this->project->usage | Project::USAGE_SUBSCRIPTION]);

    $sourceId = makeSource($this->team->id, 'newsletter');

    insertSourceUser($this->team->id, $this->pid, $sourceId, 'u1', '2026-06-01 12:00:00');
    insertSourceUser($this->team->id, $this->pid, $sourceId, 'u2', '2026-06-01 12:00:00');

    StatsPayment::factory()->subscriptionMonthly()->create([
        'stats_user_id' => 'u1', 'stats_visitor_id' => null,
        'team_id' => $this->team->id, 'project_id' => $this->pid,
        'gross' => 30, 'net' => 30,
        'time' => '2026-06-10 12:00:00', 'time_utc' => '2026-06-10 12:00:00',
    ]);

    StatsPayment::factory()->subscriptionYearly()->create([
        'stats_user_id' => 'u2', 'stats_visitor_id' => null,
        'team_id' => $this->team->id, 'project_id' => $this->pid,
        'gross' => 120, 'net' => 120,
        'time' => '2026-06-10 12:00:00', 'time_utc' => '2026-06-10 12:00:00',
    ]);

    $filter = new StatsFilterDto(
        projectId: $this->pid, rangeAmount: 1, rangeType: StatsRangeType::MONTH,
        rangeStart: CarbonImmutable::parse('2026-06-15'), comparison: '0',
    );

    $grouped = app(StatsAggregationsService::class)
        ->getAggregatedGroupedData($filter, 'track_source', 'mrr', 10, withMrr: true);

    $row = $grouped->firstWhere('name', 'newsletter');

    // monthly 30 + yearly 120/12 = 40; two subscribers; arps = 40 / 2
    expect($row)->not->toBeNull()
        ->and((int) $row['subscribers'])->toBe(2)
        ->and((float) $row['mrr'])->toBe(40.0)
        ->and((float) $row['arps'])->toBe(20.0);
});

it('omits MRR columns by default so the response stays backward compatible', function () {
    $sourceId = makeSource($this->team->id, 'newsletter');
    insertSourceVisitors($this->team->id, $this->pid, $sourceId, '2026-03-30 12:00:00', 3);

    $filter = new StatsFilterDto(
        projectId: $this->pid, rangeAmount: 1, rangeType: StatsRangeType::DAY,
        rangeStart: CarbonImmutable::parse('2026-03-30'), comparison: '0',
    );

    $row = app(StatsAggregationsService::class)
        ->getAggregatedGroupedData($filter, 'track_source', 'visitors', 10)
        ->firstWhere('name', 'newsletter');

    expect($row)->not->toBeNull()
        ->and($row)->not->toHaveKey('mrr')
        ->and($row)->not->toHaveKey('subscribers')
        ->and($row)->toHaveKey('arpv');
});

it('does not compute MRR for a project that does not track subscriptions even when requested', function () {
    Carbon::setTestNow(Carbon::parse('2026-06-15 12:00:00'));

    // Drop the subscription bit (keep visitors+users+payments).
    $this->project->update(['usage' => Project::USAGE_VISITORS
        | Project::USAGE_USERS
        | Project::USAGE_PAYMENTS]);

    $sourceId = makeSource($this->team->id, 'newsletter');
    insertSourceUser($this->team->id, $this->pid, $sourceId, 'u1', '2026-06-01 12:00:00');

    StatsPayment::factory()->subscriptionMonthly()->create([
        'stats_user_id' => 'u1', 'stats_visitor_id' => null,
        'team_id' => $this->team->id, 'project_id' => $this->pid,
        'gross' => 30, 'net' => 30,
        'time' => '2026-06-10 12:00:00', 'time_utc' => '2026-06-10 12:00:00',
    ]);

    $filter = new StatsFilterDto(
        projectId: $this->pid, rangeAmount: 1, rangeType: StatsRangeType::MONTH,
        rangeStart: CarbonImmutable::parse('2026-06-15'), comparison: '0',
    );

    $row = app(StatsAggregationsService::class)
        ->getAggregatedGroupedData($filter, 'track_source', 'net', 10, withMrr: true)
        ->firstWhere('name', 'newsletter');

    expect($row)->not->toBeNull()
        ->and($row)->not->toHaveKey('mrr');
});

it('computes a per-dimension time-to-convert median when withTimeToConvert is enabled', function () {
    $this->project->update(['usage' => Project::USAGE_VISITORS | Project::USAGE_USERS]);

    $sourceId = makeSource($this->team->id, 'newsletter');

    $v1 = insertSourceVisitorReturningId($this->team->id, $this->pid, $sourceId, '2026-03-28 12:00:00');
    $v2 = insertSourceVisitorReturningId($this->team->id, $this->pid, $sourceId, '2026-03-26 12:00:00');

    insertSourceUser($this->team->id, $this->pid, $sourceId, 'u1', '2026-03-30 12:00:00', $v1);
    insertSourceUser($this->team->id, $this->pid, $sourceId, 'u2', '2026-03-30 12:00:00', $v2);

    $filter = new StatsFilterDto(
        projectId: $this->pid, rangeAmount: 1, rangeType: StatsRangeType::DAY,
        rangeStart: CarbonImmutable::parse('2026-03-30'), comparison: '0',
    );

    $row = app(StatsAggregationsService::class)
        ->getAggregatedGroupedData($filter, 'track_source', 'time_to_convert', 10, withTimeToConvert: true)
        ->firstWhere('name', 'newsletter');

    // median of 2 days (u1) and 4 days (u2)
    expect($row)->not->toBeNull()
        ->and((int) $row['reg'])->toBe(2)
        ->and((float) $row['time_to_convert'])->toBe(3.0);
});

it('omits the time-to-convert column by default', function () {
    $sourceId = makeSource($this->team->id, 'newsletter');
    insertSourceVisitors($this->team->id, $this->pid, $sourceId, '2026-03-30 12:00:00', 3);

    $filter = new StatsFilterDto(
        projectId: $this->pid, rangeAmount: 1, rangeType: StatsRangeType::DAY,
        rangeStart: CarbonImmutable::parse('2026-03-30'), comparison: '0',
    );

    $row = app(StatsAggregationsService::class)
        ->getAggregatedGroupedData($filter, 'track_source', 'visitors', 10)
        ->firstWhere('name', 'newsletter');

    expect($row)->not->toBeNull()
        ->and($row)->not->toHaveKey('time_to_convert');
});

it('serves per-dimension time-to-convert through the stats.grouped endpoint with metric=time_to_convert', function () {
    $this->project->update(['usage' => Project::USAGE_VISITORS | Project::USAGE_USERS]);

    $sourceId = makeSource($this->team->id, 'newsletter');

    $v1 = insertSourceVisitorReturningId($this->team->id, $this->pid, $sourceId, '2026-03-29 12:00:00');
    insertSourceUser($this->team->id, $this->pid, $sourceId, 'u1', '2026-03-30 12:00:00', $v1);

    $response = $this->actingAs($this->user)->getJson(route('stats.grouped', [
        'project_id' => $this->pid,
        'range_type' => StatsRangeType::DAY->value,
        'range_amount' => 1,
        'range_start' => '2026-03-30',
        'comparison' => '0',
        'stats_type' => 'track_source',
        'stats_sort' => 'time_to_convert',
        'metric' => 'time_to_convert',
    ]));

    $response->assertOk();

    $row = collect($response->json('data'))->firstWhere('name', 'newsletter');

    expect($row)->not->toBeNull()
        ->and((float) $row['time_to_convert'])->toBe(1.0);
});

it('serves per-dimension MRR through the stats.grouped endpoint with metric=mrr', function () {
    Carbon::setTestNow(Carbon::parse('2026-06-15 12:00:00'));

    $this->project->update(['usage' => $this->project->usage | Project::USAGE_SUBSCRIPTION]);

    $sourceId = makeSource($this->team->id, 'newsletter');
    insertSourceUser($this->team->id, $this->pid, $sourceId, 'u1', '2026-06-01 12:00:00');

    StatsPayment::factory()->subscriptionMonthly()->create([
        'stats_user_id' => 'u1', 'stats_visitor_id' => null,
        'team_id' => $this->team->id, 'project_id' => $this->pid,
        'gross' => 30, 'net' => 30,
        'time' => '2026-06-10 12:00:00', 'time_utc' => '2026-06-10 12:00:00',
    ]);

    $response = $this->actingAs($this->user)->getJson(route('stats.grouped', [
        'project_id' => $this->pid,
        'range_type' => StatsRangeType::MONTH->value,
        'range_amount' => 1,
        'range_start' => '2026-06-15',
        'comparison' => '0',
        'stats_type' => 'track_source',
        'stats_sort' => 'mrr',
        'metric' => 'mrr',
    ]));

    $response->assertOk();

    $row = collect($response->json('data'))->firstWhere('name', 'newsletter');

    expect($row)->not->toBeNull()
        ->and((float) $row['mrr'])->toBe(30.0)
        ->and((int) $row['subscribers'])->toBe(1);
});

it('serves the per-dimension MRR breakdown for the All-projects scope sorted by mrr', function () {
    Carbon::setTestNow(Carbon::parse('2026-06-15 12:00:00'));

    $this->project->update(['usage' => $this->project->usage | Project::USAGE_SUBSCRIPTION]);

    $sourceId = makeSource($this->team->id, 'newsletter');
    insertSourceUser($this->team->id, $this->pid, $sourceId, 'u1', '2026-06-01 12:00:00');

    StatsPayment::factory()->subscriptionMonthly()->create([
        'stats_user_id' => 'u1', 'stats_visitor_id' => null,
        'team_id' => $this->team->id, 'project_id' => $this->pid,
        'gross' => 30, 'net' => 30,
        'time' => '2026-06-10 12:00:00', 'time_utc' => '2026-06-10 12:00:00',
    ]);

    // "All projects" (TRACK_ALL) has no concrete Project row: the breakdown must still
    // resolve the subscription opt-in from team-level usage instead of skipping the mrr
    // column while ordering by it (which previously crashed with "column mrr does not exist").
    $response = $this->actingAs($this->user)->getJson(route('stats.grouped', [
        'project_id' => StatsFilterDto::TRACK_ALL,
        'range_type' => StatsRangeType::MONTH->value,
        'range_amount' => 1,
        'range_start' => '2026-06-15',
        'comparison' => '0',
        'stats_type' => 'track_source',
        'stats_sort' => 'mrr',
        'metric' => 'mrr',
    ]));

    $response->assertOk();

    $row = collect($response->json('data'))->firstWhere('name', 'newsletter');

    expect($row)->not->toBeNull()
        ->and((float) $row['mrr'])->toBe(30.0)
        ->and((int) $row['subscribers'])->toBe(1);
});

it('falls back to a present column when sorted by a column the query did not select', function () {
    $sourceId = makeSource($this->team->id, 'newsletter');
    insertSourceVisitors($this->team->id, $this->pid, $sourceId, '2026-03-30 12:00:00', 3);

    $filter = new StatsFilterDto(
        projectId: $this->pid, rangeAmount: 1, rangeType: StatsRangeType::DAY,
        rangeStart: CarbonImmutable::parse('2026-03-30'), comparison: '0',
    );

    // withMrr stays false, so mrr is never selected; sorting by it must not emit an
    // ORDER BY on a missing column.
    $row = app(StatsAggregationsService::class)
        ->getAggregatedGroupedData($filter, 'track_source', 'mrr', 10)
        ->firstWhere('name', 'newsletter');

    expect($row)->not->toBeNull()
        ->and($row)->not->toHaveKey('mrr')
        ->and((int) $row['visitors'])->toBe(3);
});
