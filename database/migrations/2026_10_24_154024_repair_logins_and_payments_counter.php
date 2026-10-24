<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    /**
     * The nightly repair used to join payments on id only, which is not unique across
     * projects, and wrote foreign counters. Repair once on deploy instead of waiting for
     * the scheduler, which self-hosted installs may not run at all.
     */
    public function up(): void
    {
        Artisan::call('app:check-logins-and-payments-counter');
    }
};
