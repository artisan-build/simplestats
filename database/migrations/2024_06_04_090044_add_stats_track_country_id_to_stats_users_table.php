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
        Schema::table('stats_users', function (Blueprint $table) {
            $table->foreignId('stats_track_country_id')
                ->after('instance_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stats_users', function (Blueprint $table) {
            $table->dropColumn('stats_users_stats_track_country_id_foreign');
        });
    }
};
