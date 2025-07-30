<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

interface NotifiableToUser
{
    public function userNotifications(): MorphMany;

    public function getDefaultUserNotificationTypes(): Collection;
}
