<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;

interface NotifiableToUser
{
    public function userNotifications(): MorphMany;

    public function getDefaultNotificationSettings(): array;
}
