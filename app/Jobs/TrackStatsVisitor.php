<?php

namespace App\Jobs;

use App\Models\StatsVisitor;
use App\Services\BotDetectionService;
use Carbon\CarbonImmutable;

class TrackStatsVisitor extends BaseStatsTrack
{
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        parent::handle();

        if (BotDetectionService::isDatacenterIp($this->input['ip'] ?? null)) {
            return;
        }

        $properties = $this->input['properties'] ?? [];
        unset($this->input['properties']);

        $visitor = StatsVisitor::firstOrCreate([
            'visitor_hash' => $this->input['visitor_hash'],
        ], $this->input);

        $visitor->recordCustomProperties($properties, CarbonImmutable::parse($this->input['time']));
    }
}
