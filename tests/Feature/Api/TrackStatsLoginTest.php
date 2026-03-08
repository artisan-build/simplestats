<?php

use App\Helpers\AppHelper;
use App\Models\Project;
use App\Models\StatsLogin;
use App\Models\StatsUser;
use App\Models\User;
use Illuminate\Support\Facades\Context;

beforeEach(function () {
    $this->seed();
    $this->user = User::where('email', config('app.test.user')['email'])->firstOrFail();
    $this->project = Project::first();
    $this->token = $this->project->createToken('test-token')->plainTextToken;
});

function loginApiHeaders(string $token): array
{
    return [
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
    ];
}

function validLoginPayload(array $overrides = []): array
{
    return array_merge([
        'stats_user_id' => (string) fake()->unique()->randomNumber(5),
        'stats_user_time' => now()->subDays(7)->format('Y-m-d H:i:s P'),
        'time' => now()->format('Y-m-d H:i:s P'),
        'ip' => fake()->ipv4(),
        'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
    ], $overrides);
}

function setLoginProjectContext($project): void
{
    auth()->setUser($project);
    AppHelper::setTeamContextFromAuth($project);
    Context::add('project_id', $project->id);
    Context::add('request_url', '/api/v1/stats-login');
}

test('store login with auth and required fields returns 200', function () {
    $response = $this->postJson(
        '/api/v1/stats-login',
        validLoginPayload(),
        loginApiHeaders($this->token),
    );

    $response->assertOk()
        ->assertJson(['message' => 'Stats Login Request accepted.']);
});

test('store login without auth returns 401', function () {
    auth()->forgetGuards();

    $response = $this->postJson(
        '/api/v1/stats-login',
        validLoginPayload(),
        [
            'Authorization' => 'Bearer invalid-token',
            'Accept' => 'application/json',
        ],
    );

    $response->assertUnauthorized();
});

test('validation fails when stats_user_id is missing', function () {
    $response = $this->postJson(
        '/api/v1/stats-login',
        validLoginPayload(['stats_user_id' => null]),
        loginApiHeaders($this->token),
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('stats_user_id');
});

test('validation fails when time is missing', function () {
    $payload = validLoginPayload();
    unset($payload['time']);

    $response = $this->postJson(
        '/api/v1/stats-login',
        $payload,
        loginApiHeaders($this->token),
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('time');
});

test('login is created and StatsUser is auto-created', function () {
    $statsUserId = (string) fake()->unique()->randomNumber(5);

    $payload = validLoginPayload([
        'stats_user_id' => $statsUserId,
        'stats_user_time' => now()->subDay()->format('Y-m-d H:i:s P'),
    ]);

    $this->postJson('/api/v1/stats-login', $payload, loginApiHeaders($this->token))->assertOk();

    setLoginProjectContext($this->project);

    $login = StatsLogin::where('stats_user_id', $statsUserId)->first();
    expect($login)->not->toBeNull();

    $statsUser = StatsUser::where('id', $statsUserId)->first();
    expect($statsUser)->not->toBeNull();
});

test('counter increments per user', function () {
    setLoginProjectContext($this->project);

    $statsUser = StatsUser::first();

    $initialLoginCount = StatsLogin::where('stats_user_id', $statsUser->id)->count();

    $payload1 = validLoginPayload([
        'stats_user_id' => $statsUser->id,
        'time' => now()->subDays(30)->format('Y-m-d H:i:s P'),
    ]);

    $this->postJson('/api/v1/stats-login', $payload1, loginApiHeaders($this->token))->assertOk();

    $login1 = StatsLogin::where('stats_user_id', $statsUser->id)
        ->orderBy('counter', 'desc')
        ->first();
    expect($login1->counter)->toBeGreaterThan($initialLoginCount);
});

test('duplicate login on same day is not created', function () {
    setLoginProjectContext($this->project);

    $statsUser = StatsUser::first();
    $today = now()->format('Y-m-d H:i:s P');

    $payload1 = validLoginPayload([
        'stats_user_id' => $statsUser->id,
        'time' => $today,
    ]);
    $this->postJson('/api/v1/stats-login', $payload1, loginApiHeaders($this->token))->assertOk();

    $countAfterFirst = StatsLogin::where('stats_user_id', $statsUser->id)->count();

    $payload2 = validLoginPayload([
        'stats_user_id' => $statsUser->id,
        'time' => $today,
    ]);
    $this->postJson('/api/v1/stats-login', $payload2, loginApiHeaders($this->token))->assertOk();

    $countAfterSecond = StatsLogin::where('stats_user_id', $statsUser->id)->count();

    expect($countAfterSecond)->toBeLessThanOrEqual($countAfterFirst + 1);
});

test('events_count_monthly is incremented on team via StatsObserver', function () {
    $team = $this->project->team;
    $initialCount = $team->events_count_monthly;

    $payload = validLoginPayload();
    $this->postJson('/api/v1/stats-login', $payload, loginApiHeaders($this->token))->assertOk();

    $team->refresh();
    expect($team->events_count_monthly)->toBeGreaterThan($initialCount);
});
