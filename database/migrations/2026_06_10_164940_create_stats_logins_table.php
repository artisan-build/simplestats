<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stats_logins', function (Blueprint $table) {
            $table->id();
            $table->string('stats_user_id', 36);
            $table->unsignedBigInteger('team_id');
            $table->unsignedBigInteger('project_id');
            $table->timestamp('time');
            $table->integer('counter');
            $table->timestamp('time_utc')->default(DB::raw('CURRENT_TIMESTAMP'));

            $table->index('stats_user_id');
            $table->index(['team_id', 'project_id', 'time']);
            $table->index(['team_id', 'time']);

            // Postgres does not auto-create an index for FK columns (unlike MySQL).
            // project_id needs its own index for the ON DELETE CASCADE on project removal.
            // The composite [stats_user_id, project_id] FK is already served by the
            // stats_user_id index above via its leading column.
            $table->index('project_id');

            $table->foreign('project_id')
                ->references('id')
                ->on('projects')
                ->onDelete('cascade');

            $table->foreign(['stats_user_id', 'project_id'])
                ->references(['id', 'project_id'])
                ->on('stats_users')
                ->onDelete('cascade');

            $table->foreign('team_id')
                ->references('id')
                ->on('teams')
                ->onDelete('cascade');
        });

        // BRIN index on the append-only, time-ordered table: tiny on disk and very cheap on
        // insert, effective for the time range filters. Additive to the B-tree composites above.
        DB::statement('CREATE INDEX stats_logins_time_brin_index ON stats_logins USING brin (time)');
    }
};
