<?php

namespace App\Enums;

use Carbon\CarbonImmutable;
use LaracraftTech\LaravelUsefulAdditions\Traits\UsefulEnums;

enum DataImportType: string
{
    use UsefulEnums;

    case USERS = 'users';
    case LOGINS = 'logins';
    case PAYMENTS = 'payments';
}
