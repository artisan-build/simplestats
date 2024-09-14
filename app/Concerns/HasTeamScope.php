<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;

trait HasTeamScope
{
    /**
     * The "current_team_id is" used when the user is authenticated in the frontend.
     * And "team_id" is used when using the api with the instance api token.
     */
    public static function bootHasTeamScope(): void
    {
        parent::addGlobalScope('team', function (Builder $query) {
            if (auth()->check()) {
                self::ensureTeamTimezone();
                // important check first team_id then current_team_id
                $teamId = auth()->user()->team_id ?? auth()->user()->current_team_id;
                $query->where('team_id', $teamId);
            }
        });

        static::saving(function ($model) {
            if (auth()->check()) {
                self::ensureTeamTimezone();
                // important check first team_id then current_team_id
                $teamId = auth()->user()->team_id ?? auth()->user()->current_team_id;
                $model->team_id = $teamId;
            }
        });
    }

    private static function ensureTeamTimezone(): void
    {
        $teamTimezone = auth()->user()->team->timezone;

        if (config('app.timezone') !== $teamTimezone) {
            Config::set('app.timezone', $teamTimezone);
            date_default_timezone_set($teamTimezone);
        }
    }
}
