<?php

namespace App\Models;

use App\Concerns\HasTeamScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StatsCustomPropertyName extends Model
{
    use HasFactory;
    use HasTeamScope;

    protected $guarded = false;

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function values(): HasMany
    {
        return $this->hasMany(StatsCustomPropertyValue::class);
    }

    public function properties(): HasMany
    {
        return $this->hasMany(StatsCustomProperty::class);
    }
}
