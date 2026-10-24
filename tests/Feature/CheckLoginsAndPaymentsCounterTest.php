<?php

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;

// Raw inserts so the test controls the stored counter, Eloquent would compute it on create.
function insertCounterStatsUser(Project $project, int $id): void
{
    DB::table('stats_users')->insert([
        'id' => $id,
        'team_id' => $project->team_id,
        'project_id' => $project->id,
        'time' => '2026-01-01 00:00:00',
    ]);
}

function insertCounterPayment(Project $project, int $id, int $statsUserId, string $time, int $counter): void
{
    DB::table('stats_payments')->insert([
        'id' => $id,
        'stats_user_id' => $statsUserId,
        'team_id' => $project->team_id,
        'project_id' => $project->id,
        'gross' => 100,
        'net' => 80,
        'currency' => 'EUR',
        'time' => $time,
        'time_utc' => $time,
        'counter' => $counter,
    ]);
}

function storedPaymentCounters(Project $project): array
{
    return DB::table('stats_payments')->where('project_id', $project->id)->orderBy('time')->orderBy('id')->pluck('counter', 'id')->all();
}

function insertCounterAggregation(Project $project, string $time, string $kind = 'period'): void
{
    static $hash = 0;
    $hash++;

    DB::table('stats_aggregations')->insert([
        'team_id' => $project->team_id,
        'project_id' => $project->id,
        'filter_hash' => md5((string) $hash),
        'filter' => '[]',
        'type' => 'months',
        'time' => $time,
        'kind' => $kind,
        'data' => json_encode(['pd_pu' => 1]),
    ]);
}

function remainingCounterAggregations(Project $project): array
{
    return DB::table('stats_aggregations')
        ->where('project_id', $project->id)
        ->orderBy('time')
        ->get(['time', 'kind'])
        ->map(fn (object $row): string => substr((string) $row->time, 0, 10).' '.$row->kind)
        ->all();
}

beforeEach(function (): void {
    $team = User::factory()->withPersonalTeam()->create()->currentTeam;
    $this->project = Project::factory()->create(['team_id' => $team->id]);
    $this->otherProject = Project::factory()->create(['team_id' => $team->id]);
});

it('ranks payments per project when payment ids repeat across projects', function (): void {
    insertCounterStatsUser($this->project, 1);
    insertCounterPayment($this->project, 10, 1, '2026-01-01 10:00:00', 1);
    insertCounterPayment($this->project, 11, 1, '2026-02-01 10:00:00', 1);
    insertCounterPayment($this->project, 12, 1, '2026-03-01 10:00:00', 1);

    insertCounterStatsUser($this->otherProject, 1);
    insertCounterPayment($this->otherProject, 9, 1, '2026-01-01 10:00:00', 1);
    insertCounterPayment($this->otherProject, 10, 1, '2026-02-01 10:00:00', 1);
    insertCounterPayment($this->otherProject, 11, 1, '2026-03-01 10:00:00', 1);
    insertCounterPayment($this->otherProject, 12, 1, '2026-04-01 10:00:00', 1);

    $this->artisan('app:check-logins-and-payments-counter')->assertSuccessful();

    expect(storedPaymentCounters($this->project))->toBe([10 => 1, 11 => 2, 12 => 3])
        ->and(storedPaymentCounters($this->otherProject))->toBe([9 => 1, 10 => 2, 11 => 3, 12 => 4]);
});

it('breaks ties on the same payment time by id', function (): void {
    insertCounterStatsUser($this->project, 1);
    insertCounterPayment($this->project, 21, 1, '2026-01-01 10:00:00', 1);
    insertCounterPayment($this->project, 20, 1, '2026-01-01 10:00:00', 1);

    $this->artisan('app:check-logins-and-payments-counter')->assertSuccessful();

    expect(storedPaymentCounters($this->project))->toBe([20 => 1, 21 => 2]);
});

it('drops cached periods from the month of the earliest paying user change on', function (): void {
    insertCounterStatsUser($this->project, 1);
    insertCounterPayment($this->project, 1, 1, '2026-03-10 10:00:00', 1);
    insertCounterPayment($this->project, 2, 1, '2026-02-10 10:00:00', 2);
    insertCounterAggregation($this->project, '2026-01-01 00:00:00');
    insertCounterAggregation($this->project, '2026-02-01 00:00:00');
    insertCounterAggregation($this->project, '2026-03-01 00:00:00');
    insertCounterAggregation($this->project, '2026-03-01 00:00:00', 'mrr');

    $this->artisan('app:check-logins-and-payments-counter')->assertSuccessful();

    expect(remainingCounterAggregations($this->project))->toBe(['2026-01-01 period', '2026-03-01 mrr']);
});

it('keeps the cache when no row enters or leaves the paying user count', function (): void {
    insertCounterStatsUser($this->project, 1);
    insertCounterPayment($this->project, 1, 1, '2026-01-10 10:00:00', 1);
    insertCounterPayment($this->project, 2, 1, '2026-02-10 10:00:00', 3);
    insertCounterPayment($this->project, 3, 1, '2026-03-10 10:00:00', 2);
    insertCounterAggregation($this->project, '2026-02-01 00:00:00');

    $this->artisan('app:check-logins-and-payments-counter')->assertSuccessful();

    expect(storedPaymentCounters($this->project))->toBe([1 => 1, 2 => 2, 3 => 3])
        ->and(remainingCounterAggregations($this->project))->toBe(['2026-02-01 period']);
});

it('drops cached periods when a login moves onto or off the new active user counter', function (): void {
    insertCounterStatsUser($this->project, 1);
    DB::table('stats_logins')->insert([
        ['stats_user_id' => 1, 'team_id' => $this->project->team_id, 'project_id' => $this->project->id, 'time' => '2026-01-10 10:00:00', 'time_utc' => '2026-01-10 10:00:00', 'counter' => 1],
        ['stats_user_id' => 1, 'team_id' => $this->project->team_id, 'project_id' => $this->project->id, 'time' => '2026-03-10 10:00:00', 'time_utc' => '2026-03-10 10:00:00', 'counter' => 2],
        ['stats_user_id' => 1, 'team_id' => $this->project->team_id, 'project_id' => $this->project->id, 'time' => '2026-02-10 10:00:00', 'time_utc' => '2026-02-10 10:00:00', 'counter' => 3],
    ]);
    insertCounterAggregation($this->project, '2026-01-01 00:00:00');
    insertCounterAggregation($this->project, '2026-02-01 00:00:00');

    $this->artisan('app:check-logins-and-payments-counter')->assertSuccessful();

    expect(remainingCounterAggregations($this->project))->toBe(['2026-01-01 period']);
});
