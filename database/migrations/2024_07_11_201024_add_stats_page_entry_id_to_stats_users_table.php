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
            $table->foreignId('stats_page_entry_id')->after('stats_track_content_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stats_users', function (Blueprint $table) {
            $table->dropForeign('stats_users_stats_page_entry_id_foreign');
        });
    }
};
