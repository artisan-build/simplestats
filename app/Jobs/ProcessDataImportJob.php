<?php

namespace App\Jobs;

use App\Exceptions\ImportCanceledException;
use App\Helpers\AppHelper;
use App\Imports\BaseStatsImport;
use App\Mail\DataImportCanceled;
use App\Mail\DataImportCompleted;
use App\Mail\DataImportError;
use App\Mail\DataImportFailed;
use App\Models\DataImport;
use App\Services\StatsAggregationsService;
use App\Services\StatsService;
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

    public int $timeout = 3600;

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
        // make auth stuff work in this dispatched/context-less job
        auth()->setUser($this->model->createdBy);

        // ppl mostly import data without UTC, so to make validation work, we need to apply timezone directly...
        AppHelper::ensureTeamTimezone();

        // As this job uses the Trackable trait, we already track the output of these jobs,
        // and as we do not retry those, there is no need to also store it in the failed jobs table.
        config()->set('queue.failed.driver', null);

        $importer = $this->model->type->getImporter($this->model);

        try {
            Excel::import($importer, $this->filepath);
            $this->processPostImport($importer);
            Mail::to($this->model->createdBy->email)->queue(new DataImportCompleted($this->model));
        } catch (Throwable $e) {
            $this->processPostImport($importer);
            $this->model->trackedJob->output = $e->getMessage();

            // higher exceptions must be listed before Throwable, else it's always true
            match (true) {
                $e instanceof ImportCanceledException => Mail::to($this->model->createdBy->email)->queue(new DataImportCanceled($this->model)),
                $e instanceof ValidationException => Mail::to($this->model->createdBy->email)->queue(new DataImportFailed($this->model)),
                $e instanceof Throwable => Mail::to($this->model->createdBy->email)->queue(new DataImportError($this->model)),
            };

            throw $e;
        } finally {
            app(StatsService::class)->recalculateStatsStartingAt();
            $this->invalidateStatsAggregations($importer);
            Storage::disk('local')->delete($this->filepath);
        }
    }

    private function processPostImport(BaseStatsImport $importer): void
    {
        $importer->updateProgress();

        $this->model->trackedJob->finished_at = now();
    }

    private function invalidateStatsAggregations(BaseStatsImport $importer): void
    {
        $modelClass = $importer->getModelClass();

        $modelClass::unguard();
        // we fake a stats model, cause we need it as the parameter here...
        $stat = new $modelClass([
            'team_id' => $this->model->team_id,
            'project_id' => $this->model->project_id,
            'instance_id' => $this->model->instance_id,
            'time' => $importer->getLowestImportedDate(),
        ]);
        $modelClass::reguard();

        app(StatsAggregationsService::class)->invalidateByStatsUpdate($stat);
    }
}
