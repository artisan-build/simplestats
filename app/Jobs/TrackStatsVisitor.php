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
            'visitor_hash' => StatsVisitor::createHash($this->input['time'], $this->input['ip'], $this->input['user_agent']),
        ], $this->input);
    }
}
