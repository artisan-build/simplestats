<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasCounterByStatsUser
{
    public static function bootHasCounterByStatsUser(): void
    {
        static::saving(function ($model) {
            $model->counter = $model::where('stats_user_id', $model->stats_user_id)->count()+1;
        });
    }
}
