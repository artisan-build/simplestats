<?php

namespace App\Models;

use App\Concerns\HasTeamScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StatsLocationCity extends Model
{
    use HasTeamScope;

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
