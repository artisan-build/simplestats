<?php

use App\Models\Project;
use App\Models\User;
use App\Models\UserNotification;

beforeEach(function (): void {
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->user->currentTeam->update(['currency' => 'EUR']);
    // Creating a project triggers ProjectObserver, which creates UserNotification rows for each team user.
    Project::factory()->create(['team_id' => $this->user->currentTeam->id]);
    $this->actingAs($this->user);
});

test('notifications index route returns 200', function (): void {
    $this->get(route('notifications.index'))->assertOk();
});

test('notifications index renders Notifications/Index with expected props', function (): void {
    $this->get(route('notifications.index'))
        ->assertInertia(fn ($page) => $page
            ->component('Notifications/Index')
            ->has('notifications')
            ->has('notificationTypes')
            ->has('notificationStatsTypes')
            ->has('notificationFrequencies')
        );
});

test('notifications index includes notifications created by the project observer', function (): void {
    $count = UserNotification::where('user_id', $this->user->id)->count();
    expect($count)->toBeGreaterThan(0);

    $this->get(route('notifications.index'))
        ->assertInertia(fn ($page) => $page
            ->component('Notifications/Index')
            ->has('notifications')
        );
});
