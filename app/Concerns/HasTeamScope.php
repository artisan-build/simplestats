<?php

namespace App\Concerns;

use App\Helpers\AppHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Context;

trait HasTeamScope
{
    /**
     * The "current_team_id is" used when the user is authenticated in the frontend.
     * And "team_id" is used when using the api with the project api token.
     */
    public static function bootHasTeamScope(): void
    {
        parent::addGlobalScope('team', function (Builder $query) {
            if ($teamId = Context::get('team_id')) {
                AppHelper::ensureTeamTimezone();
                $query->where('team_id', $teamId);
            }
        });

        static::saving(function ($model) {
            if ($teamId = Context::get('team_id')) {
                AppHelper::ensureTeamTimezone();
                $model->team_id = $teamId;
            }
        });
    }
}
