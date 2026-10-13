<?php

use App\DTOs\StatsFilterDto;
use App\Enums\StatsRangeType;
use App\Enums\SubscriptionInterval;
use App\Models\Project;
use App\Models\StatsPayment;
use App\Models\User;
use App\Services\MrrAggregationService;
use App\Services\StatsAggregationsService;
use Carbon\CarbonImmutable;
use Database\Seeders\TestUserSeeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    CarbonImmutable::setTestNow('2026-06-21 12:00:00');
    config(['app.stats_aggregation_enabled' => true]);

    $this->seed(TestUserSeeder::class);
    $this->user = User::where('email', config('app.test.user')['email'])->firstOrFail();
    $this->project = Project::first();
    $this->team = $this->project->team;
    $this->pid = $this->project->id;

    DB::table('stats_payments')->delete();
    DB::table('stats_users')->delete();
    DB::table('stats_aggregations')->delete();

    auth()->setUser($this->user);
    Context::add('team_id', $this->team->id);

    $this->service = resolve(MrrAggregationService::class);
});

afterEach(function (): void {
    CarbonImmutable::setTestNow();
});

function cacheUser(string $id, int $teamId, int $projectId): void
{
    DB::table('stats_users')->insert([
        'id' => $id,
        'team_id' => $teamId,
        'project_id' => $projectId,
        'time' => '2026-01-01 00:00:00',
        'time_utc' => '2026-01-01 00:00:00',
    ]);
}

function cachePayment(string $id, string $userId, int $teamId, int $projectId, float $net, string $time): void
{
    DB::table('stats_payments')->insert([
        'id' => $id,
        'stats_user_id' => $userId,
        'team_id' => $teamId,
        'project_id' => $projectId,
        'gross' => $net,
        'net' => $net,
        'currency' => 'EUR',
        'subscription_interval' => SubscriptionInterval::MONTH->value,
        'counter' => 1,
        'time' => $time,
        'time_utc' => $time,
    ]);
}

function cacheMonthFilter(int $projectId, string $rangeStart, int $rangeAmount): StatsFilterDto
{
    return new StatsFilterDto(
        projectId: $projectId, rangeAmount: $rangeAmount, rangeType: StatsRangeType::MONTH,
        rangeStart: CarbonImmutable::parse($rangeStart), comparison: '0',
    );
}

function mrrCacheRows(): Collection
{
    return DB::table('stats_aggregations')->where('kind', 'mrr')->get();
}

it('persists only closed buckets and never the open bucket', function (): void {
    cacheUser('u-1', $this->team->id, $this->pid);
    // Active only around March (snapshot at end of March), churned by June.
    cachePayment('p-1', 'u-1', $this->team->id, $this->pid, 30, '2026-03-10 10:00:00');

    $series = $this->service->getMrrTimeSeries(cacheMonthFilter($this->pid, '2026-06-21', 6));

    $rows = mrrCacheRows();

    // 6-month range = 5 closed buckets (Jan-May) cached; the open June bucket is not.
    expect($rows)->toHaveCount(5)
        ->and($rows->pluck('time')->map(fn ($t): string => substr($t, 0, 10))->all())
        ->not->toContain('2026-06-01');

    $march = collect($series)->first(fn ($r): bool => str_starts_with($r['date_clear'], '2026-03-01'));
    expect($march['mrr'])->toBe(30.0);
});

it('recomputes the open bucket live on every read without invalidation', function (): void {
    cacheUser('u-old', $this->team->id, $this->pid);
    cachePayment('p-old', 'u-old', $this->team->id, $this->pid, 30, '2026-03-10 10:00:00');

    // Warm the cache (open June bucket currently 0).
    $first = $this->service->getMrrTimeSeries(cacheMonthFilter($this->pid, '2026-06-21', 6));
    expect(collect($first)->first()['mrr'])->toBe(0.0);

    // Raw insert (bypasses the observer, so no invalidation fires) into the OPEN bucket.
    cacheUser('u-new', $this->team->id, $this->pid);
    cachePayment('p-new', 'u-new', $this->team->id, $this->pid, 99, '2026-06-18 10:00:00');

    $second = $this->service->getMrrTimeSeries(cacheMonthFilter($this->pid, '2026-06-21', 6));

    // Open bucket reflects the new subscription immediately (live recompute).
    expect(collect($second)->first()['mrr'])->toBe(99.0);
});

it('serves a closed bucket from cache even when the underlying data changed', function (): void {
    cacheUser('u-1', $this->team->id, $this->pid);
    cachePayment('p-1', 'u-1', $this->team->id, $this->pid, 30, '2026-03-10 10:00:00');

    // Warm the cache: March closed bucket = 30.
    $this->service->getMrrTimeSeries(cacheMonthFilter($this->pid, '2026-06-21', 6));

    // Raw insert into the CLOSED March bucket without firing the observer.
    cacheUser('u-2', $this->team->id, $this->pid);
    cachePayment('p-2', 'u-2', $this->team->id, $this->pid, 70, '2026-03-12 10:00:00');

    $series = $this->service->getMrrTimeSeries(cacheMonthFilter($this->pid, '2026-06-21', 6));
    $march = collect($series)->first(fn ($r): bool => str_starts_with($r['date_clear'], '2026-03-01'));

    // Stale cached value, NOT the recomputed 100 -> proves it is served from cache.
    expect($march['mrr'])->toBe(30.0);
});

it('re-backfills a closed bucket after invalidation', function (): void {
    cacheUser('u-1', $this->team->id, $this->pid);
    cachePayment('p-1', 'u-1', $this->team->id, $this->pid, 30, '2026-03-10 10:00:00');

    $this->service->getMrrTimeSeries(cacheMonthFilter($this->pid, '2026-06-21', 6));

    cacheUser('u-2', $this->team->id, $this->pid);
    cachePayment('p-2', 'u-2', $this->team->id, $this->pid, 70, '2026-03-12 10:00:00');

    // Simulate the observer's invalidation for the new payment.
    $stat = (new StatsPayment)->forceFill([
        'time' => '2026-03-12 10:00:00',
        'team_id' => $this->team->id,
        'project_id' => $this->pid,
    ]);
    resolve(StatsAggregationsService::class)->invalidateByStatsUpdate($stat);

    $series = $this->service->getMrrTimeSeries(cacheMonthFilter($this->pid, '2026-06-21', 6));
    $march = collect($series)->first(fn ($r): bool => str_starts_with($r['date_clear'], '2026-03-01'));

    // Fresh value after invalidation: both March subscriptions counted.
    expect($march['mrr'])->toBe(100.0);
});

it('returns the same cached closed bucket value across different ranges', function (): void {
    cacheUser('u-1', $this->team->id, $this->pid);
    cachePayment('p-1', 'u-1', $this->team->id, $this->pid, 30, '2026-03-10 10:00:00');

    $wide = $this->service->getMrrTimeSeries(cacheMonthFilter($this->pid, '2026-06-21', 6));
    $wideMarch = collect($wide)->first(fn ($r): bool => str_starts_with($r['date_clear'], '2026-03-01'))['mrr'];

    // A different range (ends in the past, no open bucket) hits the same filter_hash.
    $narrow = $this->service->getMrrTimeSeries(cacheMonthFilter($this->pid, '2026-04-21', 3));
    $narrowMarch = collect($narrow)->first(fn ($r): bool => str_starts_with($r['date_clear'], '2026-03-01'))['mrr'];

    expect($narrowMarch)->toBe($wideMarch)->toBe(30.0)
        // Only one cached March row exists (range-independent key).
        ->and(mrrCacheRows()->filter(fn ($r): bool => str_starts_with($r->time, '2026-03-01')))->toHaveCount(1);
});

it('caches the previously-open bucket with its frozen value once it closes', function (): void {
    cacheUser('u-1', $this->team->id, $this->pid);
    // Subscription active in June (the open bucket), lapses afterwards.
    cachePayment('p-1', 'u-1', $this->team->id, $this->pid, 40, '2026-06-10 10:00:00');

    $first = $this->service->getMrrTimeSeries(cacheMonthFilter($this->pid, '2026-06-21', 6));

    // June is the open bucket: live value 40, not persisted.
    expect(collect($first)->first()['mrr'])->toBe(40.0)
        ->and(mrrCacheRows()->filter(fn ($r): bool => str_starts_with($r->time, '2026-06-01')))->toHaveCount(0);

    // Roll the clock forward a month: July becomes open, June closes.
    CarbonImmutable::setTestNow('2026-07-15 12:00:00');

    $second = $this->service->getMrrTimeSeries(cacheMonthFilter($this->pid, '2026-07-15', 6));

    $june = collect($second)->first(fn ($r): bool => str_starts_with($r['date_clear'], '2026-06-01'));

    // June is now a closed bucket, frozen at its real bucket end and cached.
    expect($june['mrr'])->toBe(40.0)
        ->and(mrrCacheRows()->filter(fn ($r): bool => str_starts_with($r->time, '2026-06-01')))->toHaveCount(1);
});

it('matches an uncached full recompute (warm equals cold)', function (): void {
    cacheUser('u-1', $this->team->id, $this->pid);
    cachePayment('p-1', 'u-1', $this->team->id, $this->pid, 30, '2026-03-10 10:00:00');

    $cold = $this->service->getMrrTimeSeries(cacheMonthFilter($this->pid, '2026-06-21', 6));
    $warm = $this->service->getMrrTimeSeries(cacheMonthFilter($this->pid, '2026-06-21', 6));

    expect($warm)->toBe($cold);
});
