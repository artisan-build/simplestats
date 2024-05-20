<?php

namespace App\Models;

use App\Concerns\HasTeamScope;
use App\Enums\StatsRangeType;
use Illuminate\Database\Eloquent\Model;

class StatsAggregation extends Model
{
    use HasTeamScope;

    public $timestamps = false;

    protected $casts = [
        'time' => 'date',
        'type' => StatsRangeType::class,
        'data' => 'json',
        'selected_at' => 'datetime',
    ];
}
