<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stats_custom_events', function (Blueprint $table) {
            $table->string('id');
            $table->string('stats_user_id', 36)->nullable();
            $table->unsignedBigInteger('stats_visitor_id')->nullable();
            $table->unsignedBigInteger('stats_custom_event_name_id');
            $table->unsignedBigInteger('team_id');
            $table->unsignedBigInteger('project_id');
            $table->timestamp('time');
            $table->timestamp('time_utc');
            $table->integer('counter')->default(1);

            $table->primary(['id', 'project_id']);

            $table->index(['stats_custom_event_name_id', 'project_id', 'stats_user_id'], 'sce_event_name_project_user_idx');
            $table->index(['stats_custom_event_name_id', 'project_id', 'stats_visitor_id'], 'sce_event_name_project_visitor_idx');
            $table->index('stats_visitor_id', 'sce_visitor_idx');
            $table->index('stats_user_id');
            $table->index(['team_id', 'project_id', 'time']);
            $table->index(['team_id', 'time']);

            $table->foreign('project_id')
                ->references('id')
                ->on('projects')
                ->onDelete('cascade');

            $table->foreign('stats_custom_event_name_id')
                ->references('id')
                ->on('stats_custom_event_names')
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
