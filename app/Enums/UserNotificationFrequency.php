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
        return collect(self::cases())->map(fn (UserNotificationFrequency $frequency): array => [
            'id' => $frequency->value,
            'name' => $frequency->title(),
        ])->values()->all();
    }

    public function title(): string
    {
        return ucfirst($this->value);
    }
}
