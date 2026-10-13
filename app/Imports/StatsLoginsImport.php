<?php

namespace App\Imports;

use App\Models\DataImport;
use App\Models\StatsLogin;
use App\Validators\StatsLoginValidator;

class StatsLoginsImport extends BaseStatsImport
{
    public function __construct(DataImport $dataImport)
    {
        parent::__construct(
            $dataImport,
            StatsLogin::class,
            resolve(StatsLoginValidator::class)
        );
    }
}
