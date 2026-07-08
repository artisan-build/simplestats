<?php

use App\Mcp\Servers\SimpleStatsServer;
use App\Mcp\Tools\SearchDocs;
use App\Models\User;
use App\Services\Ai\DocsSearchService;
use Database\Seeders\TestUserSeeder;

beforeEach(function () {
    $this->seed(TestUserSeeder::class);
    $this->user = User::where('email', config('app.test.user')['email'])->firstOrFail();
});

it('finds the payment tracking docs page', function () {
    SimpleStatsServer::actingAs($this->user)
        ->tool(SearchDocs::class, ['query' => 'track a new payment'])
        ->assertOk()
        ->assertSee('how-to-track-a-new-payment')
        ->assertStructuredContent(function ($json) {
            $json->has('results.0.title')
                ->has('results.0.url')
                ->has('results.0.excerpt')
                ->etc();
        });
});

it('notes when nothing matches', function () {
    SimpleStatsServer::actingAs($this->user)
        ->tool(SearchDocs::class, ['query' => 'xyzzyplugh'])
        ->assertOk()
        ->assertSee('No documentation pages matched');
});

it('rejects an empty query', function () {
    SimpleStatsServer::actingAs($this->user)
        ->tool(SearchDocs::class, [])
        ->assertHasErrors();
});

it('never returns the excluded starter pages', function () {
    $results = app(DocsSearchService::class)->search('markdown examples table', 10);

    expect(collect($results)->pluck('page')->all())
        ->not->toContain('markdown-examples')
        ->not->toContain('index');
});
