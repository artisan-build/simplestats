<?php

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->seed();
    $this->user = User::where('email', config('app.test.user')['email'])->firstOrFail();
    $this->project = Project::first();
    $this->token = $this->project->createToken('mcp-test-token')->plainTextToken;

    // TestUserSeeder leaves the test user on the web guard; forget it so the
    // Bearer token (the project) wins over Sanctum's stateful session check.
    auth()->forgetGuards();
});

function mcpRequest(string $method, array $params = [], int $id = 1): array
{
    return [
        'jsonrpc' => '2.0',
        'id' => $id,
        'method' => $method,
        'params' => $params,
    ];
}

function mcpHeaders(?string $token): array
{
    return array_filter([
        'Accept' => 'application/json, text/event-stream',
        'Authorization' => $token ? 'Bearer '.$token : null,
    ]);
}

it('rejects unauthenticated requests', function () {
    $this->postJson('/mcp', mcpRequest('initialize'), mcpHeaders(null))
        ->assertUnauthorized();
});

it('rejects user tokens, only project tokens are allowed', function () {
    $userToken = $this->user->createToken('user-token')->plainTextToken;
    auth()->forgetGuards();

    $this->postJson('/mcp', mcpRequest('initialize'), mcpHeaders($userToken))
        ->assertForbidden();
});

it('initializes with a project token', function () {
    $response = $this->postJson('/mcp', mcpRequest('initialize', [
        'protocolVersion' => '2025-03-26',
        'capabilities' => [],
        'clientInfo' => ['name' => 'pest', 'version' => '1.0'],
    ]), mcpHeaders($this->token));

    $response->assertOk();
    expect($response->getContent())->toContain('SimpleStats');
});

it('serves team-scoped kpi stats defaulting to the token project', function () {
    DB::table('stats_visitors')->delete();

    $time = now()->subDay()->format('Y-m-d').' 12:00:00';

    for ($i = 0; $i < 3; $i++) {
        DB::table('stats_visitors')->insert([
            'team_id' => $this->project->team_id,
            'project_id' => $this->project->id,
            'visitor_hash' => Str::uuid()->toString(),
            'time' => $time,
            'time_utc' => $time,
        ]);
    }

    $response = $this->postJson('/mcp', mcpRequest('tools/call', [
        'name' => 'get-kpi-stats',
        'arguments' => ['preset' => 'last_7_days'],
    ], 2), mcpHeaders($this->token));

    $response->assertOk();

    expect($response->getContent())
        ->toContain('Project '.$this->project->id)
        ->not->toContain('All projects');
});
