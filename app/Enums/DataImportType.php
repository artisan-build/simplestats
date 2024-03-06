<?php

namespace App\Enums;

use App\Imports\BaseStatsImport;
use App\Imports\StatsLoginsImport;
use App\Imports\StatsPaymentsImport;
use App\Imports\StatsUserImport;
use App\Models\DataImport;
use Carbon\CarbonImmutable;
use LaracraftTech\LaravelUsefulAdditions\Traits\UsefulEnums;

enum DataImportType: string
{
    use UsefulEnums;

    case USERS = 'users';
    case LOGINS = 'logins';
    case PAYMENTS = 'payments';

    public function getImporter(DataImport $dataImport): BaseStatsImport
    {
        return match($this) {
            self::USERS => new StatsUserImport($dataImport),
            self::LOGINS => new StatsLoginsImport($dataImport),
            self::PAYMENTS => new StatsPaymentsImport($dataImport),
        };
    }
}
