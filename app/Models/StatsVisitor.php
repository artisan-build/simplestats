<?php

namespace App\Models;

use App\Concerns\HasStatsRelations;
use App\Concerns\HasStatsTracking;
use App\Observers\StatsObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

#[ObservedBy(StatsObserver::class)]
class StatsVisitor extends BaseStat
{
    use HasStatsTracking;
    use HasStatsRelations;
}
