<?php

use App\Mcp\Servers\SimpleStatsServer;
use App\Mcp\Tools\ListAppPages;
use App\Models\Project;
use App\Models\User;
use App\Services\Ai\AppPageCatalog;
use Database\Seeders\TestUserSeeder;
use Illuminate\Support\Facades\Context;

beforeEach(function () {
    $this->seed(TestUserSeeder::class);
    $this->user = User::where('email', config('app.test.user')['email'])->firstOrFail();
    $this->project = Project::first();
    $this->team = $this->project->team;

    auth()->setUser($this->user);
    Context::add('team_id', $this->team->id);
});

it('lists app pages with anchored team settings urls', function () {
    SimpleStatsServer::actingAs($this->user)
        ->tool(ListAppPages::class)
        ->assertOk()
        ->assertSee('Team members')
        ->assertSee(route('teams.show', $this->team->id).'#team-members')
        ->assertSee(route('projects.index'));
});

it('hides pages whose tracking usage the team does not have', function () {
    $titles = collect(app(AppPageCatalog::class)->pages())->pluck('title');

    expect($titles)->toContain('Campaigns');

    $this->project->update(['usage' => Project::USAGE_VISITORS]);

    $titles = collect(app(AppPageCatalog::class)->pages())->pluck('title');

    expect($titles)->not->toContain('Campaigns')
        ->not->toContain('Retention')
        ->not->toContain('Revenue')
        ->toContain('Dashboard');
});
