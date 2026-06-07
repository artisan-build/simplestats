<?php

namespace App\Models;

use App\Concerns\HasCustomProperties;
use App\Concerns\HasStatsRelations;
use App\Concerns\HasStatsTracking;
use App\Contracts\Propertyable;
use App\Observers\StatsObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

#[ObservedBy(StatsObserver::class)]
class StatsVisitor extends BaseStat implements Propertyable
{
    use HasCustomProperties;
    use HasStatsRelations;
    use HasStatsTracking;
}
