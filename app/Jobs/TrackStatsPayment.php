<?php

namespace App\Jobs;

use App\Models\StatsPayment;
use App\Models\StatsVisitor;

class TrackStatsPayment extends BaseStatsTrack
{
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        parent::handle();

        if (isset($this->input['visitor_hash'])) {
            $visitorHash = $this->input['visitor_hash'];
            unset($this->input['visitor_hash']);

            $this->input['stats_visitor_id'] = StatsVisitor::where('visitor_hash', $visitorHash)->first()?->id ?? null;
        }

        StatsPayment::updateOrCreate(['id' => $this->input['id']], $this->input);
    }
}
