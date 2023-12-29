<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasTeamScope
{
    /**
     * The "current_team_id is" used when the user is authenticated in the frontend.
     * And "team_id" is used when using the api with the instance api token.
     */
    public static function bootHasTeamScope(): void
    {
        if (!auth()->check()) {
            return;
        }

        $teamId = auth()->user()->current_team_id ?? auth()->user()->team_id;

        parent::addGlobalScope('team', function (Builder $query) use ($teamId) {
            $query->where('team_id', $teamId);
        });

        static::saving(function ($model) use ($teamId) {
            $model->team_id = $teamId;
        });
    }
}
