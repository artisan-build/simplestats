<?php

namespace App\Concerns;

use App\Models\StatsUser;
use Illuminate\Support\Facades\Context;

trait HasStatsUserAssurance
{
    /**
     * If you change these things here, make sure to also reflect it in the import process
     */
    public static function bootHasStatsUserAssurance(): void
    {
        static::creating(function ($statsModel) {
            $statsModel->ensureStatsUser();
        });

        static::updating(function ($statsModel) {
            $statsModel->ensureStatsUser();
        });
    }

    /**
     * When we're in an auth context, $uniqueInstance is set by the class concerns.
     * The $uniqueInstance param is for the import process only, as we have no auth context there.
     */
    public function ensureStatsUser(array $uniqueInstance = []): void
    {
        if (! $this->stats_user_id) {
            return;
        }

        // our primary key is id + instance_id
        StatsUser::firstOrCreate([
            'id' => $this->stats_user_id,
            'instance_id' => $this->instance_id,
        ], array_merge($uniqueInstance, [
            'id' => $this->stats_user_id,
            'time' => $this->stats_user_time ?? now(),
            // hack to set ip and user_agent when the user not yet exists on our side, but the user is doing a login,
            // then an account gets created, and we want to store ip and user_agent
            'ip' => $this->ip ?? null,
            'user_agent' => $this->user_agent ?? null,
        ]));

        unset($this->stats_user_time);
    }
}
