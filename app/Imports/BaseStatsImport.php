<?php

namespace App\Imports;

use App\Concerns\HasCounterByStatsUser;
use App\Concerns\HasStatsUserAssurance;
use App\Exceptions\ImportCanceledException;
use App\Models\DataImport;
use App\Validators\BaseStatsValidator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
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

    private int $counterUpdateStep = 0;

    public function __construct(
        protected DataImport $dataImport,
        protected string $modelClass,
        protected BaseStatsValidator $validator
    ) {
        // https://docs.laravel-excel.com/3.1/imports/validation.html#database-transactions
        // We need to make inbetween db updates, so we disable transactions.
        // As we anyway use Batches and Chunks and only the current batch is wrapped in a transaction,
        // it has not really a difference...
        config(['excel.transactions.handler' => 'null']);

        $this->counterUpdateStep = max(1, floor($this->dataImport->rows_max / 100));
    }

    /**
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function getModelClass(): string
    {
        return $this->modelClass;
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

        // TODO this may be an performance issue? on 100k data import we would do 100k queries.
        // check if the users team plan has the rights to create more stats records...
        Gate::authorize('create', [$this->modelClass, $this->counter]);

        $this->counter++;

        // make from/to work also if the order is not correct...
        if (empty($this->idFrom)) $this->idFrom = $row['id'];
        if (empty($this->idTo)) $this->idTo = $row['id'];

        if ($row['id'] < $this->idFrom) $this->idFrom = $row['id'];
        if ($row['id'] > $this->idTo) $this->idTo = $row['id'];

        // limit the interval of the rows_imported value, so that we do not need to make thousands of queries here.
        if ($this->counter % $this->counterUpdateStep == 0 || $this->counter == $this->dataImport->rows_max) {
            $this->updateProgress();
        }

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

            // as batch in/upsert does not use eloquent, we have to add these things manually
            if (in_array(HasCounterByStatsUser::class, $modelConcerns)) {
                $model->counter = $model->getCounter();
            }

            if (in_array(HasStatsUserAssurance::class, $modelConcerns)) {
                $model->ensureStatsUser($uniqueInstance);
            }

            return $model;
        });
    }

    public function updateProgress(): void
    {
        $this->dataImport->update([
            'rows_imported' => $this->counter,
            'id_from' => $this->idFrom,
            'id_to' => $this->idTo,
        ]);
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
