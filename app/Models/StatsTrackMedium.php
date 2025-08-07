<?php

namespace App\Models;

use App\Concerns\HasTeamScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StatsTrackMedium extends Model
{
    use HasTeamScope;

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
