<?php

use App\Enums\StatsRangePreset;
use App\Models\Project;
use App\Models\StatsPayment;
use App\Models\StatsUser;
use App\Models\User;
use App\Services\StatsService;

beforeEach(function () {
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->user->currentTeam->update(['currency' => 'USD', 'timezone' => 'UTC']);
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

test('dashboard exposes the mrr_series prop when the project has recurring payments', function () {
    Project::where('team_id', $this->user->currentTeam->id)->delete();

    $project = Project::factory()->create([
        'team_id' => $this->user->currentTeam->id,
        'usage' => StatsService::TRACKING_USAGE_BITS_VISITORS
            | StatsService::TRACKING_USAGE_BITS_USERS
            | StatsService::TRACKING_USAGE_BITS_PAYMENTS
            | StatsService::TRACKING_USAGE_BITS_SUBSCRIPTION,
    ]);

    $statsUser = StatsUser::factory()->create([
        'team_id' => $this->user->currentTeam->id,
        'project_id' => $project->id,
    ]);

    StatsPayment::factory()->subscriptionMonthly()->create([
        'team_id' => $this->user->currentTeam->id,
        'project_id' => $project->id,
        'stats_user_id' => $statsUser->id,
    ]);

    $this->get(route('dashboard', ['project_id' => $project->id]))
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Index')
            ->has('mrr_series', fn ($series) => $series
                ->each(fn ($item) => $item
                    ->has('date')
                    ->has('date_clear')
                    ->has('mrr')
                    ->has('subscribers')
                )
            )
        );
});

test('dashboard exposes mrr_series_prev when a comparison is active', function () {
    Project::where('team_id', $this->user->currentTeam->id)->delete();

    $project = Project::factory()->create([
        'team_id' => $this->user->currentTeam->id,
        'usage' => StatsService::TRACKING_USAGE_BITS_VISITORS
            | StatsService::TRACKING_USAGE_BITS_USERS
            | StatsService::TRACKING_USAGE_BITS_PAYMENTS
            | StatsService::TRACKING_USAGE_BITS_SUBSCRIPTION,
    ]);

    $statsUser = StatsUser::factory()->create([
        'team_id' => $this->user->currentTeam->id,
        'project_id' => $project->id,
    ]);

    StatsPayment::factory()->subscriptionMonthly()->create([
        'team_id' => $this->user->currentTeam->id,
        'project_id' => $project->id,
        'stats_user_id' => $statsUser->id,
    ]);

    $this->get(route('dashboard', ['project_id' => $project->id, 'comparison' => 'period']))
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Index')
            ->has('mrr_series_prev', fn ($series) => $series
                ->each(fn ($item) => $item
                    ->has('date')
                    ->has('date_clear')
                    ->has('mrr')
                    ->has('subscribers')
                )
            )
        );
});

test('dashboard mrr_series_prev is null without a comparison', function () {
    Project::where('team_id', $this->user->currentTeam->id)->delete();

    $project = Project::factory()->create([
        'team_id' => $this->user->currentTeam->id,
        'usage' => StatsService::TRACKING_USAGE_BITS_VISITORS
            | StatsService::TRACKING_USAGE_BITS_USERS
            | StatsService::TRACKING_USAGE_BITS_PAYMENTS
            | StatsService::TRACKING_USAGE_BITS_SUBSCRIPTION,
    ]);

    $statsUser = StatsUser::factory()->create([
        'team_id' => $this->user->currentTeam->id,
        'project_id' => $project->id,
    ]);

    StatsPayment::factory()->subscriptionMonthly()->create([
        'team_id' => $this->user->currentTeam->id,
        'project_id' => $project->id,
        'stats_user_id' => $statsUser->id,
    ]);

    $this->get(route('dashboard', ['project_id' => $project->id]))
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Index')
            ->where('mrr_series_prev', null)
        );
});

test('dashboard mrr_series prop is null when the project does not track recurring revenue', function () {
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
            ->where('mrr_series', null)
        );
});

test('dashboard exposes a zeroed mrr_series when the project tracks recurring but has no payment yet', function () {
    Project::where('team_id', $this->user->currentTeam->id)->delete();

    $project = Project::factory()->create([
        'team_id' => $this->user->currentTeam->id,
        'usage' => StatsService::TRACKING_USAGE_BITS_VISITORS
            | StatsService::TRACKING_USAGE_BITS_USERS
            | StatsService::TRACKING_USAGE_BITS_PAYMENTS
            | StatsService::TRACKING_USAGE_BITS_SUBSCRIPTION,
    ]);

    $this->get(route('dashboard', ['project_id' => $project->id]))
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Index')
            ->has('mrr_series', fn ($series) => $series
                ->each(fn ($item) => $item
                    ->has('date')
                    ->has('date_clear')
                    ->where('mrr', 0)
                    ->where('subscribers', 0)
                )
            )
        );
});

test('dashboard mrr_series prop is null when the project tracks payments but not users', function () {
    Project::where('team_id', $this->user->currentTeam->id)->delete();

    $project = Project::factory()->create([
        'team_id' => $this->user->currentTeam->id,
        'usage' => StatsService::TRACKING_USAGE_BITS_VISITORS
            | StatsService::TRACKING_USAGE_BITS_PAYMENTS,
    ]);

    $statsUser = StatsUser::factory()->create([
        'team_id' => $this->user->currentTeam->id,
        'project_id' => $project->id,
    ]);

    StatsPayment::factory()->subscriptionMonthly()->create([
        'team_id' => $this->user->currentTeam->id,
        'project_id' => $project->id,
        'stats_user_id' => $statsUser->id,
    ]);

    $this->get(route('dashboard', ['project_id' => $project->id]))
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Index')
            ->where('mrr_series', null)
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

test('retention route renders Dashboard/Index in retention mode with cohorts when the project tracks users', function () {
    Project::where('team_id', $this->user->currentTeam->id)->delete();

    $project = Project::factory()->create([
        'team_id' => $this->user->currentTeam->id,
        'usage' => StatsService::TRACKING_USAGE_BITS_VISITORS
            | StatsService::TRACKING_USAGE_BITS_USERS,
    ]);

    $this->get(route('retention', ['project_id' => $project->id]))
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Index')
            ->where('showRetention', true)
            ->has('retention_cohorts.period_count')
            ->has('retention_cohorts.cohorts')
        );
});

test('dashboard route renders with retention off and no cohorts', function () {
    Project::where('team_id', $this->user->currentTeam->id)->delete();

    $project = Project::factory()->create([
        'team_id' => $this->user->currentTeam->id,
        'usage' => StatsService::TRACKING_USAGE_BITS_VISITORS
            | StatsService::TRACKING_USAGE_BITS_USERS,
    ]);

    $this->get(route('dashboard', ['project_id' => $project->id]))
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Index')
            ->where('showRetention', false)
            ->where('retention_cohorts', null)
        );
});

test('retention route exposes no cohorts when the project does not track users', function () {
    Project::where('team_id', $this->user->currentTeam->id)->delete();

    $project = Project::factory()->create([
        'team_id' => $this->user->currentTeam->id,
        'usage' => StatsService::TRACKING_USAGE_BITS_VISITORS,
    ]);

    $this->get(route('retention', ['project_id' => $project->id]))
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Index')
            ->where('showRetention', true)
            ->where('retention_cohorts', null)
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

test('retention route exposes engagement_series when the project tracks users', function () {
    Project::where('team_id', $this->user->currentTeam->id)->delete();

    $project = Project::factory()->create([
        'team_id' => $this->user->currentTeam->id,
        'usage' => StatsService::TRACKING_USAGE_BITS_VISITORS
            | StatsService::TRACKING_USAGE_BITS_USERS,
    ]);

    $this->get(route('retention', ['project_id' => $project->id]))
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Index')
            ->has('engagement_series')
        );
});

test('retention engagement_series is null when the project does not track users', function () {
    Project::where('team_id', $this->user->currentTeam->id)->delete();

    $project = Project::factory()->create([
        'team_id' => $this->user->currentTeam->id,
        'usage' => StatsService::TRACKING_USAGE_BITS_VISITORS,
    ]);

    $this->get(route('retention', ['project_id' => $project->id]))
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Index')
            ->where('engagement_series', null)
        );
});

test('dashboard route does not build engagement_series', function () {
    Project::where('team_id', $this->user->currentTeam->id)->delete();

    $project = Project::factory()->create([
        'team_id' => $this->user->currentTeam->id,
        'usage' => StatsService::TRACKING_USAGE_BITS_VISITORS
            | StatsService::TRACKING_USAGE_BITS_USERS,
    ]);

    $this->get(route('dashboard', ['project_id' => $project->id]))
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Index')
            ->where('engagement_series', null)
        );
});
