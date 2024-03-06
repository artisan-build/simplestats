<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasCounterByStatsUser
{
    /**
     * If you change these things here, make sure to also reflect it in the import process
     */

    public static function bootHasCounterByStatsUser(): void
    {
        static::creating(function ($model) {
            $model->counter = $model->getCounter();
        });
    }

    public function getCounter(): int
    {
        // stats records are unique only per instance, so we can't use whereKey or find here...

        $existingStatsRecord = $this->where([
            'id' => $this->id,
            'instance_id' => $this->instance_id,
        ])->first();

        if ($existingStatsRecord) {
            return $existingStatsRecord->counter;
        }

        return $this->where([
            'stats_user_id' => $this->stats_user_id,
            'instance_id' => $this->instance_id,
        ])->count()+1;
    }
}
