<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stats_users', function (Blueprint $table) {
            $table->dropIndex('stats_users_time_index');
        });

        Schema::table('stats_visitors', function (Blueprint $table) {
            $table->dropIndex('stats_visitors_time_index');
        });

        Schema::table('stats_payments', function (Blueprint $table) {
            $table->dropIndex('stats_payments_time_index');
            $table->dropIndex('stats_payments_counter_index');
        });

        Schema::table('stats_logins', function (Blueprint $table) {
            $table->dropIndex('stats_logins_time_index');
            $table->dropIndex('stats_logins_counter_index');
        });

        Schema::table('stats_users', function (Blueprint $table) {
            $table->index(['team_id', 'time']);
            $table->index(['team_id', 'project_id', 'time']);
            $table->index(['team_id', 'instance_id', 'time']);
        });

        Schema::table('stats_visitors', function (Blueprint $table) {
            $table->index(['team_id', 'time']);
            $table->index(['team_id', 'project_id', 'time']);
            $table->index(['team_id', 'instance_id', 'time']);
        });

        Schema::table('stats_payments', function (Blueprint $table) {
            $table->index(['team_id', 'time']);
            $table->index(['team_id', 'project_id', 'time']);
            $table->index(['team_id', 'instance_id', 'time']);
        });

        Schema::table('stats_logins', function (Blueprint $table) {
            $table->index(['team_id', 'time']);
            $table->index(['team_id', 'project_id', 'time']);
            $table->index(['team_id', 'instance_id', 'time']);
        });
    }
};
