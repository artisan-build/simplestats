<?php

namespace App\Imports;

use App\Concerns\HasCounterByStatsUser;
use App\Concerns\HasStatsUserAssurance;
use App\Exceptions\ImportCanceledException;
use App\Models\DataImport;
use App\Validators\BaseValidator;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

abstract class BaseStatsImport implements ToModel, WithHeadingRow, WithValidation, WithChunkReading, WithBatchInserts
{
    const BATCH_CHUNK_SIZE = 1000;

    public int $counter = 0;
    public int $idFrom = 0;
    public int $idTo = 0;

    public function __construct(
        protected DataImport $dataImport,
        protected string $modelClass,
        protected BaseValidator $validator
    ) {
        // https://docs.laravel-excel.com/3.1/imports/validation.html#database-transactions
        // We need to make inbetween db updates, so we disable transactions.
        // As we anyway use Batches and Chunks and only the current batch is wrapped in a transaction,
        // it has not really a difference...
        config(['excel.transactions.handler' => 'null']);
    }

    /**
     * @param  array  $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     * @throws ImportCanceledException
     */
    public function model(array $row)
    {
        if (Cache::get('cancel_data_import_'.$this->dataImport->id)) {
            Cache::forget('cancel_data_import_'.$this->dataImport->id);
            throw new ImportCanceledException();
        }

        $this->counter++;
        if ($this->counter === 1) {$this->idFrom = $row['id'] ?? 0;}
        $this->idTo = $row['id'] ?? 0;

        $modelConcerns = class_uses(new $this->modelClass());

        // team, project, instance id are normally set dynamically
        // this is safe, as we've validated the rows/cols before...
        return $this->modelClass::unguarded(function () use ($row, $modelConcerns) {
            $uniqueInstance = [
                'team_id' => $this->dataImport->team_id,
                'project_id' => $this->dataImport->project_id,
                'instance_id' => $this->dataImport->instance_id,
            ];

            // prepare validation tweaks data as we need/want it here
            $data = array_merge($this->validator->prepareForValidation($row), $uniqueInstance);

            $model = new $this->modelClass($data);

            // as batch in/upsert does not use eloquent we have to add these things manually
            if (in_array(HasCounterByStatsUser::class, $modelConcerns)) {
                $model->counter = $model->getCounter();
            }

            if (in_array(HasStatsUserAssurance::class, $modelConcerns)) {
                $model->ensureStatsUser($uniqueInstance);
            }

            return $model;
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
