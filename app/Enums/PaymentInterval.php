<?php

namespace App\Enums;

use LaracraftTech\LaravelUsefulAdditions\Traits\UsefulEnums;

enum PaymentInterval: string
{
    use UsefulEnums;

    case MONTH = 'month';
    case YEAR = 'year';

    public static function toOptions(): array
    {
        return collect(self::cases())->map(function (PaymentInterval $interval) {
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
