<?php

namespace App\Services;

use App\DTOs\StatsFilterDto;
use App\Models\StatsCustomEvent;

class StatsCustomEventService
{
    /**
     * Whether the current view has ever tracked a custom event, regardless of
     * the selected period. Drives the dashboard's events group visibility,
     * mirroring how StatsCustomPropertyService::getPropertyDimensions() gates
     * custom properties. Keeping it period-independent stops the group from
     * appearing and disappearing as the range shifts; the group's own counts
     * still scope to the range.
     *
     * Lookups are project-scoped: a selected project sees only its own events,
     * the "All projects" view (TRACK_ALL) any team event. Team scoping is
     * applied automatically via HasTeamScope.
     */
    public static function hasCustomEvents(StatsFilterDto $filters): bool
    {
        return StatsCustomEvent::query()
            ->when($filters->projectId, fn ($query) => $query->where('project_id', $filters->projectId))
            ->exists();
    }
}
