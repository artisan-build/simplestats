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
    }

    /**
     * When we're in an auth context, $uniqueInstance is set by the class concerns.
     * The $uniqueInstance param is for the import process only, as we have no auth context there.
     *
     * @param  array  $uniqueInstance
     * @return void
     */
    public function ensureStatsUser(array $uniqueInstance = []): void
    {
        // our primary key is id + instance_id
        if (StatsUser::where([
            ['id', $this->stats_user_id],
            ['instance_id', $this->instance_id],
        ])->doesntExist()) {
            StatsUser::create(array_merge($uniqueInstance, [
                'id' => $this->stats_user_id,
                'time' => now(),
            ]));
        }
    }
}
