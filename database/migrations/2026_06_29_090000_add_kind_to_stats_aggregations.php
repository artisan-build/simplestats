<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The default backfills every existing row to 'period' synchronously, so it
        // is in place before the unique key is widened to include kind below.
        Schema::table('stats_aggregations', function (Blueprint $table) {
            $table->string('kind')->default('period')->after('filter_hash');
        });

        Schema::table('stats_aggregations', function (Blueprint $table) {
            $table->dropUnique(['filter_hash', 'time']);
            $table->unique(['filter_hash', 'time', 'kind']);

            $table->index(['team_id', 'project_id', 'type', 'kind', 'time']);
        });
    }
};
