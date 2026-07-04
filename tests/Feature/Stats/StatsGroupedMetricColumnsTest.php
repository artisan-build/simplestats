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

function insertSourceUser(int $teamId, int $projectId, ?int $sourceId, string $id, string $time, ?int $visitorId = null, bool $registered = true): void
{
    DB::table('stats_users')->insert([
        'id' => $id,
        'team_id' => $teamId,
        'project_id' => $projectId,
        'stats_track_source_id' => $sourceId,
        'stats_visitor_id' => $visitorId,
        'time' => $time,
        'time_utc' => $time,
        'registered' => $registered,
    ]);
}

function insertReturnLogin(int $teamId, int $projectId, string $userId, string $time, int $counter = 2): void
{
    DB::table('stats_logins')->insert([
        'stats_user_id' => $userId,
        'team_id' => $teamId,
        'project_id' => $projectId,
        'time' => $time,
        'time_utc' => $time,
        'counter' => $counter,
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

it('computes unbounded per-dimension retention checkpoints when withRetention is enabled', function () {
    Carbon::setTestNow(Carbon::parse('2026-06-15 12:00:00'));

    $sourceId = makeSource($this->team->id, 'newsletter');

    // 30-day range -> checkpoints 1/7/15 days; all three registered 45 days ago,
    // so every checkpoint is observable
    insertSourceUser($this->team->id, $this->pid, $sourceId, 'u1', '2026-05-01 12:00:00');
    insertSourceUser($this->team->id, $this->pid, $sourceId, 'u2', '2026-05-01 12:00:00');
    insertSourceUser($this->team->id, $this->pid, $sourceId, 'u3', '2026-05-01 12:00:00');

    // u1 returns after 2 days (retained at D1 only), u2 after 31 days
    // (latest return login answers every checkpoint: D1, D7 and D15), u3 never
    insertReturnLogin($this->team->id, $this->pid, 'u1', '2026-05-03 12:00:00');
    insertReturnLogin($this->team->id, $this->pid, 'u2', '2026-06-01 12:00:00');

    $filter = new StatsFilterDto(
        projectId: $this->pid, rangeAmount: 30, rangeType: StatsRangeType::DAY,
        rangeStart: CarbonImmutable::parse('2026-05-01'), comparison: '0',
    );

    $row = app(StatsAggregationsService::class)
        ->getAggregatedGroupedData($filter, 'track_source', 'ret_c2', 10, withRetention: true)
        ->firstWhere('name', 'newsletter');

    expect($row)->not->toBeNull()
        ->and((int) $row['reg'])->toBe(3)
        ->and((float) $row['ret_c1'])->toBe(66.7)
        ->and((float) $row['ret_c2'])->toBe(33.3)
        ->and((float) $row['ret_c3'])->toBe(33.3);
});

it('does not count the registration login (counter 1) as retention while DAU still counts it', function () {
    Carbon::setTestNow(Carbon::parse('2026-06-15 12:00:00'));

    $sourceId = makeSource($this->team->id, 'newsletter');
    insertSourceUser($this->team->id, $this->pid, $sourceId, 'u1', '2026-05-01 12:00:00');
    insertReturnLogin($this->team->id, $this->pid, 'u1', '2026-05-10 12:00:00', counter: 1);

    $filter = new StatsFilterDto(
        projectId: $this->pid, rangeAmount: 1, rangeType: StatsRangeType::DAY,
        rangeStart: CarbonImmutable::parse('2026-05-01'), comparison: '0',
    );

    $row = app(StatsAggregationsService::class)
        ->getAggregatedGroupedData($filter, 'track_source', 'ret_c1', 10, withRetention: true)
        ->firstWhere('name', 'newsletter');

    expect($row)->not->toBeNull()
        ->and((float) $row['ret_c1'])->toBe(0.0)
        ->and((float) $row['ret_c2'])->toBe(0.0)
        ->and((float) $row['ret_c3'])->toBe(0.0);
});

it('right-censors retention checkpoints the cohort cannot have reached yet to null', function () {
    Carbon::setTestNow(Carbon::parse('2026-06-15 12:00:00'));

    $sourceId = makeSource($this->team->id, 'newsletter');

    // 30-day range -> checkpoints 1/7/15 days; registered 3 days ago:
    // D1 observable, D7/D15 not
    insertSourceUser($this->team->id, $this->pid, $sourceId, 'u1', '2026-06-12 12:00:00');
    insertReturnLogin($this->team->id, $this->pid, 'u1', '2026-06-13 12:00:00');

    $filter = new StatsFilterDto(
        projectId: $this->pid, rangeAmount: 30, rangeType: StatsRangeType::DAY,
        rangeStart: CarbonImmutable::parse('2026-06-12'), comparison: '0',
    );

    $row = app(StatsAggregationsService::class)
        ->getAggregatedGroupedData($filter, 'track_source', 'ret_c1', 10, withRetention: true)
        ->firstWhere('name', 'newsletter');

    expect($row)->not->toBeNull()
        ->and((float) $row['ret_c1'])->toBe(100.0)
        ->and($row['ret_c2'])->toBeNull()
        ->and($row['ret_c3'])->toBeNull();
});

it('treats a user registered exactly N days ago as observable and a return at exactly reg+N as retained', function () {
    Carbon::setTestNow(Carbon::parse('2026-06-15 12:00:00'));

    $sourceId = makeSource($this->team->id, 'newsletter');

    // 30-day range -> checkpoints 1/7/15 days; registered exactly 7 days before
    // now with a return login exactly at reg + 7 days
    insertSourceUser($this->team->id, $this->pid, $sourceId, 'u1', '2026-06-08 12:00:00');
    insertReturnLogin($this->team->id, $this->pid, 'u1', '2026-06-15 12:00:00');

    $filter = new StatsFilterDto(
        projectId: $this->pid, rangeAmount: 30, rangeType: StatsRangeType::DAY,
        rangeStart: CarbonImmutable::parse('2026-06-08'), comparison: '0',
    );

    $row = app(StatsAggregationsService::class)
        ->getAggregatedGroupedData($filter, 'track_source', 'ret_c2', 10, withRetention: true)
        ->firstWhere('name', 'newsletter');

    expect($row)->not->toBeNull()
        ->and((float) $row['ret_c2'])->toBe(100.0)
        ->and($row['ret_c3'])->toBeNull();
});

it('scales the retention checkpoints with the range granularity and length (12 weeks use 7/21/42 days)', function () {
    Carbon::setTestNow(Carbon::parse('2026-06-15 12:00:00'));

    $sourceId = makeSource($this->team->id, 'newsletter');

    // registered 26 days ago, returns after 10 days: retained at W1 (7d),
    // not at W3 (21d); W6 (42d) not observable yet
    insertSourceUser($this->team->id, $this->pid, $sourceId, 'u1', '2026-05-20 12:00:00');
    insertReturnLogin($this->team->id, $this->pid, 'u1', '2026-05-30 12:00:00');

    $filter = new StatsFilterDto(
        projectId: $this->pid, rangeAmount: 12, rangeType: StatsRangeType::WEEK,
        rangeStart: CarbonImmutable::parse('2026-06-15'), comparison: '0',
    );

    $row = app(StatsAggregationsService::class)
        ->getAggregatedGroupedData($filter, 'track_source', 'ret_c1', 10, withRetention: true)
        ->firstWhere('name', 'newsletter');

    expect($row)->not->toBeNull()
        ->and((float) $row['ret_c1'])->toBe(100.0)
        ->and((float) $row['ret_c2'])->toBe(0.0)
        ->and($row['ret_c3'])->toBeNull();
});

it('buckets users without a dimension value into the fallback row with retention values', function () {
    Carbon::setTestNow(Carbon::parse('2026-06-15 12:00:00'));

    // no country on the user, so the location_country grouping falls back to Direct / Unknown
    insertSourceUser($this->team->id, $this->pid, null, 'u1', '2026-05-01 12:00:00');
    insertReturnLogin($this->team->id, $this->pid, 'u1', '2026-05-03 12:00:00');

    $filter = new StatsFilterDto(
        projectId: $this->pid, rangeAmount: 30, rangeType: StatsRangeType::DAY,
        rangeStart: CarbonImmutable::parse('2026-05-01'), comparison: '0',
    );

    $row = app(StatsAggregationsService::class)
        ->getAggregatedGroupedData($filter, 'location_country', 'ret_c1', 10, withRetention: true)
        ->firstWhere('name', 'Direct / Unknown');

    expect($row)->not->toBeNull()
        ->and((float) $row['ret_c1'])->toBe(100.0)
        ->and((float) $row['ret_c2'])->toBe(0.0);
});

it('excludes non-registered user rows from the retention denominators but not from regs', function () {
    Carbon::setTestNow(Carbon::parse('2026-06-15 12:00:00'));

    $sourceId = makeSource($this->team->id, 'newsletter');

    insertSourceUser($this->team->id, $this->pid, $sourceId, 'u1', '2026-05-01 12:00:00');
    insertSourceUser($this->team->id, $this->pid, $sourceId, 'u2', '2026-05-01 12:00:00', registered: false);
    insertReturnLogin($this->team->id, $this->pid, 'u1', '2026-05-03 12:00:00');

    $filter = new StatsFilterDto(
        projectId: $this->pid, rangeAmount: 1, rangeType: StatsRangeType::DAY,
        rangeStart: CarbonImmutable::parse('2026-05-01'), comparison: '0',
    );

    $row = app(StatsAggregationsService::class)
        ->getAggregatedGroupedData($filter, 'track_source', 'ret_c1', 10, withRetention: true)
        ->firstWhere('name', 'newsletter');

    // denominator is only u1: with u2 included the rate would read 50.0
    expect($row)->not->toBeNull()
        ->and((int) $row['reg'])->toBe(2)
        ->and((float) $row['ret_c1'])->toBe(100.0);
});

it('omits the retention columns by default and for projects that do not track registrations', function () {
    Carbon::setTestNow(Carbon::parse('2026-06-15 12:00:00'));

    $sourceId = makeSource($this->team->id, 'newsletter');
    insertSourceVisitors($this->team->id, $this->pid, $sourceId, '2026-05-01 12:00:00', 3);

    $filter = new StatsFilterDto(
        projectId: $this->pid, rangeAmount: 1, rangeType: StatsRangeType::DAY,
        rangeStart: CarbonImmutable::parse('2026-05-01'), comparison: '0',
    );

    $service = app(StatsAggregationsService::class);

    $row = $service->getAggregatedGroupedData($filter, 'track_source', 'visitors', 10)
        ->firstWhere('name', 'newsletter');

    expect($row)->not->toBeNull()
        ->and($row)->not->toHaveKey('ret_c1')
        ->and($row)->not->toHaveKey('nau');

    // visitors-only project: the opt-in is refused even when requested,
    // and the ret_c1 sort falls back instead of ordering by a missing column
    $this->project->update(['usage' => Project::USAGE_VISITORS]);

    $row = $service->getAggregatedGroupedData($filter, 'track_source', 'ret_c1', 10, withRetention: true)
        ->firstWhere('name', 'newsletter');

    expect($row)->not->toBeNull()
        ->and($row)->not->toHaveKey('ret_c1')
        ->and((int) $row['visitors'])->toBe(3);
});

it('serves per-dimension retention through the stats.grouped endpoint sorted by ret_c2 with nulls last', function () {
    Carbon::setTestNow(Carbon::parse('2026-06-15 12:00:00'));

    $sourceA = makeSource($this->team->id, 'google');
    $sourceB = makeSource($this->team->id, 'twitter');
    $sourceC = makeSource($this->team->id, 'fresh');

    insertSourceUser($this->team->id, $this->pid, $sourceA, 'a1', '2026-05-01 12:00:00');
    insertReturnLogin($this->team->id, $this->pid, 'a1', '2026-05-20 12:00:00');

    insertSourceUser($this->team->id, $this->pid, $sourceB, 'b1', '2026-05-01 12:00:00');
    insertReturnLogin($this->team->id, $this->pid, 'b1', '2026-05-03 12:00:00');

    // registered 3 days ago: D7 not observable -> null -> sorted last
    insertSourceUser($this->team->id, $this->pid, $sourceC, 'c1', '2026-06-12 12:00:00');

    $response = $this->actingAs($this->user)->getJson(route('stats.grouped', [
        'project_id' => $this->pid,
        'range_type' => StatsRangeType::DAY->value,
        'range_amount' => 60,
        'range_start' => '2026-06-15',
        'comparison' => '0',
        'stats_type' => 'track_source',
        'stats_sort' => 'ret_c2',
        'metric' => 'retention',
    ]));

    $response->assertOk();

    $names = collect($response->json('data'))->pluck('name');

    expect($names->values()->all())->toBe(['google', 'twitter', 'fresh']);
});

it('respects the active dimension filter in the retention breakdown', function () {
    Carbon::setTestNow(Carbon::parse('2026-06-15 12:00:00'));

    $sourceA = makeSource($this->team->id, 'google');
    $sourceB = makeSource($this->team->id, 'twitter');

    insertSourceUser($this->team->id, $this->pid, $sourceA, 'a1', '2026-05-01 12:00:00');
    insertReturnLogin($this->team->id, $this->pid, 'a1', '2026-05-03 12:00:00');

    insertSourceUser($this->team->id, $this->pid, $sourceB, 'b1', '2026-05-01 12:00:00');

    $response = $this->actingAs($this->user)->getJson(route('stats.grouped', [
        'project_id' => $this->pid,
        'range_type' => StatsRangeType::DAY->value,
        'range_amount' => 60,
        'range_start' => '2026-06-15',
        'comparison' => '0',
        'stats_type' => 'track_source',
        'stats_sort' => 'ret_c1',
        'metric' => 'retention',
        'track_source' => $sourceB,
    ]));

    $response->assertOk();

    $data = collect($response->json('data'));

    expect($data->firstWhere('name', 'google'))->toBeNull()
        ->and((float) $data->firstWhere('name', 'twitter')['ret_c1'])->toBe(0.0);
});

it('keeps the events group working when retention metric and sort are requested', function () {
    Carbon::setTestNow(Carbon::parse('2026-06-15 12:00:00'));

    $response = $this->actingAs($this->user)->getJson(route('stats.grouped', [
        'project_id' => $this->pid,
        'range_type' => StatsRangeType::DAY->value,
        'range_amount' => 7,
        'range_start' => '2026-06-15',
        'comparison' => '0',
        'stats_type' => 'custom_event_name',
        'stats_sort' => 'ret_c2',
        'metric' => 'retention',
    ]));

    $response->assertOk();
});

it('derives the retention checkpoint offsets from range unit and length', function () {
    expect(StatsRangeType::DAY->retentionCheckpointDays(7))->toBe([1, 2, 3])
        ->and(StatsRangeType::DAY->retentionCheckpointDays(30))->toBe([1, 7, 15])
        ->and(StatsRangeType::WEEK->retentionCheckpointDays(12))->toBe([7, 21, 42])
        ->and(StatsRangeType::MONTH->retentionCheckpointDays(6))->toBe([30, 60, 90])
        ->and(StatsRangeType::MONTH->retentionCheckpointDays(12))->toBe([30, 90, 180])
        ->and(StatsRangeType::HOUR->retentionCheckpointDays(24))->toBe([1, 7, 30]);
});

it('computes per-dimension NAU alongside the retention checkpoints when withNau is enabled', function () {
    Carbon::setTestNow(Carbon::parse('2026-06-15 12:00:00'));

    $sourceA = makeSource($this->team->id, 'newsletter');
    $sourceB = makeSource($this->team->id, 'oldtimers');

    // u1: registered and first return inside the range; u2: registration login only
    insertSourceUser($this->team->id, $this->pid, $sourceA, 'u1', '2026-05-20 12:00:00');
    insertReturnLogin($this->team->id, $this->pid, 'u1', '2026-05-25 12:00:00');
    insertSourceUser($this->team->id, $this->pid, $sourceA, 'u2', '2026-05-20 12:00:00');
    insertReturnLogin($this->team->id, $this->pid, 'u2', '2026-05-20 12:00:00', counter: 1);

    // u3: registered BEFORE the range, first return inside it -> counts toward NAU
    // (like the headline) but not toward regs or the retention cohort
    insertSourceUser($this->team->id, $this->pid, $sourceB, 'u3', '2026-04-01 12:00:00');
    insertReturnLogin($this->team->id, $this->pid, 'u3', '2026-06-01 12:00:00');

    $filter = new StatsFilterDto(
        projectId: $this->pid, rangeAmount: 30, rangeType: StatsRangeType::DAY,
        rangeStart: CarbonImmutable::parse('2026-06-15'), comparison: '0',
    );

    $grouped = app(StatsAggregationsService::class)
        ->getAggregatedGroupedData($filter, 'track_source', 'nau', 10, withRetention: true, withNau: true);

    $newsletter = $grouped->firstWhere('name', 'newsletter');
    $oldtimers = $grouped->firstWhere('name', 'oldtimers');

    expect($newsletter)->not->toBeNull()
        ->and((int) $newsletter['reg'])->toBe(2)
        ->and((int) $newsletter['nau'])->toBe(1)
        ->and((float) $newsletter['ret_c1'])->toBe(50.0);

    // a NAU-only row (no regs/visitors/revenue in range) must survive the all-zero reject
    expect($oldtimers)->not->toBeNull()
        ->and((int) $oldtimers['reg'])->toBe(0)
        ->and((int) $oldtimers['nau'])->toBe(1)
        ->and($oldtimers['ret_c1'])->toBeNull();
});

it('serves per-dimension NAU with retention through the stats.grouped endpoint with metric=nau', function () {
    Carbon::setTestNow(Carbon::parse('2026-06-15 12:00:00'));

    $sourceId = makeSource($this->team->id, 'newsletter');
    insertSourceUser($this->team->id, $this->pid, $sourceId, 'u1', '2026-05-20 12:00:00');
    insertReturnLogin($this->team->id, $this->pid, 'u1', '2026-05-25 12:00:00');

    $response = $this->actingAs($this->user)->getJson(route('stats.grouped', [
        'project_id' => $this->pid,
        'range_type' => StatsRangeType::DAY->value,
        'range_amount' => 30,
        'range_start' => '2026-06-15',
        'comparison' => '0',
        'stats_type' => 'track_source',
        'stats_sort' => 'nau',
        'metric' => 'nau',
    ]));

    $response->assertOk();

    $row = collect($response->json('data'))->firstWhere('name', 'newsletter');

    expect($row)->not->toBeNull()
        ->and((int) $row['nau'])->toBe(1)
        ->and((float) $row['ret_c1'])->toBe(100.0);
});

it('caps the grouped breakdown at the requested limit and drops all-zero dimension rows', function () {
    $small = makeSource($this->team->id, 'small');
    $medium = makeSource($this->team->id, 'medium');
    $large = makeSource($this->team->id, 'large');
    makeSource($this->team->id, 'inactive');

    insertSourceVisitors($this->team->id, $this->pid, $small, '2026-03-30 12:00:00', 1);
    insertSourceVisitors($this->team->id, $this->pid, $medium, '2026-03-30 12:00:00', 2);
    insertSourceVisitors($this->team->id, $this->pid, $large, '2026-03-30 12:00:00', 3);

    $filter = new StatsFilterDto(
        projectId: $this->pid, rangeAmount: 1, rangeType: StatsRangeType::DAY,
        rangeStart: CarbonImmutable::parse('2026-03-30'), comparison: '0',
    );

    $service = app(StatsAggregationsService::class);

    $top = $service->getAggregatedGroupedData($filter, 'track_source', 'visitors', 2);

    expect($top->pluck('name')->all())->toBe(['large', 'medium']);

    // 'inactive' has a dimension row but no activity at all, 'Direct / Unknown' stays at zero too
    $all = $service->getAggregatedGroupedData($filter, 'track_source', 'visitors', 10);

    expect($all->pluck('name')->all())->toBe(['large', 'medium', 'small']);
});
