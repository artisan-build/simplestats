<?php

namespace App\Listeners;

use App\Models\Project;
use App\Models\UserNotification;
use Laravel\Jetstream\Events\TeamMemberRemoved;

class DeleteTeamMemberNotifications
{
    public function handle(TeamMemberRemoved $event): void
    {
        UserNotification::whereBelongsTo($event->user)
            // The remover may manage a team other than their current one, so the team scope
            // (current team) must not narrow this down to nothing.
            ->whereHasMorph('notifiable', Project::class, fn ($query) => $query->withoutGlobalScope('team')->whereBelongsTo($event->team))
            ->delete();
    }
}
