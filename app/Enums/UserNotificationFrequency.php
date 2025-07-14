<?php

namespace App\Enums;

use LaracraftTech\LaravelUsefulAdditions\Traits\UsefulEnums;

enum UserNotificationFrequency: string
{
    use UsefulEnums;

    case DAILY = 'daily';
    case WEEKLY = 'weekly';
    case MONTHLY = 'monthly';
    case DISABLED = 'disabled';

    public static function toOptions(): array
    {
        return collect(self::cases())->map(function (UserNotificationFrequency $frequency) {
            return [
                'id' => $frequency->value,
                'name' => $frequency->title(),
            ];
        })->values()->toArray();
    }

    public function title(): string
    {
        return ucfirst($this->value);
    }
}
