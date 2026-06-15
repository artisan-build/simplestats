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
        Schema::create('stats_payments', function (Blueprint $table) {
            $table->string('id', 36);
            $table->string('stats_user_id', 36)->nullable();
            $table->unsignedBigInteger('team_id');
            $table->unsignedBigInteger('project_id');
            $table->double('gross');
            $table->double('net');
            $table->string('currency', 3);
            $table->double('orig_gross')->nullable();
            $table->double('orig_net')->nullable();
            $table->string('orig_currency', 3)->nullable();
            $table->timestamp('time');
            $table->integer('counter');
            $table->timestamp('time_utc')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->unsignedBigInteger('stats_visitor_id')->nullable();

            $table->primary(['id', 'project_id']);

            $table->index('stats_user_id');
            $table->index(['team_id', 'project_id', 'time']);
            $table->index(['team_id', 'time']);

            // Postgres does not auto-create an index for FK columns (unlike MySQL).
            // project_id needs its own index for the ON DELETE CASCADE on project removal;
            // stats_visitor_id for the visitor branch of the payment joins and its CASCADE.
            // The composite [stats_user_id, project_id] FK is served by the stats_user_id index.
            $table->index('project_id');
            $table->index('stats_visitor_id');

            // Covers the EXISTS subqueries in pd_dapu/pd_daapu and the 20-day window in daapu
            // (lookup by user within a project, bounded by time).
            $table->index(['stats_user_id', 'project_id', 'time'], 'stats_payments_user_project_time_index');

            $table->foreign('project_id')
                ->references('id')
                ->on('projects')
                ->onDelete('cascade');

            $table->foreign(['stats_user_id', 'project_id'])
                ->references(['id', 'project_id'])
                ->on('stats_users')
                ->onDelete('cascade');

            $table->foreign('stats_visitor_id')
                ->references('id')
                ->on('stats_visitors')
                ->onDelete('cascade');

            $table->foreign('team_id')
                ->references('id')
                ->on('teams')
                ->onDelete('cascade');
        });
    }
};
