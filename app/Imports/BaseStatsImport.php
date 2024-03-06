<?php

namespace App\Imports;

use App\Models\DataImport;
use App\Validators\BaseValidator;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

abstract class BaseStatsImport implements ToModel, WithHeadingRow, WithValidation, WithChunkReading //, WithBatchInserts
{
    const BATCH_CHUNK_SIZE = 1000;

    public int $counter = 0;
    public int $idFrom = 0;
    public int $idTo = 0;

    public function __construct(
        protected DataImport $dataImport,
        protected string $modelClass,
        protected BaseValidator $validator
    ) {}

    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        $this->counter++;

        if ($this->counter === 1) {
            $this->idFrom = $row['id'] ?? 0;
        }

        $this->idTo = $row['id'] ?? 0;

        // team, project, instance id are normally set dynamically
        // this is safe, as we've validated the rows/cols before...
        return $this->modelClass::unguarded(function () use ($row) {
            // prepare validation tweaks data as we need/want it here
            return new $this->modelClass(array_merge([
                    'team_id' => $this->dataImport->team_id,
                    'project_id' => $this->dataImport->project_id,
                    'instance_id' => $this->dataImport->instance_id,
                ],
                $this->validator->prepareForValidation($row),
            ));
        });
    }

    public function prepareForValidation($data, $index): array
    {
        return $this->validator->prepareForValidation($data);
    }

    public function rules(): array
    {
        return $this->validator->rules();
    }

    public function batchSize(): int
    {
        return self::BATCH_CHUNK_SIZE;
    }

    public function chunkSize(): int
    {
        return self::BATCH_CHUNK_SIZE;
    }
}
