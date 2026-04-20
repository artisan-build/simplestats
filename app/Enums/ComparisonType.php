<?php

namespace App\Enums;

enum ComparisonType: string
{
    case NO = '0';
    case PERIOD = 'period';
    case CYCLE = 'cycle';
    case YEAR = 'year';

    public function getTitle(): string
    {
        return match ($this) {
            self::NO => 'No comparison',
            self::PERIOD => 'Previous period',
            self::CYCLE => 'Previous cycle',
            self::YEAR => 'Year over year',
        };
    }
}
