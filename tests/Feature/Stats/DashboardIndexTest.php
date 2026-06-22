<?php

use App\Enums\StatsRangePreset;
use App\Models\Project;
use App\Models\User;
use App\Services\StatsService;

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
            ->has('hasCustomEvents')
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

test('all projects view merges tracking usage bits from individual projects', function () {
    // Remove default project created in beforeEach and create projects with specific usage bits
    Project::where('team_id', $this->user->currentTeam->id)->delete();

    Project::factory()->create([
        'team_id' => $this->user->currentTeam->id,
        'usage' => StatsService::TRACKING_USAGE_BITS_VISITORS,
    ]);

    Project::factory()->create([
        'team_id' => $this->user->currentTeam->id,
        'usage' => StatsService::TRACKING_USAGE_BITS_VISITORS,
    ]);

    $this->get(route('dashboard', ['project_id' => 0]))
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Index')
            ->where('teamStats.projects.0.usage', StatsService::TRACKING_USAGE_BITS_VISITORS)
        );
});

test('all projects view includes payment bits when one project has payments', function () {
    Project::where('team_id', $this->user->currentTeam->id)->delete();

    Project::factory()->create([
        'team_id' => $this->user->currentTeam->id,
        'usage' => StatsService::TRACKING_USAGE_BITS_VISITORS,
    ]);

    Project::factory()->create([
        'team_id' => $this->user->currentTeam->id,
        'usage' => StatsService::TRACKING_USAGE_BITS_VISITORS | StatsService::TRACKING_USAGE_BITS_PAYMENTS,
    ]);

    $expectedUsage = StatsService::TRACKING_USAGE_BITS_VISITORS | StatsService::TRACKING_USAGE_BITS_PAYMENTS;

    $this->get(route('dashboard', ['project_id' => 0]))
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Index')
            ->where('teamStats.projects.0.usage', $expectedUsage)
        );
});

test('dashboard exposes the mrr snapshot prop when the project tracks payments', function () {
    Project::where('team_id', $this->user->currentTeam->id)->delete();

    $project = Project::factory()->create([
        'team_id' => $this->user->currentTeam->id,
        'usage' => StatsService::TRACKING_USAGE_BITS_VISITORS
            | StatsService::TRACKING_USAGE_BITS_USERS
            | StatsService::TRACKING_USAGE_BITS_PAYMENTS,
    ]);

    $this->get(route('dashboard', ['project_id' => $project->id]))
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Index')
            ->has('mrr', fn ($mrr) => $mrr
                ->has('mrr')
                ->has('arr')
                ->has('mrr_prev')
            )
        );
});

test('dashboard mrr prop is null when the project has no payments tracking', function () {
    Project::where('team_id', $this->user->currentTeam->id)->delete();

    $project = Project::factory()->create([
        'team_id' => $this->user->currentTeam->id,
        'usage' => StatsService::TRACKING_USAGE_BITS_VISITORS,
    ]);

    $this->get(route('dashboard', ['project_id' => $project->id]))
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Index')
            ->where('mrr', null)
        );
});

test('dashboard exposes the mrr_series prop when the project tracks payments', function () {
    Project::where('team_id', $this->user->currentTeam->id)->delete();

    $project = Project::factory()->create([
        'team_id' => $this->user->currentTeam->id,
        'usage' => StatsService::TRACKING_USAGE_BITS_VISITORS
            | StatsService::TRACKING_USAGE_BITS_USERS
            | StatsService::TRACKING_USAGE_BITS_PAYMENTS,
    ]);

    $this->get(route('dashboard', ['project_id' => $project->id]))
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Index')
            ->has('mrr_series', fn ($series) => $series
                ->each(fn ($item) => $item
                    ->has('date')
                    ->has('date_clear')
                    ->has('mrr')
                )
            )
        );
});

test('dashboard mrr_series prop is null when the project has no payments tracking', function () {
    Project::where('team_id', $this->user->currentTeam->id)->delete();

    $project = Project::factory()->create([
        'team_id' => $this->user->currentTeam->id,
        'usage' => StatsService::TRACKING_USAGE_BITS_VISITORS,
    ]);

    $this->get(route('dashboard', ['project_id' => $project->id]))
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Index')
            ->where('mrr_series', null)
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
