<?php

use App\Enums\PaymentInterval;
use App\Helpers\AppHelper;
use App\Models\Project;
use App\Models\StatsPayment;
use App\Models\StatsUser;
use App\Models\StatsVisitor;
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

function validPaymentPayload(array $overrides = []): array
{
    return array_merge([
        'id' => fake()->uuid(),
        'time' => now()->format('Y-m-d H:i:s P'),
        'gross' => 9999,
        'net' => 8399,
        'currency' => 'EUR',
    ], $overrides);
}

function setPaymentProjectContext($project): void
{
    auth()->setUser($project);
    AppHelper::setTeamContextFromAuth($project);
    Context::add('project_id', $project->id);
    Context::add('request_url', '/api/v1/stats-payment');
}

test('store payment with auth and required fields returns 200', function () {
    $payload = validPaymentPayload();

    $response = $this->postJson(
        '/api/v1/stats-payment',
        $payload,
        apiHeaders($this->token),
    );

    $response->assertOk()
        ->assertJson([
            'message' => 'Stats Payment Request accepted.',
            'id' => $payload['id'],
        ]);
});

test('store payment without auth returns 401', function () {
    auth()->forgetGuards();

    $response = $this->postJson(
        '/api/v1/stats-payment',
        validPaymentPayload(),
        [
            'Authorization' => 'Bearer invalid-token',
            'Accept' => 'application/json',
        ],
    );

    $response->assertUnauthorized();
});

test('validation fails when id is missing', function () {
    $response = $this->postJson(
        '/api/v1/stats-payment',
        validPaymentPayload(['id' => null]),
        apiHeaders($this->token),
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('id');
});

test('validation fails when gross is missing', function () {
    $payload = validPaymentPayload();
    unset($payload['gross']);

    $response = $this->postJson(
        '/api/v1/stats-payment',
        $payload,
        apiHeaders($this->token),
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('gross');
});

test('validation fails when net is missing', function () {
    $payload = validPaymentPayload();
    unset($payload['net']);

    $response = $this->postJson(
        '/api/v1/stats-payment',
        $payload,
        apiHeaders($this->token),
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('net');
});

test('validation fails when currency is missing', function () {
    $payload = validPaymentPayload();
    unset($payload['currency']);

    $response = $this->postJson(
        '/api/v1/stats-payment',
        $payload,
        apiHeaders($this->token),
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('currency');
});

test('validation fails when currency is invalid', function () {
    $response = $this->postJson(
        '/api/v1/stats-payment',
        validPaymentPayload(['currency' => 'INVALID']),
        apiHeaders($this->token),
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('currency');
});

test('validation fails when time is missing', function () {
    $payload = validPaymentPayload();
    unset($payload['time']);

    $response = $this->postJson(
        '/api/v1/stats-payment',
        $payload,
        apiHeaders($this->token),
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('time');
});

test('payment is created with correct amounts', function () {
    $payload = validPaymentPayload([
        'gross' => 5000,
        'net' => 4200,
    ]);

    $this->postJson('/api/v1/stats-payment', $payload, apiHeaders($this->token))->assertOk();

    setPaymentProjectContext($this->project);

    $payment = StatsPayment::where('id', $payload['id'])->first();
    expect($payment)->not->toBeNull()
        ->and($payment->gross)->toBe(5000.0)
        ->and($payment->net)->toBe(4200.0);
});

test('recurring_interval is stored when provided', function () {
    $payload = validPaymentPayload(['recurring_interval' => 'month']);

    $this->postJson('/api/v1/stats-payment', $payload, apiHeaders($this->token))->assertOk();

    setPaymentProjectContext($this->project);

    $payment = StatsPayment::where('id', $payload['id'])->first();
    expect($payment)->not->toBeNull()
        ->and($payment->recurring_interval)->toBe(PaymentInterval::MONTH);
});

test('recurring_interval defaults to null for one-time payments', function () {
    $payload = validPaymentPayload();

    $this->postJson('/api/v1/stats-payment', $payload, apiHeaders($this->token))->assertOk();

    setPaymentProjectContext($this->project);

    $payment = StatsPayment::where('id', $payload['id'])->first();
    expect($payment)->not->toBeNull()
        ->and($payment->recurring_interval)->toBeNull();
});

test('validation fails when recurring_interval is invalid', function () {
    $response = $this->postJson(
        '/api/v1/stats-payment',
        validPaymentPayload(['recurring_interval' => 'weekly']),
        apiHeaders($this->token),
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('recurring_interval');
});

test('payment with stats_user_id is stored correctly and StatsUser is auto-created', function () {
    $statsUserId = (string) fake()->unique()->randomNumber(5);

    $payload = validPaymentPayload([
        'stats_user_id' => $statsUserId,
        'stats_user_time' => now()->subDay()->format('Y-m-d H:i:s P'),
    ]);

    $this->postJson('/api/v1/stats-payment', $payload, apiHeaders($this->token))->assertOk();

    setPaymentProjectContext($this->project);

    $payment = StatsPayment::where('id', $payload['id'])->first();
    expect($payment)->not->toBeNull()
        ->and($payment->stats_user_id)->toBe($statsUserId);

    $statsUser = StatsUser::where('id', $statsUserId)->first();
    expect($statsUser)->not->toBeNull();
});

test('payment with visitor_hash resolves to stats_visitor_id', function () {
    setPaymentProjectContext($this->project);

    $visitor = StatsVisitor::factory()->create(['project_id' => $this->project->id]);

    $payload = validPaymentPayload([
        'visitor_hash' => $visitor->visitor_hash,
    ]);

    $this->postJson('/api/v1/stats-payment', $payload, apiHeaders($this->token))->assertOk();

    $payment = StatsPayment::where('id', $payload['id'])->first();
    expect($payment)->not->toBeNull()
        ->and($payment->stats_visitor_id)->toBe($visitor->id);
});

test('duplicate id performs updateOrCreate update', function () {
    $paymentId = fake()->uuid();

    $payload1 = validPaymentPayload(['id' => $paymentId, 'gross' => 1000, 'net' => 800]);
    $this->postJson('/api/v1/stats-payment', $payload1, apiHeaders($this->token))->assertOk();

    $payload2 = validPaymentPayload(['id' => $paymentId, 'gross' => 2000, 'net' => 1600]);
    $this->postJson('/api/v1/stats-payment', $payload2, apiHeaders($this->token))->assertOk();

    setPaymentProjectContext($this->project);

    $payments = StatsPayment::where('id', $paymentId)->get();
    expect($payments)->toHaveCount(1);
});

test('counter increments per user', function () {
    setPaymentProjectContext($this->project);

    $statsUser = StatsUser::factory()->create(['project_id' => $this->project->id]);

    $payload1 = validPaymentPayload([
        'id' => 'pay-counter-1',
        'stats_user_id' => $statsUser->id,
        'stats_user_time' => now()->subDay()->format('Y-m-d H:i:s P'),
    ]);

    $this->postJson('/api/v1/stats-payment', $payload1, apiHeaders($this->token))->assertOk();

    $payment1 = StatsPayment::where('id', 'pay-counter-1')->first();
    expect($payment1->counter)->toBe(1);

    $payload2 = validPaymentPayload([
        'id' => 'pay-counter-2',
        'stats_user_id' => $statsUser->id,
        'stats_user_time' => now()->subDay()->format('Y-m-d H:i:s P'),
    ]);

    $this->postJson('/api/v1/stats-payment', $payload2, apiHeaders($this->token))->assertOk();

    $payment2 = StatsPayment::where('id', 'pay-counter-2')->first();
    expect($payment2->counter)->toBe(2);
});

test('events_count_monthly is incremented on team via StatsObserver', function () {
    $team = $this->project->team;
    $initialCount = $team->events_count_monthly;

    $payload = validPaymentPayload();
    $this->postJson('/api/v1/stats-payment', $payload, apiHeaders($this->token))->assertOk();

    $team->refresh();
    expect($team->events_count_monthly)->toBeGreaterThan($initialCount);
});
