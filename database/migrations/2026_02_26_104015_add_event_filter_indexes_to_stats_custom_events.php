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
        Schema::table('stats_custom_events', function (Blueprint $table) {
            $table->index(['stats_custom_event_name_id', 'project_id', 'stats_user_id'], 'sce_event_name_project_user_idx');
            $table->index(['stats_custom_event_name_id', 'project_id', 'stats_visitor_id'], 'sce_event_name_project_visitor_idx');
            $table->index('stats_visitor_id', 'sce_visitor_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stats_custom_events', function (Blueprint $table) {
            $table->dropIndex('sce_event_name_project_user_idx');
            $table->dropIndex('sce_event_name_project_visitor_idx');
            $table->dropIndex('sce_visitor_idx');
        });
    }
};
