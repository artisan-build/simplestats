<?php

namespace App\Observers;

use App\Actions\CreateUserNotification;
use App\Models\Project;
use App\Models\UserNotification;

class ProjectObserver
{
    /**
     * Handle the Project "created" event.
     */
    public function created(Project $project): void
    {
        Project::with(['team.users', 'team.owner'])->find($project->id)->team->allUsers()
            ->each(function ($user) use ($project) {
                app(CreateUserNotification::class)($user, $project);
            });
    }

    /**
     * Handle the Project "updated" event.
     */
    public function updated(Project $project): void
    {
        if ($project->wasChanged('usage')) {
            $allowedKeys = array_keys($project->getDefaultNotificationSettings());

            $project->userNotifications()->each(function (UserNotification $notification) use ($project, $allowedKeys) {
                $filteredSettings = collect($notification->settings)
                    ->only($allowedKeys)
                    ->toArray();

                // Falls neue Default-Werte dazukommen (z. B. wenn vorher keine Einstellung da war)
                $defaults = $project->getDefaultNotificationSettings();

                $notification->update([
                    'settings' => array_merge($defaults, $filteredSettings),
                ]);
            });
        }
    }

    /**
     * Handle the Project "deleted" event.
     */
    public function deleted(Project $project): void
    {
        $project->userNotifications()->delete();
    }
}
