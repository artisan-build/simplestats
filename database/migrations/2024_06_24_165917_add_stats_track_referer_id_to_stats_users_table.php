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
            $table->foreignId('stats_track_referer_id')->after('stats_device_browser_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stats_users', function (Blueprint $table) {
            $table->dropForeign('stats_users_stats_referer_id_foreign');
        });
    }
};
