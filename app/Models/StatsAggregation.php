<?php

namespace App\Models;

use App\Concerns\HasTeamScope;
use App\Enums\StatsRangeType;
use Illuminate\Database\Eloquent\Model;

class StatsAggregation extends Model
{
    use HasTeamScope;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'time' => 'date',
            'type' => StatsRangeType::class,
            'data' => 'json',
            'filter' => 'array',
            'selected_at' => 'datetime',
        ];
    }
}
