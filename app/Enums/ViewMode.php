<?php

namespace App\Enums;

use Carbon\CarbonImmutable;
use LaracraftTech\LaravelUsefulAdditions\Traits\UsefulEnums;

enum ViewMode: string
{
    use UsefulEnums;

    case SIMPLE = 'simple';
    case CHART = 'chart';
}
