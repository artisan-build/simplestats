<?php

namespace App\Concerns;

use App\Models\StatsUser;

trait HasStatsUserAssurance
{
    /**
     * If you change these things here, make sure to also reflect it in the import process
     */
    public static function bootHasStatsUserAssurance()
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
        if (StatsUser::where([
            ['id', $this->stats_user_id],
            ['instance_id', $this->instance_id],
        ])->doesntExist()) {
            StatsUser::create(array_merge($uniqueInstance, [
                'id' => $this->stats_user_id,
                'time' => $this->stats_user_time ?? now(),
                // hack to set ip and user_agent when the user not yet exists on our side, but the user is doing a login
                // then a account gets created and we want to store ip and user_agent
                'ip' => request()->input('ip') ?? null,
                'user_agent' => request()->input('user_agent') ?? null,
            ]));
        }

        unset($this->stats_user_time);
    }
}
