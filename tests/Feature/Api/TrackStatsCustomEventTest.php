<?php

use App\Helpers\AppHelper;
use App\Models\Project;
use App\Models\StatsCustomEvent;
use App\Models\StatsCustomEventName;
use App\Models\StatsUser;
use App\Models\StatsVisitor;
use App\Models\User;
use Illuminate\Support\Facades\Context;

beforeEach(function () {
    $this->seed();
    $this->user = User::where('email', config('app.test.user')['email'])->firstOrFail();
    $this->project = Project::first();
    $this->token = $this->project->createToken('test-token')->plainTextToken;
});

function apiHeaders(string $token): array
{
    return [
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
    ];
}

function validPayload(array $overrides = []): array
{
    return array_merge([
        'id' => fake()->uuid(),
        'name' => 'Button Clicked',
        'time' => now()->format('Y-m-d H:i:s P'),
    ], $overrides);
}

function setProjectContext($project): void
{
    auth()->setUser($project);
    AppHelper::setTeamContextFromAuth($project);
    Context::add('project_id', $project->id);
    Context::add('request_url', '/api/v1/stats-custom-event');
}

test('store custom event with auth and required fields returns 200', function () {
    $response = $this->postJson(
        '/api/v1/stats-custom-event',
        validPayload(),
        apiHeaders($this->token),
    );

    $response->assertOk()
        ->assertJson(['message' => 'Stats Custom Event Request accepted.']);
});

test('store custom event without auth returns 401', function () {
    auth()->forgetGuards();

    $response = $this->postJson(
        '/api/v1/stats-custom-event',
        validPayload(),
        [
            'Authorization' => 'Bearer invalid-token',
            'Accept' => 'application/json',
        ],
    );

    $response->assertUnauthorized();
});

test('validation fails when id is missing', function () {
    $response = $this->postJson(
        '/api/v1/stats-custom-event',
        validPayload(['id' => null]),
        apiHeaders($this->token),
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('id');
});

test('validation fails when name is missing', function () {
    $response = $this->postJson(
        '/api/v1/stats-custom-event',
        validPayload(['name' => null]),
        apiHeaders($this->token),
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('name');
});

test('validation fails when time is missing', function () {
    $payload = validPayload();
    unset($payload['time']);

    $response = $this->postJson(
        '/api/v1/stats-custom-event',
        $payload,
        apiHeaders($this->token),
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('time');
});

test('event with stats_user_id is stored correctly and StatsUser is auto-created', function () {
    $statsUserId = '99999';

    $payload = validPayload([
        'stats_user_id' => $statsUserId,
        'stats_user_time' => now()->subDay()->format('Y-m-d H:i:s P'),
    ]);

    $this->postJson(
        '/api/v1/stats-custom-event',
        $payload,
        apiHeaders($this->token),
    )->assertOk();

    setProjectContext($this->project);

    $event = StatsCustomEvent::where('id', $payload['id'])->first();
    expect($event)->not->toBeNull()
        ->and($event->stats_user_id)->toBe($statsUserId);

    $statsUser = StatsUser::where('id', $statsUserId)->first();
    expect($statsUser)->not->toBeNull();
});

test('event with visitor_hash resolves to stats_visitor_id', function () {
    setProjectContext($this->project);

    $visitor = StatsVisitor::first();

    $payload = validPayload([
        'visitor_hash' => $visitor->visitor_hash,
    ]);

    $this->postJson(
        '/api/v1/stats-custom-event',
        $payload,
        apiHeaders($this->token),
    )->assertOk();

    $event = StatsCustomEvent::where('id', $payload['id'])->first();
    expect($event)->not->toBeNull()
        ->and($event->stats_visitor_id)->toBe($visitor->id);
});

test('duplicate id performs updateOrCreate update', function () {
    $eventId = fake()->uuid();

    $payload1 = validPayload(['id' => $eventId, 'name' => 'First Name']);
    $this->postJson('/api/v1/stats-custom-event', $payload1, apiHeaders($this->token))->assertOk();

    $payload2 = validPayload(['id' => $eventId, 'name' => 'Updated Name']);
    $this->postJson('/api/v1/stats-custom-event', $payload2, apiHeaders($this->token))->assertOk();

    setProjectContext($this->project);

    $events = StatsCustomEvent::where('id', $eventId)->get();
    expect($events)->toHaveCount(1);
});

test('counter increments per user', function () {
    $statsUser = StatsUser::first();

    $payload1 = validPayload([
        'id' => 'counter-1',
        'stats_user_id' => $statsUser->id,
        'stats_user_time' => now()->subDay()->format('Y-m-d H:i:s P'),
    ]);

    $this->postJson('/api/v1/stats-custom-event', $payload1, apiHeaders($this->token))->assertOk();

    setProjectContext($this->project);

    $event1 = StatsCustomEvent::where('id', 'counter-1')->first();
    expect($event1->counter)->toBe(1);

    $payload2 = validPayload([
        'id' => 'counter-2',
        'stats_user_id' => $statsUser->id,
        'stats_user_time' => now()->subDay()->format('Y-m-d H:i:s P'),
    ]);

    $this->postJson('/api/v1/stats-custom-event', $payload2, apiHeaders($this->token))->assertOk();

    $event2 = StatsCustomEvent::where('id', 'counter-2')->first();
    expect($event2->counter)->toBe(2);
});

test('event name lookup creates new entry and reuses existing', function () {
    $eventName = 'Unique Event '.fake()->word();

    $payload1 = validPayload(['id' => fake()->uuid(), 'name' => $eventName]);
    $this->postJson('/api/v1/stats-custom-event', $payload1, apiHeaders($this->token))->assertOk();

    setProjectContext($this->project);

    $nameCount = StatsCustomEventName::where('name', $eventName)->count();
    expect($nameCount)->toBe(1);

    $payload2 = validPayload(['id' => fake()->uuid(), 'name' => $eventName]);
    $this->postJson('/api/v1/stats-custom-event', $payload2, apiHeaders($this->token))->assertOk();

    $nameCount = StatsCustomEventName::where('name', $eventName)->count();
    expect($nameCount)->toBe(1);

    $event1 = StatsCustomEvent::where('id', $payload1['id'])->first();
    $event2 = StatsCustomEvent::where('id', $payload2['id'])->first();
    expect($event1->stats_custom_event_name_id)->toBe($event2->stats_custom_event_name_id);
});

test('events_count_monthly is incremented on team via StatsObserver', function () {
    $team = $this->project->team;
    $initialCount = $team->events_count_monthly;

    $payload = validPayload();
    $this->postJson('/api/v1/stats-custom-event', $payload, apiHeaders($this->token))->assertOk();

    $team->refresh();
    expect($team->events_count_monthly)->toBeGreaterThan($initialCount);
});
