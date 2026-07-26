<?php

use App\Models\AdSpend;
use App\Models\Campaign;
use App\Models\CustomStatsRangePreset;
use App\Models\DataImport;
use App\Models\Project;
use App\Models\StatsTrackCampaign;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->withPersonalTeam()->create();
    actingAs($this->user);

    $this->team = $this->user->currentTeam;
    $this->project = Project::factory()->create(['team_id' => $this->team->id]);
});

test('all user-facing models get a v4 uuid on creation', function () {
    $campaign = Campaign::factory()->create(['project_id' => $this->project->id]);
    $adSpend = AdSpend::factory()->create(['campaign_id' => $campaign->id]);
    $preset = CustomStatsRangePreset::factory()->for($this->user)->create();
    $trackCampaign = StatsTrackCampaign::create(['name' => 'summer_sale']);

    foreach ([$this->team, $campaign, $adSpend, $preset, $trackCampaign] as $model) {
        expect($model->uuid)->not->toBeNull()
            ->and(Str::isUuid($model->uuid, version: 4))->toBeTrue()
            ->and($model->getRouteKey())->toBe($model->uuid);
    }
});

test('a team resolves via its uuid and its numeric id redirects', function () {
    $this->get(route('teams.show', $this->team))->assertSuccessful();
    $this->get('/teams/'.$this->team->id)->assertStatus(301);
});

test('the overridden jetstream team update route resolves the uuid', function () {
    $this->put(route('teams.update', $this->team), [
        'name' => 'Renamed Team',
        'currency' => 'EUR',
        'timezone' => 'Europe/Berlin',
    ])->assertSessionHasNoErrors()
        ->assertRedirect();

    expect($this->team->fresh()->name)->toBe('Renamed Team');
});

test('a campaign resolves via its uuid and its numeric id redirects', function () {
    $campaign = Campaign::factory()->create(['project_id' => $this->project->id]);

    $this->get(route('campaigns.show', $campaign))->assertSuccessful();
    $this->get('/campaigns/'.$campaign->id)->assertStatus(301);
});

test('a track campaign unlinks via its uuid', function () {
    $campaign = Campaign::factory()->create(['project_id' => $this->project->id]);
    $trackCampaign = StatsTrackCampaign::create(['name' => 'summer_sale', 'campaign_id' => $campaign->id]);

    $this->delete(route('campaigns.track-campaigns.unlink', [$campaign, $trackCampaign]))
        ->assertRedirect();

    expect($trackCampaign->fresh()->campaign_id)->toBeNull();
});

test('an ad spend deletes via its uuid but not via its numeric id', function () {
    $campaign = Campaign::factory()->create(['project_id' => $this->project->id]);
    $adSpend = AdSpend::factory()->create(['campaign_id' => $campaign->id]);

    $this->delete('/ad-spends/'.$adSpend->id)->assertNotFound();
    $this->delete(route('ad-spends.destroy', $adSpend))->assertRedirect();

    expect(AdSpend::whereKey($adSpend)->exists())->toBeFalse();
});

test('a custom preset deletes via its uuid but not via its numeric id', function () {
    $preset = CustomStatsRangePreset::factory()->for($this->user)->create();

    $this->delete('/custom-presets/'.$preset->id)->assertNotFound();
    $this->delete(route('custom-presets.destroy', $preset))->assertRedirect();

    expect(CustomStatsRangePreset::whereKey($preset)->exists())->toBeFalse();
});

test('data import action urls use the uuid and reject numeric ids', function () {
    $dataImport = DataImport::factory()->create([
        'project_id' => $this->project->id,
        'created_by' => $this->user->id,
    ]);

    expect(route('data-import.cancel', $dataImport))->toContain($dataImport->uuid);

    $this->post('/data-import/'.$dataImport->id.'/cancel')->assertNotFound();
});

test('users get a random v4 uuid', function () {
    expect(Str::isUuid($this->user->uuid, version: 4))->toBeTrue();
});

test('team member routes resolve the member via uuid but not via the numeric id', function () {
    $member = User::factory()->withPersonalTeam()->create();
    $this->team->users()->attach($member, ['role' => 'editor']);

    $this->delete('/teams/'.$this->team->uuid.'/members/'.$member->id)->assertNotFound();

    $this->delete(route('team-members.destroy', [$this->team, $member]))->assertRedirect();

    expect($member->fresh()->belongsToTeam($this->team))->toBeFalse();
});

test('switching teams maps a team url onto the new team uuid', function () {
    $newTeam = Team::factory()->create(['user_id' => $this->user->id, 'personal_team' => false]);

    $this->put(route('current-team.update'), ['team_id' => $newTeam->id], [
        'referer' => 'https://simplestats.test/teams/'.$this->team->uuid,
    ])->assertRedirect('https://simplestats.test/teams/'.$newTeam->uuid);
});

test('switching teams strips a uuid id segment from entity urls', function () {
    $newTeam = Team::factory()->create(['user_id' => $this->user->id, 'personal_team' => false]);

    $this->put(route('current-team.update'), ['team_id' => $newTeam->id], [
        'referer' => 'https://simplestats.test/campaigns/'.Str::uuid(),
    ])->assertRedirect('https://simplestats.test/campaigns');
});

test('switching teams keeps non-id path segments like the metric kpi', function () {
    $newTeam = Team::factory()->create(['user_id' => $this->user->id, 'personal_team' => false]);

    $this->put(route('current-team.update'), ['team_id' => $newTeam->id], [
        'referer' => 'https://simplestats.test/dashboard/metric/pd_visitor',
    ])->assertRedirect('https://simplestats.test/dashboard/metric/pd_visitor');
});
