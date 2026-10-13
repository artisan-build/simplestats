<?php

use App\DTOs\StatsFilterDto;
use App\Enums\StatsRangeType;
use App\Enums\SubscriptionInterval;
use App\Models\Project;
use App\Models\User;
use App\Services\MrrAggregationService;
use Carbon\CarbonImmutable;
use Database\Seeders\TestUserSeeder;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
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

    $this->service = resolve(MrrAggregationService::class);
});

afterEach(function (): void {
    CarbonImmutable::setTestNow();
});

function mbpInsertUser(string $id, int $teamId, int $projectId, ?int $sourceId = null): void
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

function mbpInsertPayment(string $id, string $userId, int $teamId, int $projectId, float $net, ?SubscriptionInterval $interval, ?string $plan, string $time): void
{
    DB::table('stats_payments')->insert([
        'id' => $id,
        'stats_user_id' => $userId,
        'team_id' => $teamId,
        'project_id' => $projectId,
        'gross' => $net,
        'net' => $net,
        'currency' => 'EUR',
        'subscription_interval' => $interval?->value,
        'subscription_plan' => $plan,
        'counter' => 1,
        'time' => $time,
        'time_utc' => $time,
    ]);
}

function mbpFilter(int $projectId, string $rangeStart, int $trackSource = 0): StatsFilterDto
{
    return new StatsFilterDto(
        projectId: $projectId, rangeAmount: 1, rangeType: StatsRangeType::DAY,
        rangeStart: CarbonImmutable::parse($rangeStart), comparison: '0',
        trackSource: $trackSource,
    );
}

it('groups active MRR and subscriber count by plan, ordered by MRR desc', function (): void {
    mbpInsertUser('u-basic-1', $this->team->id, $this->pid);
    mbpInsertUser('u-basic-2', $this->team->id, $this->pid);
    mbpInsertUser('u-pro', $this->team->id, $this->pid);

    mbpInsertPayment('p-b1', 'u-basic-1', $this->team->id, $this->pid, 50, SubscriptionInterval::MONTH, 'Basic', '2026-06-15 10:00:00');
    mbpInsertPayment('p-b2', 'u-basic-2', $this->team->id, $this->pid, 50, SubscriptionInterval::MONTH, 'Basic', '2026-06-15 10:00:00');
    mbpInsertPayment('p-pro', 'u-pro', $this->team->id, $this->pid, 150, SubscriptionInterval::MONTH, 'Pro', '2026-06-15 10:00:00');

    $rows = $this->service->getMrrByPlan(mbpFilter($this->pid, '2026-06-21'));

    expect($rows)->toHaveCount(2)
        ->and($rows[0])->toBe(['plan' => 'Pro', 'subscribers' => 1, 'mrr' => 150.0])
        ->and($rows[1])->toBe(['plan' => 'Basic', 'subscribers' => 2, 'mrr' => 100.0]);
});

it('normalises a yearly plan to net/12', function (): void {
    mbpInsertUser('u-year', $this->team->id, $this->pid);
    mbpInsertPayment('p-year', 'u-year', $this->team->id, $this->pid, 1200, SubscriptionInterval::YEAR, 'Annual', '2026-06-15 10:00:00');

    $rows = $this->service->getMrrByPlan(mbpFilter($this->pid, '2026-06-21'));

    expect($rows)->toHaveCount(1)
        ->and($rows[0])->toBe(['plan' => 'Annual', 'subscribers' => 1, 'mrr' => 100.0]);
});

it('narrows the per-plan breakdown by channel filter', function (): void {
    $sourceId = DB::table('stats_track_sources')->insertGetId(['team_id' => $this->team->id, 'name' => 'google']);

    mbpInsertUser('u-google', $this->team->id, $this->pid, $sourceId);
    mbpInsertUser('u-direct', $this->team->id, $this->pid);
    mbpInsertPayment('p-google', 'u-google', $this->team->id, $this->pid, 80, SubscriptionInterval::MONTH, 'Pro', '2026-06-15 10:00:00');
    mbpInsertPayment('p-direct', 'u-direct', $this->team->id, $this->pid, 40, SubscriptionInterval::MONTH, 'Pro', '2026-06-15 10:00:00');

    $rows = $this->service->getMrrByPlan(mbpFilter($this->pid, '2026-06-21', trackSource: $sourceId));

    expect($rows)->toHaveCount(1)
        ->and($rows[0])->toBe(['plan' => 'Pro', 'subscribers' => 1, 'mrr' => 80.0]);
});

it('returns an empty list when no subscription is active in the window', function (): void {
    mbpInsertUser('u-old', $this->team->id, $this->pid);
    // Last payment is older than a month + grace before the snapshot, so it churned out.
    mbpInsertPayment('p-old', 'u-old', $this->team->id, $this->pid, 50, SubscriptionInterval::MONTH, 'Basic', '2026-04-01 10:00:00');

    $rows = $this->service->getMrrByPlan(mbpFilter($this->pid, '2026-06-21'));

    expect($rows)->toBe([]);
});
