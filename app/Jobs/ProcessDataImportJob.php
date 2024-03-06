<?php

namespace App\Jobs;

use App\Mail\DataImportError;
use App\Mail\DataImportFailed;
use App\Mail\DataImportSuccessful;
use App\Models\DataImport;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Junges\TrackableJobs\Concerns\Trackable;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ProcessDataImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Trackable {
        __construct as __baseConstruct;
    }

    // do not retry on failure
    public int $tries = 1;

    public string $filepath;

    public function __construct(DataImport $dataImport, string $filepath)
    {
        $this->__baseConstruct($dataImport);
        $this->filepath = $filepath;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $importer = $this->model->type->getImporter($this->model);

        try {
            Excel::import($importer, $this->filepath);
            $this->model->trackedJob->finished_at = now();
            Mail::to($this->model->createdBy->email)->send(new DataImportSuccessful($this->model));
        } catch (Throwable $e) {
            $this->model->update([
                'rows_count' => $importer->counter,
                'id_from' => $importer->idFrom,
                'id_to' => $importer->idTo,
            ]);

            $this->model->trackedJob->output = $e->getMessage();
            $this->model->trackedJob->finished_at = now();

            // higher exceptions must be listed before Throwable, else it's always true
            match(true) {
                $e instanceof ValidationException => Mail::to($this->model->createdBy->email)->send(new DataImportFailed($this->model)),
                $e instanceof Throwable => Mail::to($this->model->createdBy->email)->send(new DataImportError($this->model)),
            };

            throw $e;
        } finally {
            Storage::disk('local')->delete($this->filepath);
        }
    }
}
