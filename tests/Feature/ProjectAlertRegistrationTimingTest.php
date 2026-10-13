<?php

use App\Enums\UserNotificationType;
use App\Jobs\TrackStatsLogin;
use App\Jobs\TrackStatsUser;
use App\Models\Project;
use App\Models\StatsUser;
use App\Models\User;
use App\Notifications\ProjectAlertRegistrationNotification;
use Database\Seeders\TestUserSeeder;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Notification;

// SSO sign-up dispatches the registration (stats-user) and the login (stats-login) in the same
// OAuth callback, so both ingest jobs race on the queue. When the login wins, its
// HasStatsUserAssurance pre-creates an empty StatsUser shell on which the registration alert used
// to fire with no tracking data. Real incident: user 409 (project_id 1), StatsLogin counter=1
// shares the exact second of the registration.
beforeEach(function (): void {
    $this->seed(TestUserSeeder::class);
    $this->user = User::where('email', config('app.test.user')['email'])->firstOrFail();
    $this->project = Project::first();
    auth()->forgetGuards();

    $this->project->userNotifications()->updateOrCreate([
        'user_id' => $this->user->id,
        'type' => UserNotificationType::PROJECT_ALERT->value,
    ], [
        'settings' => ['tracking' => ['registrations' => true]],
    ]);

    // request_url must contain "api/" so HasProjectScope filters StatsUser lookups by project
    // (in production SetRequestContext sets it and Context propagates it into the queued job).
    Context::add('project_id', $this->project->id);
    Context::add('request_url', '/api/v1/stats-user');
});

function runLoginIngest(string $statsUserId): void
{
    (new TrackStatsLogin([
        'stats_user_id' => $statsUserId,
        'stats_user_time' => now()->format('Y-m-d H:i:s P'),
        'ip' => '8.8.8.8',
        'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36',
        'time' => now()->format('Y-m-d H:i:s P'),
    ]))->handle();
}

function runRegistrationIngest(string $statsUserId): void
{
    (new TrackStatsUser([
        'id' => $statsUserId,
        'ip' => '8.8.8.8',
        'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36',
        'track_referer' => 'google.com',
        'page_entry' => '/landing',
        'add_login' => false,
        'time' => now()->format('Y-m-d H:i:s P'),
    ]))->handle();
}

test('registration alert carries the tracking codes even when the login ingest wins the race', function (): void {
    Notification::fake();

    runLoginIngest('900001');
    runRegistrationIngest('900001');

    $statsUser = StatsUser::where('id', '900001')->firstOrFail();
    expect($statsUser->stats_page_entry_id)->not->toBeNull()
        ->and($statsUser->stats_track_referer_id)->not->toBeNull();

    Notification::assertSentTo($this->user, ProjectAlertRegistrationNotification::class, function ($notification): bool {
        $notification->statsUser->load(StatsUser::statsRelations());

        return $notification->statsUser->stats_page_entry_id !== null
            && $notification->statsUser->stats_track_referer_id !== null;
    });
});

test('registration alert carries the tracking codes when the registration ingest wins the race', function (): void {
    Notification::fake();

    // control: registration first (the non-SSO ordering, like user 408)
    runRegistrationIngest('900002');
    runLoginIngest('900002');

    Notification::assertSentTo($this->user, ProjectAlertRegistrationNotification::class, function ($notification): bool {
        $notification->statsUser->load(StatsUser::statsRelations());

        return $notification->statsUser->stats_page_entry_id !== null
            && $notification->statsUser->stats_track_referer_id !== null;
    });
});

test('the registration alert is sent exactly once across the racing ingests', function (): void {
    Notification::fake();

    runLoginIngest('900003');
    runRegistrationIngest('900003');

    Notification::assertSentToTimes($this->user, ProjectAlertRegistrationNotification::class, 1);
});

test('a login for a never-registered user does not send a registration alert', function (): void {
    Notification::fake();

    runLoginIngest('900004');

    Notification::assertNotSentTo($this->user, ProjectAlertRegistrationNotification::class);

    expect(StatsUser::where('id', '900004')->value('registered'))->toBeFalse();
});
