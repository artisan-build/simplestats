<?php

namespace App\Observers;

use App\Actions\CreateUserNotification;
use App\Models\Project;
use App\Models\UserNotification;
use App\ValueObjects\UserNotificationTypeObject;

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
            //dd($project->getDefaultUserNotificationTypes());
            $project->getDefaultUserNotificationTypes()->each(
                function (UserNotificationTypeObject $notificationType) use ($project) {
                    $allowedKeys = array_keys($notificationType->settings['tracking']);

                    $project->userNotifications()->where('type', $notificationType->type)
                        ->each(function (UserNotification $notification) use ($allowedKeys, $notificationType) {
                            $filteredSettings = collect($notification->settings)
                                ->only($allowedKeys)
                                ->toArray();

                            $notification->update([
                                'settings->tracking' => array_merge($notificationType->settings['tracking'], $filteredSettings),
                            ]);
                        });
                }
            );
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
