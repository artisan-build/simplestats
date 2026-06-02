<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * One-off cleanup of historical data where a project's own domain was recorded
     * as a referer/source (in-app navigation or a stale tracking session leaking the
     * own host). The actual work is pushed onto the queue so a large dataset never
     * blocks the deploy/migration; the idempotent app:reset-own-domain-referers
     * command does the heavy lifting on a worker and can safely run again.
     */
    public function up(): void
    {
        // Guard so this historical migration keeps working even if the command is
        // removed later (fresh installs replay the full migration history).
        if (! array_key_exists('app:reset-own-domain-referers', Artisan::all())) {
            return;
        }

        dispatch(function () {
            // Re-check on the worker: the command must still exist when the job runs.
            if (array_key_exists('app:reset-own-domain-referers', Artisan::all())) {
                Artisan::call('app:reset-own-domain-referers');
            }
        })->onQueue('low');
    }
};
