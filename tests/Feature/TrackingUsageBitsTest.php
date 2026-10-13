<?php

use App\Models\Project;
use App\Models\User;

test('custom events are no longer a tracking usage bit', function (): void {
    expect(Project::getUsages()->keys()->all())
        ->toBe(['visitors', 'users', 'payments', 'subscription']);

    expect(Project::getUsageBitsCount())->toBe(15);
});

test('migration clears the retired events bit from project usage', function (): void {
    $user = User::factory()->withPersonalTeam()->create();
    $team = $user->currentTeam;

    $all = Project::factory()->create(['team_id' => $team->id, 'usage' => 15]);
    $onlyEvents = Project::factory()->create(['team_id' => $team->id, 'usage' => 8]);
    $noEvents = Project::factory()->create(['team_id' => $team->id, 'usage' => 7]);

    $migration = require database_path('migrations/2026_06_11_142845_remove_events_bit_from_projects_usage.php');
    $migration->up();

    expect((int) $all->fresh()->usage)->toBe(7);
    expect((int) $onlyEvents->fresh()->usage)->toBe(0);
    expect((int) $noEvents->fresh()->usage)->toBe(7);
});
