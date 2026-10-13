<?php

use App\Models\Project;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Database\Seeders\TestUserSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    $this->seed(TestUserSeeder::class);
    $this->project = Project::first();
    $this->team = $this->project->team;

    Carbon::setTestNow(CarbonImmutable::parse('2026-06-15 12:00:00'));
});

afterEach(function (): void {
    Carbon::setTestNow();
});

// Raw insert to bypass the team scope and set filter/selected_at exactly.
function insertAggregation(int $teamId, int $projectId, ?string $filterJson, string $selectedAt): void
{
    static $counter = 0;
    $counter++;

    DB::table('stats_aggregations')->insert([
        'team_id' => $teamId,
        'project_id' => $projectId,
        'filter_hash' => substr(md5((string) $counter), 0, 32),
        'filter' => $filterJson,
        'type' => 'days',
        'time' => '2026-06-01 00:00:00',
        'data' => json_encode(['pd_visitor' => 1]),
        'selected_at' => $selectedAt,
    ]);
}

function remainingAggregations(): int
{
    return DB::table('stats_aggregations')->count();
}

it('keeps unfiltered aggregations within the 400 day window', function (): void {
    insertAggregation($this->team->id, $this->project->id, '[]', now()->subDays(399)->toDateTimeString());

    $this->artisan('app:clear-old-stats-aggregations')->assertSuccessful();

    expect(remainingAggregations())->toBe(1);
});

it('deletes unfiltered aggregations older than 400 days', function (): void {
    insertAggregation($this->team->id, $this->project->id, '[]', now()->subDays(401)->toDateTimeString());

    $this->artisan('app:clear-old-stats-aggregations')->assertSuccessful();

    expect(remainingAggregations())->toBe(0);
});

it('keeps filtered aggregations within the 40 day window', function (): void {
    insertAggregation($this->team->id, $this->project->id, '{"track_source":1}', now()->subDays(39)->toDateTimeString());

    $this->artisan('app:clear-old-stats-aggregations')->assertSuccessful();

    expect(remainingAggregations())->toBe(1);
});

it('deletes filtered aggregations older than 40 days but younger than 400', function (): void {
    insertAggregation($this->team->id, $this->project->id, '{"track_source":1}', now()->subDays(41)->toDateTimeString());

    $this->artisan('app:clear-old-stats-aggregations')->assertSuccessful();

    expect(remainingAggregations())->toBe(0);
});

it('keeps legacy null-filter aggregations on the long window', function (): void {
    insertAggregation($this->team->id, $this->project->id, null, now()->subDays(41)->toDateTimeString());
    insertAggregation($this->team->id, $this->project->id, null, now()->subDays(401)->toDateTimeString());

    $this->artisan('app:clear-old-stats-aggregations')->assertSuccessful();

    expect(remainingAggregations())->toBe(1);
});
