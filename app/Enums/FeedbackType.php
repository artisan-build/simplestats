<?php

namespace App\Enums;

use LaracraftTech\LaravelUsefulAdditions\Traits\UsefulEnums;

enum FeedbackType: string
{
    use UsefulEnums;

    case FEEDBACK = 'feedback';
    case IDEA = 'idea';
    case BUG = 'bug';

    public function title(): string
    {
        return ucfirst($this->value);
    }

    public static function toOptions(): array
    {
        return collect(self::cases())->map(fn (FeedbackType $type): array => [
            'id' => $type->value,
            'name' => $type->title(),
        ])->values()->all();
    }
}
