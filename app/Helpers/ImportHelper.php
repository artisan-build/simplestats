<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\HeadingRowImport;

class ImportHelper
{
    public static function countImportRows(string|UploadedFile $file): int
    {
        $countRowsImport = new class implements ToCollection, WithHeadingRow
        {
            public int $rowsCount = 0;

            public function collection(Collection $rows): void
            {
                $this->rowsCount = $rows->count();
            }
        };

        Excel::import($countRowsImport, $file);

        return $countRowsImport->rowsCount;
    }

    public static function getImportFields(string|UploadedFile $file): array
    {
        return Excel::toArray(new HeadingRowImport, $file)[0][0];
    }
}
