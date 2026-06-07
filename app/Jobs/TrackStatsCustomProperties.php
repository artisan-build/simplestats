<?php

namespace App\Jobs;

use App\Contracts\Propertyable;
use App\Models\StatsUser;
use App\Models\StatsVisitor;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Context;

class TrackStatsCustomProperties extends BaseStatsTrack
{
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        parent::handle();

        $properties = $this->input['properties'] ?? [];

        if ($properties === []) {
            return;
        }

        $this->resolvePropertyable()?->recordCustomProperties($properties, CarbonImmutable::parse($this->input['time']));
    }

    /**
     * Resolve the targeted entity. Properties for an entity we do not know
     * (yet) are dropped, e.g. a visitor that was rejected by the bot detection.
     */
    protected function resolvePropertyable(): ?Propertyable
    {
        if (isset($this->input['visitor_hash'])) {
            return StatsVisitor::where('visitor_hash', $this->input['visitor_hash'])->first();
        }

        if (isset($this->input['stats_user_id'])) {
            return StatsUser::where('id', $this->input['stats_user_id'])
                ->where('project_id', Context::get('project_id'))
                ->first();
        }

        return null;
    }
}
