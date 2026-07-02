<?php

use App\Models\Campaign;
use App\Models\Project;
use App\Models\StatsTrackCampaign;
use App\Models\User;
use Illuminate\Support\Facades\DB;

const USAGE_WITHOUT_PAYMENTS = Project::USAGE_VISITORS | Project::USAGE_USERS;

beforeEach(function () {
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->team = $this->user->currentTeam;
    $this->project = Project::factory()->create(['team_id' => $this->team->id]);
    $this->actingAs($this->user);
});

test('storing a campaign creates it and redirects to its detail page', function () {
    $response = $this->post(route('campaigns.store'), [
        'project_id' => $this->project->id,
        'name' => 'Summer Sale 2026',
    ]);

    $campaign = Campaign::where('name', 'Summer Sale 2026')->first();

    expect($campaign)->not->toBeNull()
        ->and($campaign->team_id)->toBe($this->team->id)
        ->and($campaign->project_id)->toBe($this->project->id);

    $response->assertRedirect(route('campaigns.show', $campaign));
});

test('storing an existing campaign name in the same project is idempotent', function () {
    $existing = Campaign::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'name' => 'Black Friday',
    ]);

    $this->post(route('campaigns.store'), [
        'project_id' => $this->project->id,
        'name' => 'Black Friday',
    ])->assertRedirect(route('campaigns.show', $existing));

    expect(Campaign::where('name', 'Black Friday')->count())->toBe(1);
});

test('campaign name and project are required', function () {
    $this->post(route('campaigns.store'), [])
        ->assertSessionHasErrors(['project_id', 'name']);
});

test('campaign rejects a project from another team', function () {
    $otherUser = User::factory()->withPersonalTeam()->create();
    $this->actingAs($otherUser);
    $otherProject = Project::factory()->create(['team_id' => $otherUser->currentTeam->id]);
    $this->actingAs($this->user);

    $this->post(route('campaigns.store'), [
        'project_id' => $otherProject->id,
        'name' => 'Cross team',
    ])->assertSessionHasErrors('project_id');
});

test('updating a campaign renames it', function () {
    $campaign = Campaign::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'name' => 'Old name',
    ]);

    $this->patch(route('campaigns.update', $campaign), ['name' => 'New name'])
        ->assertRedirect();

    expect($campaign->fresh()->name)->toBe('New name');
});

test('updating a campaign requires a name', function () {
    $campaign = Campaign::factory()->create(['team_id' => $this->team->id, 'project_id' => $this->project->id]);

    $this->patch(route('campaigns.update', $campaign), ['name' => ''])
        ->assertSessionHasErrors('name');
});

test('updating a campaign of another team is not possible', function () {
    $otherUser = User::factory()->withPersonalTeam()->create();
    $this->actingAs($otherUser);
    $otherProject = Project::factory()->create(['team_id' => $otherUser->currentTeam->id]);
    $campaign = Campaign::factory()->create([
        'team_id' => $otherUser->currentTeam->id,
        'project_id' => $otherProject->id,
        'name' => 'Orig',
    ]);
    $this->actingAs($this->user);

    $this->patch(route('campaigns.update', $campaign), ['name' => 'Hijack'])
        ->assertNotFound();

    expect($campaign->fresh()->name)->toBe('Orig');
});

test('linking a utm value creates and attaches the tracked dimension', function () {
    $campaign = Campaign::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
    ]);

    $this->post(route('campaigns.track-campaigns.link', $campaign), ['name' => 'summer_sale_2026'])
        ->assertRedirect();

    $this->assertDatabaseHas('stats_track_campaigns', [
        'team_id' => $this->team->id,
        'name' => 'summer_sale_2026',
        'campaign_id' => $campaign->id,
    ]);
});

test('linking an already tracked utm value attaches the existing row', function () {
    $campaign = Campaign::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
    ]);

    $utmId = DB::table('stats_track_campaigns')->insertGetId([
        'team_id' => $this->team->id,
        'name' => 'existing_utm',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->post(route('campaigns.track-campaigns.link', $campaign), ['name' => 'existing_utm'])
        ->assertRedirect();

    expect(StatsTrackCampaign::where('name', 'existing_utm')->count())->toBe(1)
        ->and(StatsTrackCampaign::find($utmId)->campaign_id)->toBe($campaign->id);
});

test('linking a utm value already owned by another campaign is rejected', function () {
    $other = Campaign::factory()->create(['team_id' => $this->team->id, 'project_id' => $this->project->id]);
    $campaign = Campaign::factory()->create(['team_id' => $this->team->id, 'project_id' => $this->project->id]);

    DB::table('stats_track_campaigns')->insert([
        'team_id' => $this->team->id,
        'name' => 'taken_utm',
        'campaign_id' => $other->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->post(route('campaigns.track-campaigns.link', $campaign), ['name' => 'taken_utm'])
        ->assertSessionHasErrors('name');

    expect(StatsTrackCampaign::where('name', 'taken_utm')->first()->campaign_id)->toBe($other->id);
});

test('campaigns index is forbidden when no project tracks payments', function () {
    $this->team->projects()->update(['usage' => USAGE_WITHOUT_PAYMENTS]);

    $this->get(route('campaigns'))->assertForbidden();
});

test('storing a campaign is forbidden when no project tracks payments', function () {
    $this->team->projects()->update(['usage' => USAGE_WITHOUT_PAYMENTS]);

    $this->post(route('campaigns.store'), [
        'project_id' => $this->project->id,
        'name' => 'No payments',
    ])->assertForbidden();
});

test('viewing a campaign is forbidden when its project tracks no payments', function () {
    $this->project->update(['usage' => USAGE_WITHOUT_PAYMENTS]);
    $campaign = Campaign::factory()->create(['team_id' => $this->team->id, 'project_id' => $this->project->id]);

    $this->get(route('campaigns.show', $campaign))->assertForbidden();
});

test('unlinking detaches the tracked dimension', function () {
    $campaign = Campaign::factory()->create(['team_id' => $this->team->id, 'project_id' => $this->project->id]);

    $utmId = DB::table('stats_track_campaigns')->insertGetId([
        'team_id' => $this->team->id,
        'name' => 'linked_utm',
        'campaign_id' => $campaign->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->delete(route('campaigns.track-campaigns.unlink', ['campaign' => $campaign->id, 'trackCampaign' => $utmId]))
        ->assertRedirect();

    expect(StatsTrackCampaign::find($utmId)->campaign_id)->toBeNull();
});
