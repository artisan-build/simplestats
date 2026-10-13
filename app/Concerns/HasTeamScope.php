<?php

namespace App\Concerns;

use App\Helpers\AppHelper;
use Illuminate\Database\Eloquent\Builder;

trait HasTeamScope
{
    /**
     * The "current_team_id is" used when the user is authenticated in the frontend.
     * And "team_id" is used when using the api with the project api token.
     */
    public static function bootHasTeamScope(): void
    {
        parent::addGlobalScope('team', function (Builder $query): void {
            if ($teamId = AppHelper::getTeamId()) {
                AppHelper::ensureTeamTimezone();
                $query->where('team_id', $teamId);
            }
        });

        static::saving(function ($model): void {
            if ($teamId = AppHelper::getTeamId()) {
                AppHelper::ensureTeamTimezone();
                $model->team_id = $teamId;
            }
        });
    }
}
