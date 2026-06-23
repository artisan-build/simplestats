<?php

use App\Models\AdSpend;
use App\Models\Campaign;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;

function campaignFor(Project $project, string $name = 'Summer Sale'): Campaign
{
    return Campaign::factory()->create([
        'team_id' => $project->team_id,
        'project_id' => $project->id,
        'name' => $name,
    ]);
}

beforeEach(function () {
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->team = $this->user->currentTeam;
    $this->project = Project::factory()->create(['team_id' => $this->team->id]);
    $this->actingAs($this->user);
});

test('store creates an ad spend and converts the amount to cents', function () {
    $campaign = campaignFor($this->project);

    $this->post(route('ad-spends.store'), [
        'campaign_id' => $campaign->id,
        'amount' => '12.50',
        'currency' => 'EUR',
        'starts_on' => '2026-06-01',
        'ends_on' => '2026-06-30',
    ])->assertRedirect();

    $this->assertDatabaseHas('ad_spends', [
        'team_id' => $this->team->id,
        'campaign_id' => $campaign->id,
        'amount' => 1250,
        'currency' => 'EUR',
    ]);
});

test('store validates required fields', function () {
    $this->post(route('ad-spends.store'), [])
        ->assertSessionHasErrors(['campaign_id', 'amount', 'currency', 'starts_on', 'ends_on']);
});

test('store rejects an end date before the start date', function () {
    $campaign = campaignFor($this->project);

    $this->post(route('ad-spends.store'), [
        'campaign_id' => $campaign->id,
        'amount' => '100',
        'currency' => 'EUR',
        'starts_on' => '2026-06-30',
        'ends_on' => '2026-06-01',
    ])->assertSessionHasErrors('ends_on');
});

test('store rejects a campaign from another team', function () {
    $otherTeam = User::factory()->withPersonalTeam()->create()->currentTeam;
    $foreignCampaignId = DB::table('campaigns')->insertGetId([
        'team_id' => $otherTeam->id,
        'project_id' => $this->project->id,
        'name' => 'Foreign',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->post(route('ad-spends.store'), [
        'campaign_id' => $foreignCampaignId,
        'amount' => '100',
        'currency' => 'EUR',
        'starts_on' => '2026-06-01',
        'ends_on' => '2026-06-30',
    ])->assertSessionHasErrors('campaign_id');
});

test('update changes an ad spend', function () {
    $campaign = campaignFor($this->project);
    $spend = AdSpend::factory()->create([
        'team_id' => $this->team->id,
        'campaign_id' => $campaign->id,
        'amount' => 5000,
    ]);

    $this->put(route('ad-spends.update', $spend), [
        'campaign_id' => $campaign->id,
        'amount' => '99',
        'currency' => 'EUR',
        'starts_on' => '2026-06-01',
        'ends_on' => '2026-06-30',
    ])->assertRedirect();

    expect($spend->fresh()->amount)->toBe(9900);
});

test('destroy deletes an ad spend', function () {
    $spend = AdSpend::factory()->create([
        'team_id' => $this->team->id,
        'campaign_id' => campaignFor($this->project)->id,
    ]);

    $this->delete(route('ad-spends.destroy', $spend))->assertRedirect();

    $this->assertDatabaseMissing('ad_spends', ['id' => $spend->id]);
});

test('cannot delete an ad spend of another team', function () {
    $otherTeam = User::factory()->withPersonalTeam()->create()->currentTeam;
    $foreignCampaignId = DB::table('campaigns')->insertGetId([
        'team_id' => $otherTeam->id,
        'project_id' => $this->project->id,
        'name' => 'Foreign',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $foreignId = DB::table('ad_spends')->insertGetId([
        'team_id' => $otherTeam->id,
        'campaign_id' => $foreignCampaignId,
        'amount' => 5000,
        'currency' => 'EUR',
        'starts_on' => '2026-06-01',
        'ends_on' => '2026-06-30',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->delete(route('ad-spends.destroy', $foreignId))->assertNotFound();

    $this->assertDatabaseHas('ad_spends', ['id' => $foreignId]);
});
