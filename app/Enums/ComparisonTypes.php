<?php

namespace App\Enums;

enum ComparisonTypes: string
{
    case PERIOD = 'period';
    case YEAR = 'year';

    public function getTitle(): string
    {
        return match ($this) {
            self::PERIOD => 'Previous period',
            self::YEAR => 'Year over year',
        };
    }
}
