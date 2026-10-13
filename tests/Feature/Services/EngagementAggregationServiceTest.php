<?php

use App\DTOs\StatsFilterDto;
use App\Enums\StatsRangeType;
use App\Models\Project;
use App\Models\User;
use App\Services\EngagementAggregationService;
use Carbon\CarbonImmutable;
use Database\Seeders\TestUserSeeder;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    // Anchor is now-capped, so the trailing windows are measured back from here:
    // last 7d > 2026-06-17 12:00, last 30d > 2026-05-25 12:00, prior 30d back to 2026-04-25 12:00.
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

    $this->service = resolve(EngagementAggregationService::class);
});

afterEach(function (): void {
    CarbonImmutable::setTestNow();
});

function insertEngagementUser(string $id, int $teamId, int $projectId, ?int $sourceId = null): void
{
    DB::table('stats_users')->insert([
        'id' => $id,
        'team_id' => $teamId,
        'project_id' => $projectId,
        'stats_track_source_id' => $sourceId,
        'time' => '2026-01-01 10:00:00',
        'time_utc' => '2026-01-01 10:00:00',
        'registered' => true,
    ]);
}

function insertEngagementLogin(string $userId, int $teamId, int $projectId, string $time, int $counter = 2): void
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

function engagementFilter(int $projectId, int $trackSource = 0): StatsFilterDto
{
    return new StatsFilterDto(
        projectId: $projectId,
        rangeAmount: 1,
        rangeType: StatsRangeType::DAY,
        rangeStart: CarbonImmutable::parse('2026-06-24'),
        comparison: '0',
        trackSource: $trackSource,
    );
}

// The latest bucket (series[0], ordered date_clear DESC) is now-capped, so its
// rolling 30d / 30-60d windows end at "now" - the engagement health as of today.
function currentBucket(EngagementAggregationService $service, StatsFilterDto $filters): array
{
    return $service->getEngagementTimeSeries($filters)[0];
}

it('computes stickiness as avg daily active over monthly active (rolling 30d)', function (): void {
    insertEngagementUser('u1', $this->team->id, $this->pid);
    insertEngagementUser('u2', $this->team->id, $this->pid);
    insertEngagementUser('u3', $this->team->id, $this->pid);

    // u1: three distinct active days within the last 30 days.
    insertEngagementLogin('u1', $this->team->id, $this->pid, '2026-06-20 10:00:00');
    insertEngagementLogin('u1', $this->team->id, $this->pid, '2026-06-21 10:00:00');
    insertEngagementLogin('u1', $this->team->id, $this->pid, '2026-06-22 10:00:00');
    // u2 and u3: one active day each within the last 30 days.
    insertEngagementLogin('u2', $this->team->id, $this->pid, '2026-06-10 10:00:00');
    insertEngagementLogin('u3', $this->team->id, $this->pid, '2026-06-23 10:00:00');

    $bucket = currentBucket($this->service, engagementFilter($this->pid));

    // 5 distinct user-days / 30 = avg DAU; MAU = 3 distinct users.
    expect($bucket['stickiness'])->toBe(round((5 / 30) / 3 * 100, 1));
});

it('computes monthly user churn from the preceding month cohort', function (): void {
    insertEngagementUser('retained', $this->team->id, $this->pid);
    insertEngagementUser('churned', $this->team->id, $this->pid);

    // Both active in the prior 30d window (30-60 days back).
    insertEngagementLogin('retained', $this->team->id, $this->pid, '2026-05-10 10:00:00');
    insertEngagementLogin('churned', $this->team->id, $this->pid, '2026-05-12 10:00:00');

    // Only "retained" comes back in the current 30d window.
    insertEngagementLogin('retained', $this->team->id, $this->pid, '2026-06-20 10:00:00');

    $bucket = currentBucket($this->service, engagementFilter($this->pid));

    // prior cohort = 2 (retained, churned), 1 did not return -> 50 %.
    expect($bucket['user_churn'])->toBe(50.0);
});

it('counts any login including the registration login (counter >= 1)', function (): void {
    // A user whose only login is the registration (counter 1) still counts as active.
    insertEngagementUser('reg_only', $this->team->id, $this->pid);
    insertEngagementLogin('reg_only', $this->team->id, $this->pid, '2026-06-20 10:00:00', counter: 1);

    $bucket = currentBucket($this->service, engagementFilter($this->pid));

    // 1 user-day / 30 over MAU = 1; under the old counter>=2 rule this was null.
    expect($bucket['stickiness'])->toBe(round(1 / 30 * 100, 1));
});

it('narrows the counts by the channel filter', function (): void {
    $paidSource = DB::table('stats_track_sources')->insertGetId(['team_id' => $this->team->id, 'name' => 'google']);
    $organicSource = DB::table('stats_track_sources')->insertGetId(['team_id' => $this->team->id, 'name' => 'newsletter']);

    insertEngagementUser('paid', $this->team->id, $this->pid, sourceId: $paidSource);
    insertEngagementUser('organic', $this->team->id, $this->pid, sourceId: $organicSource);
    insertEngagementLogin('paid', $this->team->id, $this->pid, '2026-06-20 10:00:00');
    insertEngagementLogin('organic', $this->team->id, $this->pid, '2026-06-20 10:00:00');

    $bucket = currentBucket($this->service, engagementFilter($this->pid, trackSource: $paidSource));

    // Only the paid-source user is active: 1 user-day / 30 over MAU = 1.
    expect($bucket['stickiness'])->toBe(round(1 / 30 * 100, 1));
});

it('returns null stickiness and churn when there is no activity', function (): void {
    $bucket = currentBucket($this->service, engagementFilter($this->pid));

    expect($bucket['stickiness'])->toBeNull()
        ->and($bucket['user_churn'])->toBeNull();
});

it('builds a per-bucket engagement series with rolling stickiness and churn', function (): void {
    insertEngagementUser('u1', $this->team->id, $this->pid);
    insertEngagementLogin('u1', $this->team->id, $this->pid, '2026-06-23 10:00:00');

    $filter = new StatsFilterDto(
        projectId: $this->pid,
        rangeAmount: 3,
        rangeType: StatsRangeType::DAY,
        rangeStart: CarbonImmutable::parse('2026-06-24'),
        comparison: '0',
    );

    $series = $this->service->getEngagementTimeSeries($filter);

    expect($series)->not->toBeEmpty()
        ->and($series[0])->toHaveKeys(['date', 'date_clear', 'stickiness', 'user_churn']);

    $activeBucket = collect($series)->firstWhere('stickiness', '>', 0);
    // rolling 30d as of that bucket: 1 user-day / 30 over MAU 1.
    expect($activeBucket['stickiness'])->toBe(round(1 / 30 * 100, 1));
});
