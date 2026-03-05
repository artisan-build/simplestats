<?php

namespace App\Jobs;

use App\Models\StatsPayment;

class TrackStatsPayment extends BaseStatsTrack
{
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        parent::handle();

        $this->resolveVisitorHash();

        StatsPayment::updateOrCreate(['id' => $this->input['id']], $this->input);
    }
}
