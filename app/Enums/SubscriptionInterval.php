<?php

namespace App\Enums;

use LaracraftTech\LaravelUsefulAdditions\Traits\UsefulEnums;

enum SubscriptionInterval: string
{
    use UsefulEnums;

    case MONTH = 'month';
    case YEAR = 'year';

    public static function toOptions(): array
    {
        return collect(self::cases())->map(function (SubscriptionInterval $interval) {
            return [
                'id' => $interval->value,
                'name' => $interval->title(),
            ];
        })->values()->toArray();
    }

    public function title(): string
    {
        return ucfirst($this->value);
    }
}
