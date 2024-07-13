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
        Schema::create('stats_visitors', function (Blueprint $table) {
            $table->id('id');
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('instance_id')->constrained()->cascadeOnDelete();

            $table->string('visitor_hash', 32)->unique();

            $table->foreignId('stats_location_country_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('stats_location_region_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('stats_location_city_id')->nullable()->constrained()->nullOnDelete();

            $table->foreignId('stats_device_type_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('stats_device_platform_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('stats_device_browser_id')->nullable()->constrained()->nullOnDelete();

            $table->foreignId('stats_track_referer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('stats_track_source_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('stats_track_medium_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('stats_track_campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('stats_track_term_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('stats_track_content_id')->nullable()->constrained()->nullOnDelete();

            $table->foreignId('stats_page_entry_id')->nullable()->constrained()->nullOnDelete();

            $table->date('time')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stats_visitors');
    }
};
