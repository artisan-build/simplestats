<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasTeamScope
{
    public static function bootHasTeamScope()
    {
        parent::addGlobalScope('team', function (Builder $query) {
            if (auth()->check()) {
                $query->where('team_id', auth()->user()->current_team_id);
            }
        });

        static::saving(function ($model) {
            if (auth()->check()) {
                $model->team_id = auth()->user()->current_team_id;
            }
        });
    }
}
