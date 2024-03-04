<?php

namespace App\Jobs;

use App\Models\DataImport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Junges\TrackableJobs\Concerns\Trackable;

class ProcessDataImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Trackable;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        sleep(10);
        100/0;
    }
}
