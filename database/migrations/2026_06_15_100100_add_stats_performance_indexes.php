<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Quick-win indexes for the stats read/maintenance path (Postgres-only).
     */
    public function up(): void
    {
        Schema::table('stats_payments', function (Blueprint $table) {
            // FKs do not create an index in Postgres. Needed for the visitor
            // branch of the payment joins and for ON DELETE CASCADE of stats_visitors.
            $table->index('stats_visitor_id');

            // Covers the EXISTS subqueries in pd_dapu/pd_daapu and the 20-day
            // window in daapu (lookup by user within a project, bounded by time).
            $table->index(['stats_user_id', 'project_id', 'time'], 'stats_payments_user_project_time_index');
        });

        // BRIN indexes on the append-only, time-ordered tables: tiny on disk and
        // very cheap on insert, effective for the time range filters. Additive to
        // the existing B-tree composites, not a replacement.
        DB::statement('CREATE INDEX stats_visitors_time_brin_index ON stats_visitors USING brin (time)');
        DB::statement('CREATE INDEX stats_logins_time_brin_index ON stats_logins USING brin (time)');
    }
};
