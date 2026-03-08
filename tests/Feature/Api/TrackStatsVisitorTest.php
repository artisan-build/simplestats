<?php

use App\Helpers\AppHelper;
use App\Models\Project;
use App\Models\StatsVisitor;
use App\Models\User;
use Illuminate\Support\Facades\Context;

beforeEach(function () {
    $this->seed();
    $this->user = User::where('email', config('app.test.user')['email'])->firstOrFail();
    $this->project = Project::first();
    $this->token = $this->project->createToken('test-token')->plainTextToken;
});

function visitorApiHeaders(string $token): array
{
    return [
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
    ];
}

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
        visitorApiHeaders($this->token),
    );

    $response->assertOk()
        ->assertJson(['message' => 'Stats Visitor Request accepted.']);
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
        visitorApiHeaders($this->token),
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('time');
});

test('visitor is created via firstOrCreate using visitor_hash', function () {
    $payload = validVisitorPayload([
        'visitor_hash' => 'test-hash-'.fake()->uuid(),
    ]);

    $this->postJson('/api/v1/stats-visitor', $payload, visitorApiHeaders($this->token))->assertOk();

    setVisitorProjectContext($this->project);

    $visitor = StatsVisitor::where('visitor_hash', $payload['visitor_hash'])->first();
    expect($visitor)->not->toBeNull();
});

test('duplicate visitor_hash does not create a second record', function () {
    $visitorHash = 'duplicate-hash-'.fake()->uuid();

    $payload1 = validVisitorPayload(['visitor_hash' => $visitorHash]);
    $this->postJson('/api/v1/stats-visitor', $payload1, visitorApiHeaders($this->token))->assertOk();

    $payload2 = validVisitorPayload(['visitor_hash' => $visitorHash]);
    $this->postJson('/api/v1/stats-visitor', $payload2, visitorApiHeaders($this->token))->assertOk();

    setVisitorProjectContext($this->project);

    $visitors = StatsVisitor::where('visitor_hash', $visitorHash)->get();
    expect($visitors)->toHaveCount(1);
});

test('bot user agents are rejected with 200', function () {
    $payload = validVisitorPayload([
        'user_agent' => 'Googlebot/2.1 (+http://www.google.com/bot.html)',
    ]);

    $response = $this->postJson('/api/v1/stats-visitor', $payload, visitorApiHeaders($this->token));

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

    $this->postJson('/api/v1/stats-visitor', $payload, visitorApiHeaders($this->token))->assertOk();

    setVisitorProjectContext($this->project);

    $visitor = StatsVisitor::where('visitor_hash', $payload['visitor_hash'])->first();
    expect($visitor)->not->toBeNull();
});

test('events_count_monthly is incremented on team via StatsObserver', function () {
    $team = $this->project->team;
    $initialCount = $team->events_count_monthly;

    $payload = validVisitorPayload();
    $this->postJson('/api/v1/stats-visitor', $payload, visitorApiHeaders($this->token))->assertOk();

    $team->refresh();
    expect($team->events_count_monthly)->toBeGreaterThan($initialCount);
});
