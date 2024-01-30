<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StatsLoginResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'stats_user_id' => $this->stats_user_id,
            'project_id' => $this->project_id,
            'instance_id' => $this->instance_id,
            'time' => $this->time,
        ];
    }
}
