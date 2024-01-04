<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Request;

trait HasInstanceScope
{
    public static function bootHasInstanceScope(): void
    {
        // on api requests, I want to make sure,
        // that the instance_id and project_id form the current instance always is used
        if (! Request::is('api/*')) {
            return;
        }

        parent::addGlobalScope('instance', function (Builder $query) {
            if (auth()->check()) {
                $query->where('instance_id', auth()->user()->id);
                $query->where('project_id', auth()->user()->project_id);
            }
        });

        static::saving(function ($model) {
            if (auth()->check()) {
                $model->instance_id = auth()->user()->id;
                $model->project_id = auth()->user()->project_id;
            }
        });
    }
}
