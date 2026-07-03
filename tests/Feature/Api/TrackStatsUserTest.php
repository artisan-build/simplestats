<?php

use App\Enums\UserNotificationType;
use App\Helpers\AppHelper;
use App\Models\Project;
use App\Models\StatsLogin;
use App\Models\StatsUser;
use App\Models\User;
use App\Notifications\ProjectAlertRegistrationNotification;
use Database\Seeders\TestUserSeeder;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

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

/**
 * Simulates a parallel ingest worker winning the insert race (SIMPLESTATS-72): the
 * creating hook fires between the job's firstOrNew lookup and its insert, so the raw
 * insert lands exactly inside that window and the job's own insert hits the pkey.
 * Fires only once so the job's retry attempt goes through cleanly (in production the
 * winner's row survives, here it is rolled back together with the job's savepoint).
 */
function insertConflictingStatsUserRowOnCreating(string $userId, Project $project): void
{
    $fired = false;

    StatsUser::creating(function (StatsUser $statsUser) use ($userId, $project, &$fired) {
        if ($fired || (string) $statsUser->id !== $userId) {
            return;
        }

        $fired = true;

        DB::table('stats_users')->insert([
            'id' => $userId,
            'team_id' => $project->team_id,
            'project_id' => $project->id,
            'time' => now()->format('Y-m-d H:i:s'),
            'time_utc' => now()->format('Y-m-d H:i:s'),
            'registered' => false,
        ]);
    });
}

function enableRegistrationAlerts($project, $user): void
{
    $project->userNotifications()->updateOrCreate([
        'user_id' => $user->id,
        'type' => UserNotificationType::PROJECT_ALERT->value,
    ], [
        'settings' => ['tracking' => ['registrations' => true]],
    ]);
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

test('a concurrent insert between firstOrNew and save is retried instead of failing', function () {
    Notification::fake();
    enableRegistrationAlerts($this->project, $this->user);

    $payload = validUserPayload(['page_entry' => '/signup']);
    insertConflictingStatsUserRowOnCreating($payload['id'], $this->project);

    $this->postJson('/api/v1/stats-user', $payload, apiHeaders($this->token))->assertOk();

    setUserProjectContext($this->project);

    $users = StatsUser::where('id', $payload['id'])->get();
    expect($users)->toHaveCount(1)
        ->and($users->first()->registered)->toBeTrue()
        ->and($users->first()->stats_page_entry_id)->not->toBeNull();

    Notification::assertSentToTimes($this->user, ProjectAlertRegistrationNotification::class, 1);
});

test('a duplicate registration for an already registered user sends no second alert', function () {
    Notification::fake();
    enableRegistrationAlerts($this->project, $this->user);

    $userId = (string) fake()->unique()->randomNumber(5);

    $this->postJson('/api/v1/stats-user', validUserPayload(['id' => $userId]), apiHeaders($this->token))->assertOk();
    $this->postJson('/api/v1/stats-user', validUserPayload(['id' => $userId]), apiHeaders($this->token))->assertOk();

    setUserProjectContext($this->project);

    expect(StatsUser::where('id', $userId)->count())->toBe(1);

    Notification::assertSentToTimes($this->user, ProjectAlertRegistrationNotification::class, 1);
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

test('visitor_hash links the registration to its visitor', function () {
    $visitorId = DB::table('stats_visitors')->insertGetId([
        'team_id' => $this->project->team_id,
        'project_id' => $this->project->id,
        'visitor_hash' => 'hash-abc',
        'time' => now()->subDays(2)->format('Y-m-d H:i:s'),
        'time_utc' => now()->subDays(2)->format('Y-m-d H:i:s'),
    ]);

    $payload = validUserPayload(['visitor_hash' => 'hash-abc']);
    $this->postJson('/api/v1/stats-user', $payload, apiHeaders($this->token))->assertOk();

    setUserProjectContext($this->project);

    $statsUser = StatsUser::where('id', $payload['id'])->first();
    expect((int) $statsUser->stats_visitor_id)->toBe($visitorId);
});

test('unknown visitor_hash leaves the registration unlinked', function () {
    $payload = validUserPayload(['visitor_hash' => 'does-not-exist']);
    $this->postJson('/api/v1/stats-user', $payload, apiHeaders($this->token))->assertOk();

    setUserProjectContext($this->project);

    $statsUser = StatsUser::where('id', $payload['id'])->first();
    expect($statsUser->stats_visitor_id)->toBeNull();
});

test('events_count_monthly is incremented on team via StatsObserver', function () {
    $team = $this->project->team;
    $initialCount = $team->events_count_monthly;

    $payload = validUserPayload();
    $this->postJson('/api/v1/stats-user', $payload, apiHeaders($this->token))->assertOk();

    $team->refresh();
    expect($team->events_count_monthly)->toBeGreaterThan($initialCount);
});
