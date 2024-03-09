<?php

namespace App\Jobs;

use App\Exceptions\ImportCanceledException;
use App\Imports\BaseStatsImport;
use App\Mail\DataImportCanceled;
use App\Mail\DataImportError;
use App\Mail\DataImportFailed;
use App\Mail\DataImportCompleted;
use App\Models\DataImport;
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
        $this->onQueue('low');

        $this->__baseConstruct($dataImport);
        $this->filepath = $filepath;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // As this job uses the Trackable trait, we already track the output of these jobs,
        // and as we do not retry those, there is no need to also store it in the failed jobs table.
        config()->set('queue.failed.driver', null);

        $importer = $this->model->type->getImporter($this->model);

        try {
            Excel::import($importer, $this->filepath);
            $this->processPostImportBeforeMails($importer);
            Mail::to($this->model->createdBy->email)->queue(new DataImportCompleted($this->model));
        } catch (Throwable $e) {
            $this->processPostImportBeforeMails($importer);
            $this->model->trackedJob->output = $e->getMessage();

            // higher exceptions must be listed before Throwable, else it's always true
            match(true) {
                $e instanceof ImportCanceledException => Mail::to($this->model->createdBy->email)->queue(new DataImportCanceled($this->model)),
                $e instanceof ValidationException => Mail::to($this->model->createdBy->email)->queue(new DataImportFailed($this->model)),
                $e instanceof Throwable => Mail::to($this->model->createdBy->email)->queue(new DataImportError($this->model)),
            };

            throw $e;
        } finally {
            Storage::disk('local')->delete($this->filepath);
        }
    }

    private function processPostImportBeforeMails(BaseStatsImport $importer): void
    {
        $this->model->update([
            'rows_imported' => $importer->counter,
            'id_from' => $importer->idFrom,
            'id_to' => $importer->idTo,
        ]);

        $this->model->trackedJob->finished_at = now();
    }
}
