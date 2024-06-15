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
            $table->foreignId('stats_location_country_id')->after('instance_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('stats_location_region_id')->after('stats_location_country_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('stats_location_city_id')->after('stats_location_region_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stats_users', function (Blueprint $table) {
            $table->dropColumn('stats_users_stats_location_country_id_foreign');
            $table->dropColumn('stats_users_stats_location_region_id_foreign');
            $table->dropColumn('stats_users_stats_location_city_id_foreign');
        });
    }
};
