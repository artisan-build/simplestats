<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Config;

class AppHelper
{
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
