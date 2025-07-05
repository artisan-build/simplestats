<?php

namespace App\Actions;

use App\Contracts\NotifiableToUser;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Database\Eloquent\Model;

class CreateUserNotification
{
    public function __invoke(User $user, NotifiableToUser|Model $notifiable): void
    {
        UserNotification::create([
            'user_id' => $user->id,
            'notifiable_id' => $notifiable->id,
            'notifiable_type' => $notifiable::class,
            'settings' => $notifiable->getDefaultNotificationSettings(),
        ]);
    }
}
