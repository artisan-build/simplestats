<?php

namespace App\Helpers;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Context;

class AppHelper
{
    public static function setTeamContextFromAuth(?Authenticatable $authModel): void
    {
        if ($authModel) {
            // important check first team_id then current_team_id
            // on api the user model is the project and else its normal user
            Context::add('team_id', $authModel->team_id ?? $authModel->current_team_id);
        }
    }

    public static function getTeamId(): ?int
    {
        return Context::get('team_id')
            ?? auth()->user()?->team_id
            ?? auth()->user()?->current_team_id;
    }

    public static function ensureTeamTimezone(): void
    {
        $user = auth()->user();
        $teamTimezone = $user?->loadMissing('team')->team?->timezone ?: 'UTC';

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

    /**
     * A unique hash that identifies a visit on a given day.
     *
     * Must stay identical to SimpleStatsIo\LaravelClient\SimplestatsClient::createVisitorHash
     * so v2 clients (server-side fallback) and v3+ clients (payload) produce the same hash.
     */
    public static function createVisitorHash(?string $time, ?string $ip, ?string $userAgent): string
    {
        $time = CarbonImmutable::parse($time);
        $visitorHash = hash('sha256', $ip.$userAgent.$time->format('Y-m-d').config('app.key'));

        return substr($visitorHash, 0, 32);
    }
}
