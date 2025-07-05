<?php

namespace App\Enums;

enum UserNotificationFrequency: string
{
    case MINUTELY = 'minutely';
    case DAILY = 'daily';
    case WEEKLY = 'weekly';
    case MONTHLY = 'monthly';
}
