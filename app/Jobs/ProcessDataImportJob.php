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
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Junges\TrackableJobs\TrackableJob;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ProcessDataImportJob extends TrackableJob implements ShouldQueue
{
    use Queueable;

    // do not retry on failure
    public int $tries = 1;

    public int $timeout = 3600;

    public function __construct(public DataImport $dataImport, public string $filepath)
    {
        $this->onQueue('low');

        parent::__construct();
    }

    public function trackableKey(): ?string
    {
        return (string) $this->dataImport->getKey();
    }

    public function trackableType(): ?string
    {
        return $this->dataImport->getMorphClass();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // make auth stuff work in this dispatched/context-less job
        // bind team scope to the import's team, not the user's current_team_id
        // (the user may belong to multiple teams and have switched in the frontend)
        Context::add('team_id', $this->dataImport->team_id);
        auth()->setUser($this->dataImport->createdBy);

        // ppl mostly import data without UTC, so to make validation work, we need to apply timezone directly...
        AppHelper::ensureTeamTimezone();

        // As this job uses the Trackable trait, we already track the output of these jobs,
        // and as we do not retry those, there is no need to also store it in the failed jobs table.
        config()->set('queue.failed.driver');

        $importer = $this->dataImport->type->getImporter($this->dataImport);

        try {
            Excel::import($importer, $this->filepath, Config::string('filesystems.shared'));
            $this->processPostImport($importer);
            Mail::to($this->dataImport->createdBy->email)->queue(new DataImportCompleted($this->dataImport));
        } catch (Throwable $e) {
            $this->processPostImport($importer);
            $this->trackedJob->setOutput(['message' => $e->getMessage()]);

            // higher exceptions must be listed before Throwable, else it's always true
            match (true) {
                $e instanceof ImportCanceledException => Mail::to($this->dataImport->createdBy->email)->queue(new DataImportCanceled($this->dataImport)),
                $e instanceof ValidationException => Mail::to($this->dataImport->createdBy->email)->queue(new DataImportFailed($this->dataImport)),
                $e instanceof Throwable => Mail::to($this->dataImport->createdBy->email)->queue(new DataImportError($this->dataImport)),
            };

            throw $e;
        } finally {
            resolve(StatsService::class)->recalculateStatsStartingAt();
            $this->invalidateStatsAggregations($importer);
            Storage::disk(Config::string('filesystems.shared'))->delete($this->filepath);
        }
    }

    private function processPostImport(BaseStatsImport $importer): void
    {
        $importer->updateProgress();

        $this->trackedJob->finished_at = now();
    }

    private function invalidateStatsAggregations(BaseStatsImport $importer): void
    {
        $modelClass = $importer->getModelClass();

        $modelClass::unguard();
        // we fake a stats model, cause we need it as the parameter here...
        $stat = new $modelClass([
            'team_id' => $this->dataImport->team_id,
            'project_id' => $this->dataImport->project_id,
            'time' => $importer->getLowestImportedDate(),
        ]);
        $modelClass::reguard();

        resolve(StatsAggregationsService::class)->invalidateByStatsUpdate($stat);
    }
}
