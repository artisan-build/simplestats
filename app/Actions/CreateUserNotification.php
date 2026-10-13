<?php

namespace App\Actions;

use App\Contracts\NotifiableToUser;
use App\Models\User;
use App\Models\UserNotification;
use App\ValueObjects\UserNotificationTypeObject;
use Illuminate\Database\Eloquent\Model;

class CreateUserNotification
{
    public function __invoke(User $user, NotifiableToUser|Model $notifiable): void
    {
        $notifiable->getDefaultUserNotificationTypes()
            ->each(function (UserNotificationTypeObject $notificationType) use ($user, $notifiable): void {
                UserNotification::firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'notifiable_id' => $notifiable->id,
                        'notifiable_type' => $notifiable::class,
                        'type' => $notificationType->type,
                    ],
                    [
                        'frequency' => $notificationType->frequency?->value,
                        'settings' => $notificationType->settings,
                    ],
                );
            });
    }
}
