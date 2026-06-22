<?php

namespace App\Enums;

use LaracraftTech\LaravelUsefulAdditions\Traits\UsefulEnums;

enum DashboardPreset: string
{
    use UsefulEnums;

    case SIMPLE = 'simple';
    case ADVANCED = 'advanced';
    case EXPERT = 'expert';

    public static function toOptions(): array
    {
        return collect(self::cases())->map(function (DashboardPreset $preset) {
            return [
                'id' => $preset->value,
                'name' => $preset->title(),
            ];
        })->values()->toArray();
    }

    public function title(): string
    {
        return ucfirst($this->value);
    }
}
