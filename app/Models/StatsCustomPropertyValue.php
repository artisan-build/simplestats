<?php

namespace App\Models;

use App\Concerns\HasTeamScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StatsCustomPropertyValue extends Model
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

    public function name(): BelongsTo
    {
        return $this->belongsTo(StatsCustomPropertyName::class, 'stats_custom_property_name_id');
    }

    public function properties(): HasMany
    {
        return $this->hasMany(StatsCustomProperty::class);
    }
}
