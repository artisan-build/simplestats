<?php

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->user = User::factory()->withPersonalTeam()->create();
    actingAs($this->user);

    $this->project = Project::factory()->create([
        'team_id' => $this->user->currentTeam->id,
    ]);
});

test('a project gets a random v4 uuid on creation', function (): void {
    expect($this->project->uuid)
        ->not->toBeNull()
        ->and(Str::isUuid($this->project->uuid, version: 4))->toBeTrue();
});

test('project urls use the uuid instead of the id', function (): void {
    expect(route('projects.show', $this->project))
        ->toContain($this->project->uuid)
        ->not->toContain('/'.$this->project->id);
});

test('a project resolves via its uuid', function (): void {
    $this->get(route('projects.show', $this->project))
        ->assertSuccessful();
});

test('a numeric project id redirects to the uuid url', function (): void {
    $this->get('/projects/'.$this->project->id)
        ->assertStatus(301)
        ->assertRedirect(route('projects.show', $this->project));
});

test('an unknown uuid returns a 404', function (): void {
    $this->get('/projects/'.Str::uuid())
        ->assertNotFound();
});

test('storing a project redirects to its uuid url', function (): void {
    $this->post(route('projects.store'), [
        'name' => 'Uuid Project',
        'domain' => 'example.com',
        'usage' => 1,
    ])->assertRedirect(route('projects.show', Project::where('name', 'Uuid Project')->first()));
});
