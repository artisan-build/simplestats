<?php

use App\Models\Campaign;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->withPersonalTeam()->create();
    actingAs($this->user);

    $this->team = $this->user->currentTeam;
    $this->project = Project::factory()->create(['team_id' => $this->team->id]);
});

test('a legacy numeric project url permanently redirects to the uuid url', function () {
    $this->get('/projects/'.$this->project->id)
        ->assertStatus(301)
        ->assertRedirect(route('projects.show', $this->project));
});

test('a legacy numeric team url permanently redirects to the uuid url', function () {
    $this->get('/teams/'.$this->team->id)
        ->assertStatus(301)
        ->assertRedirect(route('teams.show', $this->team));
});

test('a legacy campaign url redirects with its query string preserved', function () {
    $campaign = Campaign::factory()->create(['project_id' => $this->project->id]);

    $this->get('/campaigns/'.$campaign->id.'?range_type=days&range_amount=7')
        ->assertStatus(301)
        ->assertRedirect(route('campaigns.show', ['campaign' => $campaign, 'range_type' => 'days', 'range_amount' => 7]));
});

test('a foreign project id does not redirect and stays a 404', function () {
    // Raw insert: the HasTeamScope saving hook would force the acting user's
    // team onto a factory-created project.
    $foreignId = DB::table('projects')->insertGetId([
        'uuid' => (string) Str::uuid(),
        'team_id' => User::factory()->withPersonalTeam()->create()->currentTeam->id,
        'name' => 'Foreign',
        'usage' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->get('/projects/'.$foreignId)->assertNotFound();
});

test('a foreign team id does not redirect and stays a 404', function () {
    $foreignTeam = User::factory()->withPersonalTeam()->create()->currentTeam;

    $this->get('/teams/'.$foreignTeam->id)->assertNotFound();
});

test('non-get requests with numeric ids are not redirected', function () {
    $this->delete('/projects/'.$this->project->id, ['password' => 'password'])
        ->assertNotFound();
});
