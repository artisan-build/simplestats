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
            ->each(function (UserNotificationTypeObject $notificationType) use ($user, $notifiable) {
                UserNotification::create([
                    'user_id' => $user->id,
                    'notifiable_id' => $notifiable->id,
                    'notifiable_type' => $notifiable::class,
                    'frequency' => $notificationType->frequency?->value,
                    'type' => $notificationType->type,
                    'settings' => $notificationType->settings,
                ]);
            });
    }
}
