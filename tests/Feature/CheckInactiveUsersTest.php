<?php

use App\Models\Team;
use App\Models\User;
use App\Notifications\UserDeletedNotification;
use App\Notifications\UserInactiveNotification;
use Illuminate\Support\Facades\Notification;

it('marks inactive users for deletion after the 10 day warning period', function () {
    Notification::fake();

    $user = User::factory()->withPersonalTeam()->create([
        'inactive_since' => now()->subDays(11),
    ]);
    $personalTeam = $user->personalTeam();

    $this->artisan('app:check-inactive-users');

    // Soft mark only: user and their data survive the grace window.
    expect($user->fresh())->not->toBeNull();
    expect($user->fresh()->deleted_at)->not->toBeNull();
    expect($personalTeam->fresh())->not->toBeNull();
    Notification::assertSentTo($user, UserDeletedNotification::class);
});

it('does not re-notify users already marked for deletion', function () {
    Notification::fake();

    $user = User::factory()->withPersonalTeam()->create([
        'inactive_since' => now()->subDays(11),
        'deleted_at' => now()->subDay(),
    ]);

    $this->artisan('app:check-inactive-users');

    Notification::assertNotSentTo($user, UserDeletedNotification::class);
    Notification::assertNotSentTo($user, UserInactiveNotification::class);
});

it('does not mark users with active subscriptions', function () {
    Notification::fake();

    $user = User::factory()->withPersonalTeam()->create([
        'inactive_since' => now()->subDays(11),
    ]);
    $user->subscriptions()->create([
        'type' => 'default',
        'paddle_id' => 'sub_test',
        'status' => 'active',
    ]);

    $this->artisan('app:check-inactive-users');

    expect($user->fresh()->deleted_at)->toBeNull();
    Notification::assertNotSentTo($user, UserDeletedNotification::class);
});

it('sends 10 day warning to users inactive for 50+ days', function () {
    Notification::fake();

    $user = User::factory()->withPersonalTeam()->create([
        'last_login_at' => now()->subDays(51),
        'inactive_since' => null,
    ]);

    $this->artisan('app:check-inactive-users');

    expect($user->fresh()->inactive_since)->not->toBeNull();
    Notification::assertSentTo($user, UserInactiveNotification::class);
});

it('sends 1 day final warning to users warned 9+ days ago', function () {
    Notification::fake();

    $user = User::factory()->withPersonalTeam()->create([
        'inactive_since' => now()->subDays(9)->subHour(),
        'last_login_at' => now()->subDays(60),
    ]);

    $this->artisan('app:check-inactive-users');

    // Should receive final warning but NOT be marked (only after 10 days)
    expect($user->fresh()->deleted_at)->toBeNull();
    Notification::assertSentTo($user, UserInactiveNotification::class);
});

it('middleware switches user to personal team when current team owner is missing', function () {
    $owner = User::factory()->withPersonalTeam()->create();
    $sharedTeam = Team::factory()->create([
        'user_id' => $owner->id,
        'personal_team' => false,
    ]);

    $member = User::factory()->withPersonalTeam()->create([
        'current_team_id' => $sharedTeam->id,
    ]);
    $sharedTeam->users()->attach($member, ['role' => 'editor']);
    $personalTeam = $member->personalTeam();

    // Simulate owner deletion without proper cleanup (the bug scenario)
    $owner->forceDelete();

    $this->actingAs($member);
    $this->get('/dashboard');

    expect($member->fresh()->current_team_id)->toBe($personalTeam->id);
});
