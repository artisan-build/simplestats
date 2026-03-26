<?php

use App\Models\Team;
use App\Models\User;
use App\Notifications\UserDeletedNotification;
use App\Notifications\UserInactiveNotification;
use Illuminate\Support\Facades\Notification;

it('deletes inactive users after 10 days warning period', function () {
    Notification::fake();

    $user = User::factory()->withPersonalTeam()->create([
        'inactive_since' => now()->subDays(11),
    ]);

    $this->artisan('app:check-inactive-users');

    expect($user->fresh())->toBeNull();
    Notification::assertSentTo($user, UserDeletedNotification::class);
});

it('deletes owned teams when inactive user is deleted', function () {
    Notification::fake();

    $owner = User::factory()->withPersonalTeam()->create([
        'inactive_since' => now()->subDays(11),
    ]);
    $personalTeam = $owner->personalTeam();

    $this->artisan('app:check-inactive-users');

    expect($owner->fresh())->toBeNull();
    expect($personalTeam->fresh())->toBeNull();
});

it('removes deleted user from other teams and cleans up the team', function () {
    Notification::fake();

    $teamOwner = User::factory()->withPersonalTeam()->create();
    $sharedTeam = Team::factory()->create([
        'user_id' => $teamOwner->id,
        'personal_team' => false,
    ]);

    $member = User::factory()->withPersonalTeam()->create([
        'inactive_since' => now()->subDays(11),
        'current_team_id' => $sharedTeam->id,
    ]);
    $sharedTeam->users()->attach($member, ['role' => 'editor']);

    $this->artisan('app:check-inactive-users');

    expect($member->fresh())->toBeNull();
    expect($sharedTeam->fresh()->users)->toHaveCount(0);
    expect($teamOwner->fresh())->not->toBeNull();
});

it('deletes owned team and detaches members when owner becomes inactive', function () {
    Notification::fake();

    $owner = User::factory()->withPersonalTeam()->create([
        'inactive_since' => now()->subDays(11),
    ]);
    $sharedTeam = Team::factory()->create([
        'user_id' => $owner->id,
        'personal_team' => false,
    ]);

    $member = User::factory()->withPersonalTeam()->create();
    $sharedTeam->users()->attach($member, ['role' => 'editor']);
    $member->update(['current_team_id' => $sharedTeam->id]);

    $this->artisan('app:check-inactive-users');

    expect($owner->fresh())->toBeNull();
    expect($sharedTeam->fresh())->toBeNull();
    expect($member->fresh())->not->toBeNull();
    expect($member->fresh()->teams)->toHaveCount(0);
});

it('does not delete users with active subscriptions', function () {
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

    expect($user->fresh())->not->toBeNull();
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

    // Should receive final warning but NOT be deleted (only after 10 days)
    expect($user->fresh())->not->toBeNull();
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
