<?php

namespace App\Listeners;

use App\Actions\CreateUserNotification;
use Laravel\Jetstream\Events\TeamMemberAdded;

class CreateTeamMemberNotifications
{
    public function handle(TeamMemberAdded $event): void
    {
        $event->team->projects->each(function ($project) use ($event) {
            app(CreateUserNotification::class)($event->user, $project);
        });
    }
}
