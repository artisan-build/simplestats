<?php

use App\Enums\StatsRangePreset;
use App\Models\Project;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->user->currentTeam->update(['currency' => 'USD']);
    Project::factory()->create(['team_id' => $this->user->currentTeam->id]);
    $this->actingAs($this->user);
});

test('dashboard route returns 200', function () {
    $this->get(route('dashboard'))->assertStatus(200);
});

test('dashboard renders Dashboard/Index with expected props', function () {
    $this->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Index')
            ->has('teams')
            ->has('teamStats')
            ->has('presets')
            ->has('allPresets')
            ->has('hiddenPresets')
            ->has('customPresets')
            ->has('filters')
            ->has('statsGroups')
            ->has('statsGroupNames')
            ->has('topReferers')
            ->has('rangeTypes')
            ->has('rangeCurrent')
            ->has('freePlanWarning')
            ->has('readDocsWarning')
            ->has('subscriptionPlanAlarm')
            ->has('trackingUsageBits')
            ->has('comparisonTypes')
        );
});

test('dashboard persists preset selection in user settings', function () {
    $this->get(route('dashboard', ['preset' => StatsRangePreset::LAST_7_DAYS->value]))
        ->assertStatus(200);

    $this->user->refresh();

    $teamId = $this->user->current_team_id;
    expect($this->user->settings['statsFilters'][$teamId] ?? null)->not->toBeNull();
    expect($this->user->settings['statsFilters'][$teamId]['preset'] ?? null)
        ->toBe(StatsRangePreset::LAST_7_DAYS->value);
});
