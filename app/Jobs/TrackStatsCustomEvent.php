<?php

namespace App\Jobs;

use App\Models\StatsCustomEvent;
use App\Models\StatsCustomEventName;

class TrackStatsCustomEvent extends BaseStatsTrack
{
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        parent::handle();

        $this->resolveVisitorHash();

        $eventName = StatsCustomEventName::firstOrCreate([
            'team_id' => auth()->user()->team_id,
            'project_id' => auth()->user()->id,
            'name' => $this->input['name'],
        ]);

        $this->input['stats_custom_event_name_id'] = $eventName->id;
        unset($this->input['name']);

        StatsCustomEvent::updateOrCreate(['id' => $this->input['id']], $this->input);
    }
}
