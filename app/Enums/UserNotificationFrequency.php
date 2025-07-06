<?php

namespace App\Enums;

use LaracraftTech\LaravelUsefulAdditions\Traits\UsefulEnums;

enum UserNotificationFrequency: string
{
    use UsefulEnums;

    case MINUTELY = 'minutely';
    case DAILY = 'daily';
    case WEEKLY = 'weekly';
    case MONTHLY = 'monthly';
    case DISABLED = 'disabled';

    public static function toOptions(): array
    {
        return collect(self::array())->map(function ($frequency) {
            return [
                'id' => $frequency,
                'name' => ucfirst($frequency),
            ];
        })->values()->toArray();
    }
}
