<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StatsPaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'stats_user_id' => $this->stats_user_id,
            'team_id' => $this->team_id,
            'project_id' => $this->project_id,
            'instance_id' => $this->instance_id,
            'gross' => $this->gross,
            'net' => $this->net,
            'time' => $this->time,
        ];
    }
}
