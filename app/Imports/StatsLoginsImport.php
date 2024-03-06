<?php

namespace App\Imports;

use App\Models\DataImport;
use App\Models\StatsLogin;
use App\Validators\StatsLoginValidator;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StatsLoginsImport extends BaseStatsImport
{
    public function __construct(DataImport $dataImport)
    {
        parent::__construct(
            $dataImport,
            StatsLogin::class,
            app(StatsLoginValidator::class)
        );
    }
}
