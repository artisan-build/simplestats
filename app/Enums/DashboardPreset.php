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
        return collect(self::cases())->map(fn (DashboardPreset $preset): array => [
            'id' => $preset->value,
            'name' => $preset->title(),
        ])->values()->all();
    }

    public function title(): string
    {
        return ucfirst($this->value);
    }
}
