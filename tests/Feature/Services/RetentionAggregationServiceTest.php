<?php

use App\DTOs\StatsFilterDto;
use App\Enums\StatsRangeType;
use App\Models\Project;
use App\Models\User;
use App\Services\RetentionAggregationService;
use Carbon\CarbonImmutable;
use Database\Seeders\TestUserSeeder;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    CarbonImmutable::setTestNow('2026-06-24 12:00:00');

    $this->seed(TestUserSeeder::class);
    $this->user = User::where('email', config('app.test.user')['email'])->firstOrFail();
    $this->project = Project::first();

    $this->team = $this->project->team;
    $this->pid = $this->project->id;

    DB::table('stats_logins')->delete();
    DB::table('stats_users')->delete();

    auth()->setUser($this->user);
    Context::add('team_id', $this->team->id);

    $this->service = resolve(RetentionAggregationService::class);
});

afterEach(function (): void {
    CarbonImmutable::setTestNow();
});

function insertRetentionUser(string $id, int $teamId, int $projectId, string $time, bool $registered = true, ?int $sourceId = null): void
{
    DB::table('stats_users')->insert([
        'id' => $id,
        'team_id' => $teamId,
        'project_id' => $projectId,
        'stats_track_source_id' => $sourceId,
        'time' => $time,
        'time_utc' => $time,
        'registered' => $registered,
    ]);
}

function insertRetentionLogin(string $userId, int $teamId, int $projectId, string $time, int $counter): void
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

// 3 weekly buckets: idx0 = w/c 2026-06-08, idx1 = 06-15, idx2 = 06-22.
function retentionWeekFilter(int $projectId, int $trackSource = 0): StatsFilterDto
{
    return new StatsFilterDto(
        projectId: $projectId,
        rangeAmount: 3,
        rangeType: StatsRangeType::WEEK,
        rangeStart: CarbonImmutable::parse('2026-06-24'),
        comparison: '0',
        trackSource: $trackSource,
    );
}

it('builds the triangular cohort matrix, oldest cohort first, with later periods null', function (): void {
    // Cohort idx0: returns in week 1 and week 2.
    insertRetentionUser('a', $this->team->id, $this->pid, '2026-06-09 10:00:00');
    insertRetentionLogin('a', $this->team->id, $this->pid, '2026-06-16 10:00:00', 2);
    insertRetentionLogin('a', $this->team->id, $this->pid, '2026-06-23 10:00:00', 3);

    // Cohort idx1: returns in week 2.
    insertRetentionUser('b', $this->team->id, $this->pid, '2026-06-16 10:00:00');
    insertRetentionLogin('b', $this->team->id, $this->pid, '2026-06-23 10:00:00', 2);

    // Cohort idx2: no observable follow-up bucket in range.
    insertRetentionUser('c', $this->team->id, $this->pid, '2026-06-23 10:00:00');

    $result = $this->service->getRetentionCohorts(retentionWeekFilter($this->pid));

    expect($result['period_count'])->toBe(3)
        ->and($result['cohorts'])->toHaveCount(3);

    // bucket_labels carries every bucket so the view can resolve a cell to its
    // real calendar bucket (cohort idx + period).
    expect($result['bucket_labels'])->toHaveCount(3)
        ->and($result['cohorts'][0]['idx'])->toBe(0);

    // Oldest first.
    expect($result['cohorts'][0]['size'])->toBe(1)
        ->and($result['cohorts'][0]['retention'])->toBe([100.0, 100.0, 100.0])
        ->and($result['cohorts'][1]['retention'])->toBe([100.0, 100.0, null])
        ->and($result['cohorts'][2]['retention'])->toBe([100.0, null, null]);
});

it('shows every bucket of the range, including empty edges, as 0-user rows', function (): void {
    // Only idx1 (w/c 06-15) has a cohort; idx0 and idx2 stay empty.
    insertRetentionUser('b', $this->team->id, $this->pid, '2026-06-16 10:00:00');

    $result = $this->service->getRetentionCohorts(retentionWeekFilter($this->pid));

    // oldest first: idx0 (empty edge), idx1 (the cohort), idx2 (empty edge).
    expect($result['cohorts'])->toHaveCount(3)
        ->and($result['cohorts'][0]['size'])->toBe(0)
        ->and($result['cohorts'][0]['retention'])->toBe([null, null, null])
        ->and($result['cohorts'][1]['size'])->toBe(1)
        ->and($result['cohorts'][2]['size'])->toBe(0);
});

it('returns no cohorts when the whole range has no registrations', function (): void {
    $result = $this->service->getRetentionCohorts(retentionWeekFilter($this->pid));

    expect($result['cohorts'])->toBe([]);
});

it('counts only return logins (counter >= 2), not the registration login', function (): void {
    insertRetentionUser('a', $this->team->id, $this->pid, '2026-06-09 10:00:00');
    insertRetentionUser('b', $this->team->id, $this->pid, '2026-06-09 10:00:00');

    // a only has its registration login in the next week -> not retained.
    insertRetentionLogin('a', $this->team->id, $this->pid, '2026-06-16 10:00:00', 1);
    // b genuinely returns.
    insertRetentionLogin('b', $this->team->id, $this->pid, '2026-06-16 10:00:00', 2);

    $result = $this->service->getRetentionCohorts(retentionWeekFilter($this->pid));

    $oldest = collect($result['cohorts'])->firstWhere('size', 2);

    // period 1: 1 of 2 returned -> 50 %, observed (not null).
    expect($oldest['retention'][0])->toBe(100.0)
        ->and($oldest['retention'][1])->toBe(50.0);
});

it('excludes non-registered users from the cohort', function (): void {
    insertRetentionUser('a', $this->team->id, $this->pid, '2026-06-09 10:00:00', registered: true);
    insertRetentionUser('ghost', $this->team->id, $this->pid, '2026-06-09 10:00:00', registered: false);
    insertRetentionLogin('a', $this->team->id, $this->pid, '2026-06-16 10:00:00', 2);

    $result = $this->service->getRetentionCohorts(retentionWeekFilter($this->pid));

    $oldest = Arr::first($result['cohorts']);

    expect($oldest['size'])->toBe(1)
        ->and($oldest['retention'][1])->toBe(100.0);
});

it('narrows the cohorts by the channel filter', function (): void {
    $paidSource = DB::table('stats_track_sources')->insertGetId(['team_id' => $this->team->id, 'name' => 'google']);
    $organicSource = DB::table('stats_track_sources')->insertGetId(['team_id' => $this->team->id, 'name' => 'newsletter']);

    insertRetentionUser('paid', $this->team->id, $this->pid, '2026-06-09 10:00:00', sourceId: $paidSource);
    insertRetentionUser('organic', $this->team->id, $this->pid, '2026-06-09 10:00:00', sourceId: $organicSource);
    insertRetentionLogin('paid', $this->team->id, $this->pid, '2026-06-16 10:00:00', 2);
    insertRetentionLogin('organic', $this->team->id, $this->pid, '2026-06-16 10:00:00', 2);

    $result = $this->service->getRetentionCohorts(retentionWeekFilter($this->pid, trackSource: $paidSource));

    $oldest = Arr::first($result['cohorts']);

    expect($oldest['size'])->toBe(1);
});
