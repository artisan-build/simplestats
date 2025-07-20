<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Context;

trait HasInstanceScope
{
    public static function bootHasInstanceScope(): void
    {
        // on api requests, I want to make sure
        // that the instance_id and project_id from the current instance always are used
        $requestUrL = Context::get('request_url') ?? request()->fullUrl();
        if (! str($requestUrL)->contains('api/')) {
            return;
        }

        parent::addGlobalScope('instance', function (Builder $query) {
            if ($instanceId = Context::get('instance_id')) {
                $query->where('instance_id', $instanceId);
            }

            if ($projectId = Context::get('project_id')) {
                $query->where('project_id', $projectId);
            }
        });

        static::saving(function ($model) {
            if ($instanceId = Context::get('instance_id')) {
                $model->instance_id = $instanceId;
            }

            if ($projectId = Context::get('project_id')) {
                $model->project_id = $projectId;
            }
        });
    }
}
