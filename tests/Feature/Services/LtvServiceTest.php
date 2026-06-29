<?php

use App\Builder\StatsFilterConditionsBuilder;
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

function insertLtvUser(string $id, int $teamId, int $projectId, ?int $sourceId = null): void
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

function insertLtvPayment(string $id, string $userId, int $teamId, int $projectId, float $net, ?SubscriptionInterval $interval, string $time): void
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
        'counter' => 1,
        'time' => $time,
        'time_utc' => $time,
    ]);
}

function ltvMonthFilter(int $projectId, string $rangeStart, int $rangeAmount, int $trackSource = 0): StatsFilterDto
{
    return new StatsFilterDto(
        projectId: $projectId, rangeAmount: $rangeAmount, rangeType: StatsRangeType::MONTH,
        rangeStart: CarbonImmutable::parse($rangeStart), comparison: '0',
        trackSource: $trackSource,
    );
}

/**
 * Range length in days, now-capped exactly like getLtv, so a test can rebuild the
 * monthly normalisation factor without depending on the private helper.
 */
function ltvRangeDays(StatsFilterDto $filter): float
{
    $conditions = app(StatsFilterConditionsBuilder::class)->build($filter);

    $rangeEnd = min($conditions['endDateEnd'], CarbonImmutable::now()->format(StatsRangeType::DATE_FORMAT));
    $rangeStart = $conditions['startDateStart'];

    return max(1.0, CarbonImmutable::parse($rangeStart)->diffInDays(CarbonImmutable::parse($rangeEnd)));
}

it('computes LTV as ARPS divided by the monthly gross churn rate', function () {
    insertLtvUser('u-stable', $this->team->id, $this->pid);
    insertLtvUser('u-expand', $this->team->id, $this->pid);
    insertLtvUser('u-churn', $this->team->id, $this->pid);

    insertLtvPayment('s-1', 'u-stable', $this->team->id, $this->pid, 100, SubscriptionInterval::MONTH, '2026-04-15 10:00:00');
    insertLtvPayment('s-2', 'u-stable', $this->team->id, $this->pid, 100, SubscriptionInterval::MONTH, '2026-05-15 10:00:00');
    insertLtvPayment('s-3', 'u-stable', $this->team->id, $this->pid, 100, SubscriptionInterval::MONTH, '2026-06-15 10:00:00');

    insertLtvPayment('e-1', 'u-expand', $this->team->id, $this->pid, 50, SubscriptionInterval::MONTH, '2026-04-15 10:00:00');
    insertLtvPayment('e-2', 'u-expand', $this->team->id, $this->pid, 50, SubscriptionInterval::MONTH, '2026-05-15 10:00:00');
    insertLtvPayment('e-3', 'u-expand', $this->team->id, $this->pid, 80, SubscriptionInterval::MONTH, '2026-06-15 10:00:00');

    insertLtvPayment('c-1', 'u-churn', $this->team->id, $this->pid, 40, SubscriptionInterval::MONTH, '2026-04-15 10:00:00');

    $filter = ltvMonthFilter($this->pid, '2026-06-21', 2);

    // Hand-derived from the data over the May+June buckets:
    //   current (June) MRR = stable 100 + expand 80 = 180, subscribers = 2 -> ARPS 90
    //   period gross churn = (contraction 0 + churn 40) / starting MRR 190 (April total)
    $arps = 90.0;
    $periodGrossChurn = 40 / 190;
    $monthlyChurn = $periodGrossChurn * (30.44 / ltvRangeDays($filter));
    $expected = round($arps / $monthlyChurn, 2);

    expect($this->service->getLtv($filter))->toBe($expected);
});

it('returns null when there are no subscribers', function () {
    expect($this->service->getLtv(ltvMonthFilter($this->pid, '2026-06-21', 2)))->toBeNull();
});

it('returns null when there is no churn to estimate a lifetime from', function () {
    insertLtvUser('u-stable', $this->team->id, $this->pid);
    insertLtvPayment('s-1', 'u-stable', $this->team->id, $this->pid, 100, SubscriptionInterval::MONTH, '2026-04-15 10:00:00');
    insertLtvPayment('s-2', 'u-stable', $this->team->id, $this->pid, 100, SubscriptionInterval::MONTH, '2026-05-15 10:00:00');
    insertLtvPayment('s-3', 'u-stable', $this->team->id, $this->pid, 100, SubscriptionInterval::MONTH, '2026-06-15 10:00:00');

    expect($this->service->getLtv(ltvMonthFilter($this->pid, '2026-06-21', 2)))->toBeNull();
});

it('returns null when the range has no starting mrr', function () {
    insertLtvUser('u-new', $this->team->id, $this->pid);
    insertLtvPayment('n-1', 'u-new', $this->team->id, $this->pid, 100, SubscriptionInterval::MONTH, '2026-06-15 10:00:00');

    // Single subscriber, first payment inside the range -> starting MRR is 0.
    expect($this->service->getLtv(ltvMonthFilter($this->pid, '2026-06-21', 2)))->toBeNull();
});

it('exposes ARPS, monthly churn and an LTV matching getLtv', function () {
    insertLtvUser('u-stable', $this->team->id, $this->pid);
    insertLtvUser('u-churn', $this->team->id, $this->pid);

    insertLtvPayment('s-1', 'u-stable', $this->team->id, $this->pid, 100, SubscriptionInterval::MONTH, '2026-04-15 10:00:00');
    insertLtvPayment('s-2', 'u-stable', $this->team->id, $this->pid, 100, SubscriptionInterval::MONTH, '2026-05-15 10:00:00');
    insertLtvPayment('s-3', 'u-stable', $this->team->id, $this->pid, 100, SubscriptionInterval::MONTH, '2026-06-15 10:00:00');
    insertLtvPayment('c-1', 'u-churn', $this->team->id, $this->pid, 40, SubscriptionInterval::MONTH, '2026-04-15 10:00:00');

    $filter = ltvMonthFilter($this->pid, '2026-06-21', 2);
    $economics = $this->service->getSubscriptionEconomics($filter);

    // Current (June) MRR = 100, subscribers = 1 -> ARPS 100.
    $periodGrossChurn = 40 / 140;
    $monthlyChurn = $periodGrossChurn * (30.44 / ltvRangeDays($filter));

    expect($economics['arps'])->toBe(100.0)
        ->and($economics['monthly_churn'])->toBe($monthlyChurn)
        ->and($economics['ltv'])->toBe(round(100.0 / $monthlyChurn, 2))
        ->and($economics['ltv'])->toBe($this->service->getLtv($filter));
});

it('exposes a valid ARPS but a null LTV when there is no churn', function () {
    insertLtvUser('u-stable', $this->team->id, $this->pid);
    insertLtvPayment('s-1', 'u-stable', $this->team->id, $this->pid, 100, SubscriptionInterval::MONTH, '2026-04-15 10:00:00');
    insertLtvPayment('s-2', 'u-stable', $this->team->id, $this->pid, 100, SubscriptionInterval::MONTH, '2026-05-15 10:00:00');
    insertLtvPayment('s-3', 'u-stable', $this->team->id, $this->pid, 100, SubscriptionInterval::MONTH, '2026-06-15 10:00:00');

    $economics = $this->service->getSubscriptionEconomics(ltvMonthFilter($this->pid, '2026-06-21', 2));

    expect($economics['arps'])->toBe(100.0)
        ->and($economics['monthly_churn'])->toBeNull()
        ->and($economics['ltv'])->toBeNull();
});

it('returns null economics when there are no subscribers', function () {
    expect($this->service->getSubscriptionEconomics(ltvMonthFilter($this->pid, '2026-06-21', 2)))->toBeNull();
});

it('attributes LTV per channel via the source filter', function () {
    $sourceId = DB::table('stats_track_sources')->insertGetId([
        'team_id' => $this->team->id,
        'name' => 'newsletter',
        'created_at' => '2026-06-01 12:00:00',
        'updated_at' => '2026-06-01 12:00:00',
    ]);

    insertLtvUser('u-nl-stable', $this->team->id, $this->pid, $sourceId);
    insertLtvUser('u-nl-churn', $this->team->id, $this->pid, $sourceId);
    insertLtvUser('u-direct', $this->team->id, $this->pid, null);

    // One newsletter subscriber renews through June, the other lapses after April,
    // so the newsletter segment carries a higher churn (lower LTV) than the blend.
    insertLtvPayment('nls-1', 'u-nl-stable', $this->team->id, $this->pid, 100, SubscriptionInterval::MONTH, '2026-04-15 10:00:00');
    insertLtvPayment('nls-2', 'u-nl-stable', $this->team->id, $this->pid, 100, SubscriptionInterval::MONTH, '2026-05-15 10:00:00');
    insertLtvPayment('nls-3', 'u-nl-stable', $this->team->id, $this->pid, 100, SubscriptionInterval::MONTH, '2026-06-15 10:00:00');

    insertLtvPayment('nlc-1', 'u-nl-churn', $this->team->id, $this->pid, 100, SubscriptionInterval::MONTH, '2026-04-15 10:00:00');

    insertLtvPayment('d-1', 'u-direct', $this->team->id, $this->pid, 100, SubscriptionInterval::MONTH, '2026-04-15 10:00:00');
    insertLtvPayment('d-2', 'u-direct', $this->team->id, $this->pid, 100, SubscriptionInterval::MONTH, '2026-05-15 10:00:00');
    insertLtvPayment('d-3', 'u-direct', $this->team->id, $this->pid, 100, SubscriptionInterval::MONTH, '2026-06-15 10:00:00');

    $unfiltered = $this->service->getLtv(ltvMonthFilter($this->pid, '2026-06-21', 2));
    $newsletterOnly = $this->service->getLtv(ltvMonthFilter($this->pid, '2026-06-21', 2, $sourceId));

    expect($unfiltered)->not->toBeNull()
        ->and($newsletterOnly)->not->toBeNull()
        ->and($newsletterOnly)->not->toBe($unfiltered);
});
