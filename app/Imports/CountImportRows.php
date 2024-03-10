<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;

class CountImportRows implements ToCollection, WithHeadingRow
{
    public int $rowsCount = 0;

    public function collection(Collection $rows)
    {
        $this->rowsCount = $rows->count();
    }
}
