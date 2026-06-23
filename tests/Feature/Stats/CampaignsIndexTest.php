<?php

use App\Models\AdSpend;
use App\Models\Project;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

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

test('campaigns renders Campaigns/Index with the shared filter and ad-spend props', function () {
    $this->get(route('campaigns'))
        ->assertInertia(fn ($page) => $page
            ->component('Campaigns/Index')
            ->has('teamStats')
            ->has('presets')
            ->has('filters')
            ->has('rangeTypes')
            ->has('trackingUsageBits')
            ->has('campaigns')
            ->has('adSpends')
            ->has('availableCampaigns')
        );
});

test('campaigns surfaces an ad spend as a profitability row and in the management list', function () {
    $campaignId = DB::table('stats_track_campaigns')->insertGetId([
        'team_id' => $this->team->id,
        'name' => 'Google Ads',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    AdSpend::factory()->create([
        'project_id' => $this->project->id,
        'stats_track_campaign_id' => $campaignId,
        'amount' => 5000,
        'starts_on' => '2026-06-17',
        'ends_on' => '2026-06-21',
    ]);

    $this->get(route('campaigns', ['range_type' => 'days', 'range_amount' => 7, 'range_start' => '2026-06-21']))
        ->assertInertia(fn ($page) => $page
            ->component('Campaigns/Index')
            ->has('campaigns', 1, fn ($row) => $row
                ->where('campaign_id', $campaignId)
                ->where('name', 'Google Ads')
                ->where('spend', 5000)
                ->where('net', 0)
                ->where('net_profit', -5000)
                ->etc()
            )
            ->has('adSpends', 1, fn ($spend) => $spend
                ->where('campaign_name', 'Google Ads')
                ->where('amount', 5000)
                ->etc()
            )
            ->has('availableCampaigns', 1)
        );
});
