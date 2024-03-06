<?php

namespace App\Concerns;

use App\Models\StatsUser;

trait HasStatsUserAssurance
{
    public static function bootHasStatsUserAssurance()
    {
        static::creating(function ($statsModel) {
            // our primary key is id + instance_id
            if (StatsUser::where([
                ['id', $statsModel->stats_user_id],
                ['instance_id', $statsModel->instance_id],
            ])->doesntExist()) {
                // ungarded cause we have to supply team, project and instance, as no user is authed
                StatsUser::unguarded(function () use ($statsModel) {
                    StatsUser::create([
                        'id' => $statsModel->stats_user_id,
                        'team_id' => $statsModel->team_id,
                        'project_id' => $statsModel->project_id,
                        'instance_id' => $statsModel->instance_id,
                        'time' => now(),
                    ]);
                });
            }
        });
    }
}
