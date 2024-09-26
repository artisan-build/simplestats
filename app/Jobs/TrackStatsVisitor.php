<?php

namespace App\Jobs;

use App\Models\StatsVisitor;
use Carbon\Carbon;

class TrackStatsVisitor extends BaseStatsTrack
{
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        parent::handle();

        StatsVisitor::firstOrCreate([
            'visitor_hash' => StatsVisitor::createHash(Carbon::parse($this->input['time'])->format('Y-m-d'), $this->input['ip'] ?? '', $this->input['user_agent'] ?? ''),
        ], $this->input);
    }
}
