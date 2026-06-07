<?php

namespace App\Models;

use App\Concerns\HasTeamScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StatsCustomProperty extends Model
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

    /**
     * The owning entity, a StatsUser or a StatsVisitor.
     *
     * Note: StatsUser has a composite primary key (id, project_id), so the native
     * morphTo() only matches on id. Always scope by project_id when resolving the
     * owner outside of an already project-scoped context.
     */
    public function propertyable(): MorphTo
    {
        return $this->morphTo();
    }

    public function name(): BelongsTo
    {
        return $this->belongsTo(StatsCustomPropertyName::class, 'stats_custom_property_name_id');
    }

    public function value(): BelongsTo
    {
        return $this->belongsTo(StatsCustomPropertyValue::class, 'stats_custom_property_value_id');
    }
}
