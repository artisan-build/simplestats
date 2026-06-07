<?php

use App\Helpers\AppHelper;
use App\Models\Project;
use App\Models\StatsLogin;
use App\Models\StatsUser;
use App\Models\User;
use Database\Seeders\TestUserSeeder;
use Illuminate\Support\Facades\Context;

beforeEach(function () {
    $this->seed(TestUserSeeder::class);
    $this->user = User::where('email', config('app.test.user')['email'])->firstOrFail();
    $this->project = Project::first();
    $this->token = $this->project->createToken('test-token')->plainTextToken;

    // TestUserSeeder leaves the test user on the web guard; forget it so the
    // Bearer token (the project) wins over Sanctum's stateful session check.
    auth()->forgetGuards();
});

function validUserPayload(array $overrides = []): array
{
    return array_merge([
        'id' => (string) fake()->unique()->randomNumber(5),
        'time' => now()->format('Y-m-d H:i:s P'),
        'ip' => fake()->ipv4(),
        'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
    ], $overrides);
}

function setUserProjectContext($project): void
{
    auth()->setUser($project);
    AppHelper::setTeamContextFromAuth($project);
    Context::add('project_id', $project->id);
    Context::add('request_url', '/api/v1/stats-user');
}

test('store user with auth and required fields returns 200', function () {
    $payload = validUserPayload();

    $response = $this->postJson(
        '/api/v1/stats-user',
        $payload,
        apiHeaders($this->token),
    );

    $response->assertOk()
        ->assertJson([
            'message' => 'Stats User Request accepted.',
            'id' => $payload['id'],
        ]);
});

test('store user without auth returns 401', function () {
    auth()->forgetGuards();

    $response = $this->postJson(
        '/api/v1/stats-user',
        validUserPayload(),
        [
            'Authorization' => 'Bearer invalid-token',
            'Accept' => 'application/json',
        ],
    );

    $response->assertUnauthorized();
});

test('validation fails when id is missing', function () {
    $response = $this->postJson(
        '/api/v1/stats-user',
        validUserPayload(['id' => null]),
        apiHeaders($this->token),
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('id');
});

test('validation fails when time is missing', function () {
    $payload = validUserPayload();
    unset($payload['time']);

    $response = $this->postJson(
        '/api/v1/stats-user',
        $payload,
        apiHeaders($this->token),
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('time');
});

test('user is created with correct id', function () {
    $payload = validUserPayload();

    $this->postJson('/api/v1/stats-user', $payload, apiHeaders($this->token))->assertOk();

    setUserProjectContext($this->project);

    $statsUser = StatsUser::where('id', $payload['id'])->first();
    expect($statsUser)->not->toBeNull();
});

test('duplicate id performs updateOrCreate update', function () {
    $userId = (string) fake()->unique()->randomNumber(5);

    $payload1 = validUserPayload(['id' => $userId]);
    $this->postJson('/api/v1/stats-user', $payload1, apiHeaders($this->token))->assertOk();

    $payload2 = validUserPayload(['id' => $userId]);
    $this->postJson('/api/v1/stats-user', $payload2, apiHeaders($this->token))->assertOk();

    setUserProjectContext($this->project);

    $users = StatsUser::where('id', $userId)->get();
    expect($users)->toHaveCount(1);
});

test('add_login flag creates a login record', function () {
    $payload = validUserPayload(['add_login' => true]);

    $this->postJson('/api/v1/stats-user', $payload, apiHeaders($this->token))->assertOk();

    setUserProjectContext($this->project);

    $login = StatsLogin::where('stats_user_id', $payload['id'])->first();
    expect($login)->not->toBeNull();
});

test('without add_login flag no login record is created', function () {
    $payload = validUserPayload();

    $this->postJson('/api/v1/stats-user', $payload, apiHeaders($this->token))->assertOk();

    setUserProjectContext($this->project);

    $login = StatsLogin::where('stats_user_id', $payload['id'])->first();
    expect($login)->toBeNull();
});

test('bot user agents are rejected with 200', function () {
    $payload = validUserPayload([
        'user_agent' => 'Googlebot/2.1 (+http://www.google.com/bot.html)',
    ]);

    $response = $this->postJson('/api/v1/stats-user', $payload, apiHeaders($this->token));

    $response->assertOk()
        ->assertJson(['message' => 'Bots are not allowed.']);
});

test('tracking fields are accepted', function () {
    $payload = validUserPayload([
        'track_source' => 'google',
        'track_medium' => 'cpc',
        'track_campaign' => 'summer-sale',
        'track_term' => 'analytics',
        'track_content' => 'banner-ad',
        'track_referer' => 'https://google.com',
        'page_entry' => '/signup',
        'location_country' => 'US',
    ]);

    $this->postJson('/api/v1/stats-user', $payload, apiHeaders($this->token))->assertOk();

    setUserProjectContext($this->project);

    $statsUser = StatsUser::where('id', $payload['id'])->first();
    expect($statsUser)->not->toBeNull();
});

test('events_count_monthly is incremented on team via StatsObserver', function () {
    $team = $this->project->team;
    $initialCount = $team->events_count_monthly;

    $payload = validUserPayload();
    $this->postJson('/api/v1/stats-user', $payload, apiHeaders($this->token))->assertOk();

    $team->refresh();
    expect($team->events_count_monthly)->toBeGreaterThan($initialCount);
});
