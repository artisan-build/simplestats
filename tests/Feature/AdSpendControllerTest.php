<?php

use App\Models\AdSpend;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;

function campaignFor(int $teamId, string $name = 'Google Ads'): int
{
    return DB::table('stats_track_campaigns')->insertGetId([
        'team_id' => $teamId,
        'name' => $name,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

beforeEach(function () {
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->team = $this->user->currentTeam;
    $this->project = Project::factory()->create(['team_id' => $this->team->id]);
    $this->actingAs($this->user);
});

test('store creates an ad spend and converts the amount to cents', function () {
    $campaignId = campaignFor($this->team->id);

    $this->post(route('ad-spends.store'), [
        'project_id' => $this->project->id,
        'stats_track_campaign_id' => $campaignId,
        'amount' => '12.50',
        'currency' => 'EUR',
        'starts_on' => '2026-06-01',
        'ends_on' => '2026-06-30',
    ])->assertRedirect();

    $this->assertDatabaseHas('ad_spends', [
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'stats_track_campaign_id' => $campaignId,
        'amount' => 1250,
        'currency' => 'EUR',
    ]);
});

test('store validates required fields', function () {
    $this->post(route('ad-spends.store'), [])
        ->assertSessionHasErrors(['project_id', 'stats_track_campaign_id', 'amount', 'currency', 'starts_on', 'ends_on']);
});

test('store rejects an end date before the start date', function () {
    $campaignId = campaignFor($this->team->id);

    $this->post(route('ad-spends.store'), [
        'project_id' => $this->project->id,
        'stats_track_campaign_id' => $campaignId,
        'amount' => '100',
        'currency' => 'EUR',
        'starts_on' => '2026-06-30',
        'ends_on' => '2026-06-01',
    ])->assertSessionHasErrors('ends_on');
});

test('store rejects a campaign from another team', function () {
    $otherTeam = User::factory()->withPersonalTeam()->create()->currentTeam;
    $foreignCampaign = campaignFor($otherTeam->id, 'Foreign');

    $this->post(route('ad-spends.store'), [
        'project_id' => $this->project->id,
        'stats_track_campaign_id' => $foreignCampaign,
        'amount' => '100',
        'currency' => 'EUR',
        'starts_on' => '2026-06-01',
        'ends_on' => '2026-06-30',
    ])->assertSessionHasErrors('stats_track_campaign_id');
});

test('update changes an ad spend', function () {
    $campaignId = campaignFor($this->team->id);
    $spend = AdSpend::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'stats_track_campaign_id' => $campaignId,
        'amount' => 5000,
    ]);

    $this->put(route('ad-spends.update', $spend), [
        'project_id' => $this->project->id,
        'stats_track_campaign_id' => $campaignId,
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
        'project_id' => $this->project->id,
        'stats_track_campaign_id' => campaignFor($this->team->id),
    ]);

    $this->delete(route('ad-spends.destroy', $spend))->assertRedirect();

    $this->assertDatabaseMissing('ad_spends', ['id' => $spend->id]);
});

test('cannot delete an ad spend of another team', function () {
    $otherTeam = User::factory()->withPersonalTeam()->create()->currentTeam;
    $otherProject = Project::factory()->create(['team_id' => $otherTeam->id]);

    $foreignId = DB::table('ad_spends')->insertGetId([
        'team_id' => $otherTeam->id,
        'project_id' => $otherProject->id,
        'stats_track_campaign_id' => campaignFor($otherTeam->id, 'Foreign'),
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
