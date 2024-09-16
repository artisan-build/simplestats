<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Config;

class AppHelper
{
    public static function ensureTeamTimezone(): void
    {
        $teamTimezone = auth()->user()->team->timezone;

        if (config('app.timezone') !== $teamTimezone) {
            Config::set('app.timezone', $teamTimezone);
            date_default_timezone_set($teamTimezone);
        }
    }
}
