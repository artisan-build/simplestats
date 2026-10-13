<?php

namespace App\Concerns;

use App\Models\StatsUser;

trait HasStatsUserAssurance
{
    /**
     * If you change these things here, make sure to also reflect it in the import process
     */
    public static function bootHasStatsUserAssurance(): void
    {
        static::creating(function ($statsModel): void {
            $statsModel->ensureStatsUser();
        });

        static::updating(function ($statsModel): void {
            $statsModel->ensureStatsUser();
        });
    }

    /**
     * When we're in an auth context, $uniqueProject is set by the class concerns.
     * The $uniqueProject param is for the import process only, as we have no auth context there.
     */
    public function ensureStatsUser(array $uniqueProject = []): void
    {
        if (! $this->stats_user_id) {
            return;
        }

        // our primary key is id + project_id
        StatsUser::firstOrCreate([
            'id' => $this->stats_user_id,
            'project_id' => $this->project_id,
        ], array_merge($uniqueProject, [
            'id' => $this->stats_user_id,
            'time' => $this->stats_user_time ?? now(),
            // placeholder for a login/payment/event before we saw the registration; TrackStatsUser
            // flips this to true once the real registration ingest lands.
            'registered' => false,
            // hack to set ip and user_agent when the user not yet exists on our side, but the user is doing a login,
            // then an account gets created, and we want to store ip and user_agent
            'ip' => $this->ip ?? null,
            'user_agent' => $this->user_agent ?? null,
        ]));

        unset($this->stats_user_time);
    }
}
