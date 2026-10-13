<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    config(['session.driver' => 'database']);
});

test('profile sessions exclude impersonation sessions and re-index correctly', function (): void {
    $user = User::factory()->withPersonalTeam()->create();

    $normalPayload = base64_encode(serialize(['foo' => 'bar']));
    $impersonatingPayload = base64_encode(serialize([
        'foo' => 'bar',
        'impersonated_by' => 999,
    ]));

    DB::table('sessions')->insert([
        [
            'id' => 'impersonation-session-id',
            'user_id' => $user->id,
            'ip_address' => '10.0.0.1',
            'user_agent' => 'Mozilla/5.0',
            'payload' => $impersonatingPayload,
            'last_activity' => now()->timestamp,
        ],
        [
            'id' => 'normal-session-id',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0',
            'payload' => $normalPayload,
            'last_activity' => now()->subSeconds(10)->timestamp,
        ],
    ]);

    $this->actingAs($user)
        ->get('/user/profile')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('sessions', 1)
            ->where('sessions.0.ip_address', '127.0.0.1')
        );
});

test('profile sessions show all sessions when none are impersonating', function (): void {
    $user = User::factory()->withPersonalTeam()->create();

    $payload = base64_encode(serialize(['foo' => 'bar']));

    DB::table('sessions')->insert([
        [
            'id' => 'session-1',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0',
            'payload' => $payload,
            'last_activity' => now()->timestamp,
        ],
        [
            'id' => 'session-2',
            'user_id' => $user->id,
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0',
            'payload' => $payload,
            'last_activity' => now()->subMinutes(5)->timestamp,
        ],
    ]);

    $this->actingAs($user)
        ->get('/user/profile')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('sessions', 2)
        );
});
