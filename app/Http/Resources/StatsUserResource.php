<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StatsUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'instance_id' => $this->instance_id,
            'ip' => $this->ip,
            'user_agent' => $this->user_agent,
            'stats_track_country_id' => $this->stats_track_country_id,
            'stats_track_source_id' => $this->stats_track_source_id,
            'stats_track_medium_id' => $this->stats_track_medium_id,
            'stats_track_campaign_id' => $this->stats_track_campaign_id,
            'stats_track_term_id' => $this->stats_track_term_id,
            'stats_track_content_id' => $this->stats_track_content_id,
            'time' => $this->time,
        ];
    }
}
