<?php

namespace App\Concerns;

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
        // stats records are unique only per project, so we can't use whereKey or find here...

        if ($existingStatsRecord = $this->where(['id' => $this->id, 'project_id' => $this->project_id])->first()) {
            return $existingStatsRecord->counter;
        }

        return $this->where([
            'stats_user_id' => $this->stats_user_id,
            'project_id' => $this->project_id,
        ])->count() + 1;
    }
}
