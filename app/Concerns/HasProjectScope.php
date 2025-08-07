<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Context;

trait HasProjectScope
{
    public static function bootHasProjectScope(): void
    {
        // on api requests, I want to make sure
        // that the project_id from the current project always are used
        $requestUrL = Context::get('request_url') ?? request()->fullUrl();
        if (! str($requestUrL)->contains('api/')) {
            return;
        }

        parent::addGlobalScope('project', function (Builder $query) {
            if ($projectId = Context::get('project_id')) {
                $query->where('project_id', $projectId);
            }
        });

        static::saving(function ($model) {
            if ($projectId = Context::get('project_id')) {
                $model->project_id = $projectId;
            }
        });
    }
}
