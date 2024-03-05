<?php

namespace App\Concerns;

use App\Models\StatsUser;

trait HasStatsUserAssurance
{
    public static function bootHasStatsUserInsurance()
    {
        static::creating(function ($statsModel) {
            if (StatsUser::whereKey($statsModel->stats_user_id)->doesntExist()) {
                StatsUser::create([
                    'id' => $statsModel->stats_user_id,
                    'time' => now(),
                ]);
            }
        });
    }
}
