<?php

use App\Models\AdSpend;
use App\Models\Campaign;
use App\Models\Project;
use App\Models\User;
use Carbon\CarbonImmutable;

beforeEach(function () {
    CarbonImmutable::setTestNow('2026-06-21 12:00:00');

    $this->user = User::factory()->withPersonalTeam()->create();
    $this->user->currentTeam->update(['currency' => 'EUR']);
    $this->team = $this->user->currentTeam;
    $this->project = Project::factory()->create(['team_id' => $this->team->id]);
    $this->actingAs($this->user);
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

test('campaigns route returns 200', function () {
    $this->get(route('campaigns'))->assertStatus(200);
});

test('campaigns renders Campaigns/Index with the shared filter and profitability props', function () {
    $this->get(route('campaigns'))
        ->assertInertia(fn ($page) => $page
            ->component('Campaigns/Index')
            ->has('teamStats')
            ->has('presets')
            ->has('filters')
            ->has('rangeTypes')
            ->has('usageBits')
            ->has('campaigns')
            ->has('projects')
            ->missing('adSpends')
            ->missing('availableCampaigns')
        );
});

test('campaigns lists a campaign with its spend as a profitability row', function () {
    $campaign = Campaign::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'name' => 'Google Ads',
    ]);

    AdSpend::factory()->create([
        'team_id' => $this->team->id,
        'campaign_id' => $campaign->id,
        'amount' => 5000,
        'starts_on' => '2026-06-17',
        'ends_on' => '2026-06-21',
    ]);

    $this->get(route('campaigns', ['range_type' => 'days', 'range_amount' => 7, 'range_start' => '2026-06-21']))
        ->assertInertia(fn ($page) => $page
            ->component('Campaigns/Index')
            ->has('campaigns.data', 1, fn ($row) => $row
                ->where('campaign_id', $campaign->id)
                ->where('name', 'Google Ads')
                ->where('spend', 5000)
                ->where('net', 0)
                ->where('net_profit', -5000)
                ->etc()
            )
            ->has('campaigns.links')
        );
});

test('campaigns lists a freshly created campaign even without spend', function () {
    Campaign::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'name' => 'Pre-launch',
    ]);

    $this->get(route('campaigns'))
        ->assertInertia(fn ($page) => $page
            ->has('campaigns.data', 1, fn ($row) => $row
                ->where('name', 'Pre-launch')
                ->where('spend', 0)
                ->etc()
            )
        );
});

test('campaigns paginates the profitability list at 20 per page', function () {
    Campaign::factory()->count(25)->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
    ]);

    $this->get(route('campaigns'))
        ->assertInertia(fn ($page) => $page
            ->has('campaigns.data', 20)
            ->where('campaigns.total', 25)
            ->where('campaigns.per_page', 20)
        );

    $this->get(route('campaigns', ['page' => 2]))
        ->assertInertia(fn ($page) => $page
            ->has('campaigns.data', 5)
            ->where('campaigns.current_page', 2)
        );
});

test('campaigns exposes ltv:cac and cac payback keys on each row', function () {
    $this->project->update(['usage' => Project::USAGE_VISITORS | Project::USAGE_USERS | Project::USAGE_PAYMENTS | Project::USAGE_SUBSCRIPTION]);

    Campaign::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'name' => 'Sub Ads',
    ]);

    $this->get(route('campaigns'))
        ->assertInertia(fn ($page) => $page
            ->has('campaigns.data', 1, fn ($row) => $row
                ->where('ltv_cac', null)
                ->where('cac_payback', null)
                ->etc()
            )
        );
});
