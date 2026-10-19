<?php

use App\Enums\UserNotificationFrequency;
use App\Enums\UserNotificationType;
use App\Models\Project;
use App\Models\User;
use App\Notifications\ProjectReportNotification;
use Illuminate\Support\Facades\Notification;

beforeEach(function (): void {
    Notification::fake();

    $this->owner = User::factory()->withPersonalTeam()->create();
    $this->member = User::factory()->create();
    $this->project = Project::factory()->create(['team_id' => $this->owner->currentTeam->id]);

    foreach ([$this->owner, $this->member] as $user) {
        $this->project->userNotifications()->updateOrCreate([
            'user_id' => $user->id,
            'type' => UserNotificationType::PROJECT_REPORT->value,
        ], [
            'frequency' => UserNotificationFrequency::DAILY,
            'settings' => ['tracking' => ['visitors' => true]],
        ]);
    }
});

test('reports skip notifications of soft-deleted users and still reach the others', function (): void {
    $this->owner->delete();

    $this->artisan('app:send-project-reports')->assertSuccessful();

    Notification::assertSentTo($this->member, ProjectReportNotification::class);
    Notification::assertNotSentTo($this->owner, ProjectReportNotification::class);
});
