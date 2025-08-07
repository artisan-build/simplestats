<?php

namespace App\Imports;

use App\Models\DataImport;
use App\Models\StatsPayment;
use App\Validators\StatsPaymentValidator;
use Maatwebsite\Excel\Concerns\WithUpserts;

class StatsPaymentsImport extends BaseStatsImport implements WithUpserts
{
    public function __construct(DataImport $dataImport)
    {
        parent::__construct(
            $dataImport,
            StatsPayment::class,
            app(StatsPaymentValidator::class)
        );
    }

    public function uniqueBy(): array
    {
        return ['id', 'project_id'];
    }
}
