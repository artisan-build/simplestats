<?php

use App\Helpers\AppHelper;
use App\Jobs\TrackStatsVisitor;
use App\Models\Project;
use App\Models\StatsVisitor;
use App\Models\User;
use Database\Seeders\TestUserSeeder;
use Illuminate\Support\Facades\Bus;
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

function validVisitorPayload(array $overrides = []): array
{
    return array_merge([
        'time' => now()->format('Y-m-d H:i:s P'),
        'ip' => fake()->ipv4(),
        'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
    ], $overrides);
}

function setVisitorProjectContext($project): void
{
    auth()->setUser($project);
    AppHelper::setTeamContextFromAuth($project);
    Context::add('project_id', $project->id);
    Context::add('request_url', '/api/v1/stats-visitor');
}

test('store visitor with auth and required fields returns 200', function () {
    $response = $this->postJson(
        '/api/v1/stats-visitor',
        validVisitorPayload(),
        apiHeaders($this->token),
    );

    $response->assertOk()
        ->assertJson(['message' => 'Stats Visitor Request accepted.'])
        ->assertJsonPath('visitor_hash', fn (mixed $hash) => is_string($hash) && $hash !== '');
});

test('store visitor without auth returns 401', function () {
    auth()->forgetGuards();

    $response = $this->postJson(
        '/api/v1/stats-visitor',
        validVisitorPayload(),
        [
            'Authorization' => 'Bearer invalid-token',
            'Accept' => 'application/json',
        ],
    );

    $response->assertUnauthorized();
});

test('validation fails when time is missing', function () {
    $payload = validVisitorPayload();
    unset($payload['time']);

    $response = $this->postJson(
        '/api/v1/stats-visitor',
        $payload,
        apiHeaders($this->token),
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('time');
});

test('visitor is created via firstOrCreate using visitor_hash', function () {
    $payload = validVisitorPayload([
        'visitor_hash' => 'test-hash-'.fake()->uuid(),
    ]);

    $this->postJson('/api/v1/stats-visitor', $payload, apiHeaders($this->token))
        ->assertOk()
        ->assertJson(['visitor_hash' => $payload['visitor_hash']]);

    setVisitorProjectContext($this->project);

    $visitor = StatsVisitor::where('visitor_hash', $payload['visitor_hash'])->first();
    expect($visitor)->not->toBeNull();
});

test('duplicate visitor_hash does not create a second record', function () {
    $visitorHash = 'duplicate-hash-'.fake()->uuid();

    $payload1 = validVisitorPayload(['visitor_hash' => $visitorHash]);
    $this->postJson('/api/v1/stats-visitor', $payload1, apiHeaders($this->token))->assertOk();

    $payload2 = validVisitorPayload(['visitor_hash' => $visitorHash]);
    $this->postJson('/api/v1/stats-visitor', $payload2, apiHeaders($this->token))->assertOk();

    setVisitorProjectContext($this->project);

    $visitors = StatsVisitor::where('visitor_hash', $visitorHash)->get();
    expect($visitors)->toHaveCount(1);
});

test('bot user agents are rejected with 200', function () {
    $payload = validVisitorPayload([
        'user_agent' => 'Googlebot/2.1 (+http://www.google.com/bot.html)',
    ]);

    $response = $this->postJson('/api/v1/stats-visitor', $payload, apiHeaders($this->token));

    $response->assertOk()
        ->assertJson(['message' => 'Bots are not allowed']);
});

test('tracking fields are accepted', function () {
    $payload = validVisitorPayload([
        'visitor_hash' => 'tracking-hash-'.fake()->uuid(),
        'track_source' => 'google',
        'track_medium' => 'cpc',
        'track_campaign' => 'summer-sale',
        'track_term' => 'analytics',
        'track_content' => 'banner-ad',
        'track_referer' => 'https://google.com',
        'page_entry' => '/landing-page',
        'location_country' => 'DE',
    ]);

    $this->postJson('/api/v1/stats-visitor', $payload, apiHeaders($this->token))->assertOk();

    setVisitorProjectContext($this->project);

    $visitor = StatsVisitor::where('visitor_hash', $payload['visitor_hash'])->first();
    expect($visitor)->not->toBeNull();
});

test('events_count_monthly is incremented on team via StatsObserver', function () {
    $team = $this->project->team;
    $initialCount = $team->events_count_monthly;

    $payload = validVisitorPayload();
    $this->postJson('/api/v1/stats-visitor', $payload, apiHeaders($this->token))->assertOk();

    $team->refresh();
    expect($team->events_count_monthly)->toBeGreaterThan($initialCount);
});

test('events_count_monthly is incremented on project via StatsObserver', function () {
    $project = $this->project;
    $initialCount = $project->events_count_monthly;

    $payload = validVisitorPayload();
    $this->postJson('/api/v1/stats-visitor', $payload, apiHeaders($this->token))->assertOk();

    $project->refresh();
    expect($project->events_count_monthly)->toBeGreaterThan($initialCount);
});

test('reaching the monthly event limit returns a terminal 200 and does not dispatch the tracking job', function () {
    Bus::fake();

    $team = $this->project->team;
    $limit = $team->owner->sparkPlan()->options['Events'];
    $team->update(['events_count_monthly' => $limit]);

    $response = $this->postJson('/api/v1/stats-visitor', validVisitorPayload(), apiHeaders($this->token));

    $response->assertOk()->assertJson(['message' => 'Monthly event limit reached.']);
    Bus::assertNotDispatched(TrackStatsVisitor::class);
});

test('deleting a stat decrements the event counters', function () {
    $payload = validVisitorPayload(['visitor_hash' => 'delete-hash-'.fake()->uuid()]);
    $this->postJson('/api/v1/stats-visitor', $payload, apiHeaders($this->token))->assertOk();

    setVisitorProjectContext($this->project);

    $team = $this->project->team->refresh();
    $project = $this->project->fresh();

    $teamMonthlyBefore = $team->events_count_monthly;
    $teamOverallBefore = $team->events_count_overall;
    $projectMonthlyBefore = $project->events_count_monthly;
    expect($teamMonthlyBefore)->toBeGreaterThan(0);

    StatsVisitor::where('visitor_hash', $payload['visitor_hash'])->firstOrFail()->delete();

    $team->refresh();
    $project->refresh();
    expect($team->events_count_monthly)->toBe($teamMonthlyBefore - 1);
    expect($team->events_count_overall)->toBe($teamOverallBefore - 1);
    expect($project->events_count_monthly)->toBe($projectMonthlyBefore - 1);
});

test('deleting a stat never pushes a counter below zero', function () {
    $payload = validVisitorPayload(['visitor_hash' => 'floor-hash-'.fake()->uuid()]);
    $this->postJson('/api/v1/stats-visitor', $payload, apiHeaders($this->token))->assertOk();

    setVisitorProjectContext($this->project);

    $team = $this->project->team;
    $team->update(['events_count_monthly' => 0, 'events_count_overall' => 0]);
    $this->project->update(['events_count_monthly' => 0]);

    StatsVisitor::where('visitor_hash', $payload['visitor_hash'])->firstOrFail()->delete();

    $team->refresh();
    expect($team->events_count_monthly)->toBe(0);
    expect($team->events_count_overall)->toBe(0);
    expect($this->project->fresh()->events_count_monthly)->toBe(0);
});
