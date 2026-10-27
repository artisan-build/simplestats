<?php

use App\Enums\StatsRangePreset;
use App\Models\Project;
use App\Models\User;
use Carbon\CarbonImmutable;

beforeEach(function (): void {
    CarbonImmutable::setTestNow('2026-06-21 12:00:00');

    $this->user = User::factory()->withPersonalTeam()->create();
    $this->project = Project::factory()->create(['team_id' => $this->user->currentTeam->id]);
    $this->actingAs($this->user);
});

afterEach(function (): void {
    CarbonImmutable::setTestNow();
});

test('all time reaches back to the project stats start', function (): void {
    $this->project->update(['stats_starting_at' => '2025-10-01']);

    $range = StatsRangePreset::ALL_TIME->range($this->project->id);

    expect($range['range_type'])->toBe('months')
        ->and((int) $range['range_amount'])->toBe(10);
});

test('all time falls back to the start of last year before the stats start is known', function (): void {
    $this->project->update(['stats_starting_at' => null]);

    $range = StatsRangePreset::ALL_TIME->range($this->project->id);

    expect($range['range_type'])->toBe('months')
        ->and((int) $range['range_amount'])->toBe(19);
});
