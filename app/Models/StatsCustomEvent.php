<?php

namespace App\Models;

use App\Concerns\HasCounterByStatsUser;
use App\Concerns\HasStatsUserAssurance;
use App\Observers\StatsObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy(StatsObserver::class)]
class StatsCustomEvent extends BaseStat
{
    use HasCounterByStatsUser;
    use HasStatsUserAssurance;

    public $incrementing = false;

    public function statsUser(): BelongsTo
    {
        return $this->belongsTo(StatsUser::class);
    }

    public function statsVisitor(): BelongsTo
    {
        return $this->belongsTo(StatsVisitor::class);
    }

    public function statsCustomEventName(): BelongsTo
    {
        return $this->belongsTo(StatsCustomEventName::class);
    }

    /**
     * Override to handle visitor-only events (stats_user_id is null).
     * Without this, HasCounterByStatsUser::getCounter() would match ALL
     * visitor-only events via WHERE stats_user_id = NULL.
     */
    public function getCounter(): int
    {
        if ($this->stats_user_id === null) {
            return 1;
        }

        if ($existingStatsRecord = $this->where(['id' => $this->id, 'project_id' => $this->project_id])->first()) {
            return $existingStatsRecord->counter;
        }

        return $this->where([
            'stats_user_id' => $this->stats_user_id,
            'project_id' => $this->project_id,
        ])->count() + 1;
    }
}
