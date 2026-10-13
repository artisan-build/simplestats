<?php

use App\Models\Project;
use App\Models\User;

beforeEach(function (): void {
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->user->currentTeam->update(['currency' => 'EUR']);
    Project::factory()->create(['team_id' => $this->user->currentTeam->id]);
    $this->actingAs($this->user);
});

test('data-import index route returns 200', function (): void {
    $this->get(route('data-import.index'))->assertOk();
});

test('data-import index renders DataImports/Index with expected props', function (): void {
    $this->get(route('data-import.index'))
        ->assertInertia(fn ($page) => $page
            ->component('DataImports/Index')
            ->has('dataImports')
            ->has('projects')
            ->has('importTypes')
            ->has('mimeTypes')
            ->has('filters')
            ->has('permissions.canCreateDataImport')
        );
});
