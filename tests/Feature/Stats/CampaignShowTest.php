<?php

use App\Models\AdSpend;
use App\Models\Campaign;
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

    $this->campaign = Campaign::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'name' => 'Google Ads',
    ]);
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

test('campaign show route returns 200', function () {
    $this->get(route('campaigns.show', $this->campaign))->assertStatus(200);
});

test('campaign show renders Campaigns/Show with campaign, summary, paginated spends and links', function () {
    AdSpend::factory()->create([
        'team_id' => $this->team->id,
        'campaign_id' => $this->campaign->id,
        'amount' => 5000,
        'starts_on' => '2026-06-17',
        'ends_on' => '2026-06-21',
    ]);

    $this->get(route('campaigns.show', ['campaign' => $this->campaign->id, 'range_type' => 'days', 'range_amount' => 7, 'range_start' => '2026-06-21']))
        ->assertInertia(fn ($page) => $page
            ->component('Campaigns/Show')
            ->where('campaign.id', $this->campaign->id)
            ->where('campaign.name', 'Google Ads')
            ->where('summary.campaign_id', $this->campaign->id)
            ->where('summary.spend', 5000)
            ->where('summary.net', 0)
            ->where('summary.net_profit', -5000)
            ->has('adSpends.data', 1, fn ($spend) => $spend
                ->where('amount', 5000)
                ->etc()
            )
            ->has('adSpends.links')
            ->has('linkedTrackCampaigns')
            ->has('linkableTrackCampaigns')
        );
});

test('campaign show lists linked utm campaigns', function () {
    DB::table('stats_track_campaigns')->insert([
        'team_id' => $this->team->id,
        'name' => 'google_ads_brand',
        'campaign_id' => $this->campaign->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->get(route('campaigns.show', $this->campaign))
        ->assertInertia(fn ($page) => $page
            ->has('linkedTrackCampaigns', 1, fn ($utm) => $utm
                ->where('name', 'google_ads_brand')
                ->etc()
            )
        );
});

test('campaign show returns a zero summary when the campaign has no spend or revenue', function () {
    $this->get(route('campaigns.show', $this->campaign))
        ->assertInertia(fn ($page) => $page
            ->component('Campaigns/Show')
            ->where('summary.spend', 0)
            ->where('summary.net', 0)
            ->where('summary.net_profit', 0)
            ->where('summary.roas', null)
            ->where('summary.cac', null)
            ->has('adSpends.data', 0)
        );
});

test('campaign show 404s for another team campaign', function () {
    $otherTeam = User::factory()->withPersonalTeam()->create()->currentTeam;
    $foreignId = DB::table('campaigns')->insertGetId([
        'team_id' => $otherTeam->id,
        'project_id' => $this->project->id,
        'name' => 'Foreign',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->get(route('campaigns.show', $foreignId))->assertNotFound();
});
