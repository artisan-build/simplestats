<?php

use App\Models\Project;
use App\Models\User;

beforeEach(function () {
    $this->seed();
    $this->user = User::where('email', config('app.test.user')['email'])->firstOrFail();
    $this->project = Project::first();
    $this->token = $this->project->createToken('test-token')->plainTextToken;
});

function statsApiHeaders(string $token): array
{
    return [
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
    ];
}

test('stats index without auth returns 401', function () {
    auth()->forgetGuards();

    $response = $this->getJson('/api/v1/stats', [
        'Authorization' => 'Bearer invalid-token',
        'Accept' => 'application/json',
    ]);

    $response->assertUnauthorized();
});

test('stats index with valid token returns 200 with correct structure', function () {
    $response = $this->getJson('/api/v1/stats', statsApiHeaders($this->token));

    $response->assertOk()
        ->assertJsonStructure([
            'data',
            'meta' => [
                'project_id',
                'range_type',
                'range_amount',
                'range_start',
                'preset',
                'comparison',
            ],
        ]);
});

test('stats index defaults to last 7 days when no parameters given', function () {
    $response = $this->getJson('/api/v1/stats', statsApiHeaders($this->token));

    $response->assertOk()
        ->assertJsonPath('meta.preset', 'last_7_days')
        ->assertJsonPath('meta.range_type', 'days')
        ->assertJsonPath('meta.range_amount', 7);
});

test('stats index with preset parameter applies preset', function () {
    $response = $this->getJson('/api/v1/stats?preset=last_30_days', statsApiHeaders($this->token));

    $response->assertOk()
        ->assertJsonPath('meta.preset', 'last_30_days')
        ->assertJsonPath('meta.range_type', 'days')
        ->assertJsonPath('meta.range_amount', 30);
});

test('stats index with comparison returns data_previous', function () {
    $response = $this->getJson('/api/v1/stats?preset=last_7_days&comparison=period', statsApiHeaders($this->token));

    $response->assertOk()
        ->assertJsonStructure([
            'data',
            'data_previous',
            'meta',
        ]);
});

test('stats index without comparison does not return data_previous', function () {
    $response = $this->getJson('/api/v1/stats', statsApiHeaders($this->token));

    $response->assertOk();
    expect($response->json())->not->toHaveKey('data_previous');
});

test('stats index with invalid preset returns 422', function () {
    $response = $this->getJson('/api/v1/stats?preset=invalid_preset', statsApiHeaders($this->token));

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('preset');
});

test('stats index with invalid comparison returns 422', function () {
    $response = $this->getJson('/api/v1/stats?comparison=invalid', statsApiHeaders($this->token));

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('comparison');
});

test('stats grouped without auth returns 401', function () {
    auth()->forgetGuards();

    $response = $this->getJson('/api/v1/stats/grouped?stats_type=track_source', [
        'Authorization' => 'Bearer invalid-token',
        'Accept' => 'application/json',
    ]);

    $response->assertUnauthorized();
});

test('stats grouped with valid token returns 200 with correct structure', function () {
    $response = $this->getJson('/api/v1/stats/grouped?stats_type=track_source', statsApiHeaders($this->token));

    $response->assertOk()
        ->assertJsonStructure([
            'data',
            'meta' => [
                'project_id',
                'range_type',
                'range_amount',
                'range_start',
                'preset',
                'comparison',
            ],
        ]);
});

test('stats grouped without statsType returns 422', function () {
    $response = $this->getJson('/api/v1/stats/grouped', statsApiHeaders($this->token));

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('stats_type');
});

test('stats grouped with invalid statsType returns 422', function () {
    $response = $this->getJson('/api/v1/stats/grouped?stats_type=invalid_type', statsApiHeaders($this->token));

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('stats_type');
});

test('stats grouped with limit parameter limits results', function () {
    $response = $this->getJson('/api/v1/stats/grouped?stats_type=track_source&limit=3', statsApiHeaders($this->token));

    $response->assertOk();
    expect(count($response->json('data')))->toBeLessThanOrEqual(3);
});

test('stats index project_id in meta matches authenticated project', function () {
    $response = $this->getJson('/api/v1/stats', statsApiHeaders($this->token));

    $response->assertOk();
    expect($response->json('meta.project_id'))->toBeInt();
});

test('stats groups returns data for valid statsType', function () {
    $response = $this->getJson('/api/v1/stats/groups?stats_type=track_source', statsApiHeaders($this->token));

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name'],
            ],
        ]);
});

test('stats groups without statsType returns 422', function () {
    $response = $this->getJson('/api/v1/stats/groups', statsApiHeaders($this->token));

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('stats_type');
});

test('stats groups with invalid statsType returns 422', function () {
    $response = $this->getJson('/api/v1/stats/groups?stats_type=invalid', statsApiHeaders($this->token));

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('stats_type');
});

test('stats groups without auth returns 401', function () {
    auth()->forgetGuards();

    $response = $this->getJson('/api/v1/stats/groups?stats_type=track_source', [
        'Authorization' => 'Bearer invalid-token',
        'Accept' => 'application/json',
    ]);

    $response->assertUnauthorized();
});
