<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Context;

trait HasProjectScope
{
    public static function bootHasProjectScope(): void
    {
        // On API requests, ensure the project_id from the current project is always used.
        // The checks are inside the callbacks (not at boot time) because boot() runs once
        // per model class per process. If the model boots in a non-API context first
        // (e.g. a factory in test setup, or parallel test workers), the callbacks would
        // never be registered for subsequent API requests in the same process.
        parent::addGlobalScope('project', function (Builder $query): void {
            $requestUrl = Context::get('request_url') ?? request()->fullUrl();
            if (str($requestUrl)->contains('api/') && ($projectId = Context::get('project_id'))) {
                $query->where('project_id', $projectId);
            }
        });

        static::saving(function ($model): void {
            if ($projectId = Context::get('project_id')) {
                $model->project_id = $projectId;
            }
        });
    }
}
