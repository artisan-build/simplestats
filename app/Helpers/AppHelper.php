<?php

namespace App\Helpers;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Context;

class AppHelper
{
    public static function setTeamContextFromUser(?Authenticatable $user): void
    {
        if ($user) {
            // important check first team_id then current_team_id
            // on api the user model is the instance and else its normal user
            Context::add('team_id', $user->team_id ?? $user->current_team_id);
        }
    }

    public static function ensureTeamTimezone(): void
    {
        $teamTimezone = auth()->user()->team->timezone ?: 'UTC';

        if (config('app.timezone') !== $teamTimezone) {
            Config::set('app.timezone', $teamTimezone);
            date_default_timezone_set($teamTimezone);
        }
    }

    public static function getDBDriver(): string
    {
        $connectionName = config('database.default');

        return config("database.connections.$connectionName.driver");
    }
}
