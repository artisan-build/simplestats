<?php

namespace App\Jobs;

use App\Models\StatsVisitor;

class TrackStatsVisitor extends BaseStatsTrack
{
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        parent::handle();

        StatsVisitor::firstOrCreate([
            'visitor_hash' => $this->input['visitor_hash'],
        ], $this->input);
    }
}
