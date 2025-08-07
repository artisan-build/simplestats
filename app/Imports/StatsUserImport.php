<?php

namespace App\Imports;

use App\Models\DataImport;
use App\Models\StatsUser;
use App\Validators\StatsUserValidator;
use Maatwebsite\Excel\Concerns\WithUpserts;

class StatsUserImport extends BaseStatsImport implements WithUpserts
{
    public function __construct(DataImport $dataImport)
    {
        parent::__construct(
            $dataImport,
            StatsUser::class,
            app(StatsUserValidator::class)
        );
    }

    public function uniqueBy(): array
    {
        return ['id', 'project_id'];
    }
}
