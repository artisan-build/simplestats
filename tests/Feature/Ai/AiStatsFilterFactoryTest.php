<?php

use App\DTOs\StatsFilterDto;
use App\Enums\StatsRangePreset;
use App\Enums\StatsRangeType;
use App\Models\Project;
use App\Models\User;
use App\Services\Ai\AiStatsFilterFactory;
use Carbon\Carbon;
use Database\Seeders\TestUserSeeder;
use Illuminate\Support\Facades\Context;

beforeEach(function (): void {
    $this->seed(TestUserSeeder::class);
    $this->user = User::where('email', config('app.test.user')['email'])->firstOrFail();
    $this->project = Project::first();

    auth()->setUser($this->user);
    Context::add('team_id', $this->project->team_id);

    $this->factory = resolve(AiStatsFilterFactory::class);
});

afterEach(function (): void {
    Carbon::setTestNow();
});

it('builds filters from a preset', function (): void {
    $filters = $this->factory->make('last_7_days', null, null, $this->project->id);

    expect($filters->preset)->toBe(StatsRangePreset::LAST_7_DAYS)
        ->and($filters->rangeType)->toBe(StatsRangeType::DAY)
        ->and($filters->rangeAmount)->toBe(7)
        ->and($filters->projectId)->toBe($this->project->id)
        ->and($filters->comparison)->toBe('0');
});

it('builds last_month filters covering the previous month', function (): void {
    // Explicit team timezone: setTestNow would otherwise pin the instant in UTC
    // while the factory formats in the team's timezone (Europe/Berlin).
    Carbon::setTestNow(Carbon::parse('2026-07-07 10:00:00', 'Europe/Berlin'));

    $filters = $this->factory->make('last_month', null, null, 0);

    expect($filters->rangeType)->toBe(StatsRangeType::DAY)
        ->and($filters->rangeAmount)->toBe(30)
        ->and($filters->rangeStart->format('Y-m-d'))->toBe('2026-06-30');
});

it('builds day buckets from short explicit date ranges', function (): void {
    $filters = $this->factory->make(null, '2026-03-01', '2026-03-10', 0);

    expect($filters->preset)->toBeNull()
        ->and($filters->rangeType)->toBe(StatsRangeType::DAY)
        ->and($filters->rangeAmount)->toBe(10)
        ->and($filters->rangeStart->format('Y-m-d H:i:s'))->toBe('2026-03-10 23:59:59');
});

it('builds week buckets from medium explicit date ranges', function (): void {
    $filters = $this->factory->make(null, '2026-01-01', '2026-06-30', 0);

    expect($filters->rangeType)->toBe(StatsRangeType::WEEK)
        ->and($filters->rangeAmount)->toBeGreaterThanOrEqual(26)
        ->and($filters->rangeAmount)->toBeLessThanOrEqual(27);
});

it('clamps very long ranges to the bucket limit', function (): void {
    $filters = $this->factory->make(null, '2000-01-01', '2026-06-30', 0);

    expect($filters->rangeType)->toBe(StatsRangeType::MONTH)
        ->and($filters->rangeAmount)->toBe(AiStatsFilterFactory::MAX_BUCKETS);
});

it('does not extend an end date of today into the future', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-07-07 10:00:00', 'Europe/Berlin'));

    $filters = $this->factory->make(null, '2026-07-01', '2026-07-07', 0);

    expect($filters->rangeStart->format('Y-m-d H:i:s'))->toBe('2026-07-07 10:00:00');
});

it('falls back to all projects for a foreign project id', function (): void {
    $filters = $this->factory->make('last_7_days', null, null, 999999);

    expect($filters->projectId)->toBe(StatsFilterDto::TRACK_ALL);
});
