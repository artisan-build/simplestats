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
            'team_id' => $this->team_id,
            'project_id' => $this->project_id,
            'instance_id' => $this->instance_id,
            'track_source' => $this->track_source,
            'track_medium' => $this->track_medium,
            'track_campaign' => $this->track_campaign,
            'track_term' => $this->track_term,
            'track_content' => $this->track_content,
            'time' => $this->time,
        ];
    }
}
