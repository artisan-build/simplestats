<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stats_users', function (Blueprint $table): void {
            // defaults true so existing and bulk-imported rows count as registered; only the
            // placeholder shells from HasStatsUserAssurance set it false until the real
            // registration ingest lands (otherwise the alert fires on whichever shell-creating
            // job wins the ingest race, with empty tracking data).
            $table->boolean('registered')->default(true)->after('time_utc');
        });
    }
};
