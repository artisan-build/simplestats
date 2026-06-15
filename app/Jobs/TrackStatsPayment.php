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

        $statsPayment = StatsPayment::updateOrCreate(['id' => $this->input['id']], $this->input);

        // a payment is never a placeholder shell (unlike StatsUser), so a fresh create is the alert signal.
        if ($statsPayment->wasRecentlyCreated) {
            $statsPayment->sendPaymentAlerts();
        }
    }
}
