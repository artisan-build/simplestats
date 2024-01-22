<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Request;

trait HasProjectScope
{
    public static function bootHasProjectScope(): void
    {
        // on api requests, I want to make sure,
        // that the project_id form the current instance always is used
        if (! Request::is('api/*')) {
            return;
        }

        parent::addGlobalScope('project', function (Builder $query) {
            if (auth()->check()) {
                $query->where('project_id', auth()->user()->project_id);
            }
        });

        static::saving(function ($model) {
            if (auth()->check()) {
                $model->project_id = auth()->user()->project_id;
            }
        });
    }
}
