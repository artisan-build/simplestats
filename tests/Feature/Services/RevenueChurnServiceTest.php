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

function insertRcUser(string $id, int $teamId, int $projectId, ?int $sourceId = null): void
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

function insertRcPayment(string $id, string $userId, int $teamId, int $projectId, float $net, ?PaymentInterval $interval, string $time): void
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

function rcMonthFilter(int $projectId, string $rangeStart, int $rangeAmount, int $trackSource = 0): StatsFilterDto
{
    return new StatsFilterDto(
        projectId: $projectId, rangeAmount: $rangeAmount, rangeType: StatsRangeType::MONTH,
        rangeStart: CarbonImmutable::parse($rangeStart), comparison: '0',
        trackSource: $trackSource,
    );
}

it('derives gross and net churn rates from the movement breakdown per bucket', function () {
    insertRcUser('u-stable', $this->team->id, $this->pid);
    insertRcUser('u-expand', $this->team->id, $this->pid);
    insertRcUser('u-churn', $this->team->id, $this->pid);

    insertRcPayment('s-1', 'u-stable', $this->team->id, $this->pid, 100, PaymentInterval::MONTH, '2026-04-15 10:00:00');
    insertRcPayment('s-2', 'u-stable', $this->team->id, $this->pid, 100, PaymentInterval::MONTH, '2026-05-15 10:00:00');
    insertRcPayment('s-3', 'u-stable', $this->team->id, $this->pid, 100, PaymentInterval::MONTH, '2026-06-15 10:00:00');

    insertRcPayment('e-1', 'u-expand', $this->team->id, $this->pid, 50, PaymentInterval::MONTH, '2026-04-15 10:00:00');
    insertRcPayment('e-2', 'u-expand', $this->team->id, $this->pid, 50, PaymentInterval::MONTH, '2026-05-15 10:00:00');
    insertRcPayment('e-3', 'u-expand', $this->team->id, $this->pid, 80, PaymentInterval::MONTH, '2026-06-15 10:00:00');

    insertRcPayment('c-1', 'u-churn', $this->team->id, $this->pid, 40, PaymentInterval::MONTH, '2026-04-15 10:00:00');

    // Buckets newest first: June (start 150, expansion 30), May (start 190, churn 40).
    $churn = $this->service->getRevenueChurn(rcMonthFilter($this->pid, '2026-06-21', 2));

    expect($churn)->toHaveCount(2);

    $june = $churn[0];
    $may = $churn[1];

    expect($june['mrr'])->toBe(180.0)
        ->and($june['gross_churn_rate'])->toBe(0.0)
        ->and($june['net_churn_rate'])->toBe(-20.0)
        ->and($may['mrr'])->toBe(150.0)
        ->and($may['gross_churn_rate'])->toBe(21.1)
        ->and($may['net_churn_rate'])->toBe(21.1);
});

it('returns null rates for buckets with no starting mrr', function () {
    insertRcUser('u-1', $this->team->id, $this->pid);
    insertRcPayment('p-1', 'u-1', $this->team->id, $this->pid, 50, PaymentInterval::MONTH, '2026-06-15 10:00:00');

    $churn = $this->service->getRevenueChurn(rcMonthFilter($this->pid, '2026-06-21', 2));

    expect($churn[0]['gross_churn_rate'])->toBeNull()
        ->and($churn[0]['net_churn_rate'])->toBeNull();
});

it('attributes revenue churn per channel via the source filter', function () {
    $sourceId = DB::table('stats_track_sources')->insertGetId([
        'team_id' => $this->team->id,
        'name' => 'newsletter',
        'created_at' => '2026-06-01 12:00:00',
        'updated_at' => '2026-06-01 12:00:00',
    ]);

    insertRcUser('u-newsletter', $this->team->id, $this->pid, $sourceId);
    insertRcUser('u-direct', $this->team->id, $this->pid, null);

    // Newsletter user lapses after April; direct user renews through June.
    insertRcPayment('n-1', 'u-newsletter', $this->team->id, $this->pid, 100, PaymentInterval::MONTH, '2026-04-15 10:00:00');
    insertRcPayment('d-1', 'u-direct', $this->team->id, $this->pid, 100, PaymentInterval::MONTH, '2026-04-15 10:00:00');
    insertRcPayment('d-2', 'u-direct', $this->team->id, $this->pid, 100, PaymentInterval::MONTH, '2026-05-15 10:00:00');
    insertRcPayment('d-3', 'u-direct', $this->team->id, $this->pid, 100, PaymentInterval::MONTH, '2026-06-15 10:00:00');

    // May bucket (start = April): unfiltered start 200, churn 100 -> 50%;
    // newsletter-only start 100, churn 100 -> 100%.
    $unfiltered = $this->service->getRevenueChurn(rcMonthFilter($this->pid, '2026-06-21', 2));
    $filtered = $this->service->getRevenueChurn(rcMonthFilter($this->pid, '2026-06-21', 2, $sourceId));

    expect($unfiltered[1]['gross_churn_rate'])->toBe(50.0)
        ->and($filtered[1]['gross_churn_rate'])->toBe(100.0);
});

it('returns null rates when there are no recurring payments', function () {
    $churn = $this->service->getRevenueChurn(rcMonthFilter($this->pid, '2026-06-21', 2));

    expect($churn)->toHaveCount(2)
        ->and($churn[0]['gross_churn_rate'])->toBeNull()
        ->and($churn[0]['net_churn_rate'])->toBeNull()
        ->and($churn[0]['mrr'])->toBe(0.0);
});
