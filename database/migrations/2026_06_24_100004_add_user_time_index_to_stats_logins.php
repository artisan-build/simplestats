<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The retention cohorts join a range-scoped set of cohort users to their
     * return logins on (stats_user_id, project_id) and filter by time. Only a
     * standalone stats_user_id index existed, leaving the time predicate to a
     * post-fetch filter; this composite lets one index serve the whole join.
     */
    public function up(): void
    {
        Schema::table('stats_logins', function (Blueprint $table) {
            $table->index(['stats_user_id', 'project_id', 'time']);
        });
    }
};
