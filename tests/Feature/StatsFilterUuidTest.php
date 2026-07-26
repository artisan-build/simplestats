<?php

use App\Models\Campaign;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->withPersonalTeam()->create();
    actingAs($this->user);

    $this->team = $this->user->currentTeam;
    $this->team->update(['currency' => 'USD', 'timezone' => 'UTC']);
    $this->project = Project::factory()->create(['team_id' => $this->team->id]);
    // A second project so the single-project auto-select does not kick in.
    Project::factory()->create(['team_id' => $this->team->id]);
});

test('the dashboard accepts a project uuid filter and echoes it back', function () {
    $this->get(route('dashboard', ['project_id' => $this->project->uuid]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('filters.project_id', $this->project->uuid)
            ->etc());
});

test('a legacy numeric project filter still works and self-heals to the uuid', function () {
    $this->get(route('dashboard', ['project_id' => $this->project->id]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('filters.project_id', $this->project->uuid)
            ->etc());
});

test('an unknown project uuid falls back to all projects', function () {
    $this->get(route('dashboard', ['project_id' => (string) Str::uuid()]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('filters.project_id', 0)
            ->etc());
});

test('the campaign filter accepts and echoes the campaign uuid', function () {
    $campaign = Campaign::factory()->create(['project_id' => $this->project->id]);

    $this->get(route('dashboard', ['project_id' => $this->project->uuid, 'campaign' => $campaign->uuid]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('filters.campaign', $campaign->uuid)
            ->etc());
});

test('the shared projects map is keyed by uuid with the all entry at 0', function () {
    $this->get(route('dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('teamStats.projects.0')
            ->has('teamStats.projects.'.$this->project->uuid)
            ->etc());
});
