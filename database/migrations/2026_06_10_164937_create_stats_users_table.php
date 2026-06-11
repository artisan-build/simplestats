<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stats_users', function (Blueprint $table) {
            $table->string('id', 36);
            $table->unsignedBigInteger('team_id');
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('stats_track_source_id')->nullable();
            $table->unsignedBigInteger('stats_track_medium_id')->nullable();
            $table->unsignedBigInteger('stats_track_campaign_id')->nullable();
            $table->unsignedBigInteger('stats_track_term_id')->nullable();
            $table->unsignedBigInteger('stats_track_content_id')->nullable();
            $table->timestamp('time');
            $table->unsignedBigInteger('stats_location_country_id')->nullable();
            $table->unsignedBigInteger('stats_location_region_id')->nullable();
            $table->unsignedBigInteger('stats_location_city_id')->nullable();
            $table->unsignedBigInteger('stats_device_type_id')->nullable();
            $table->unsignedBigInteger('stats_device_platform_id')->nullable();
            $table->unsignedBigInteger('stats_device_browser_id')->nullable();
            $table->unsignedBigInteger('stats_track_referer_id')->nullable();
            $table->unsignedBigInteger('stats_page_entry_id')->nullable();
            $table->timestamp('time_utc')->default(DB::raw('CURRENT_TIMESTAMP'));

            $table->primary(['id', 'project_id']);

            $table->index(['team_id', 'project_id', 'time']);
            $table->index(['team_id', 'time']);

            $table->foreign('project_id')
                ->references('id')
                ->on('projects')
                ->onDelete('cascade');

            $table->foreign('stats_device_browser_id')
                ->references('id')
                ->on('stats_device_browsers')
                ->onDelete('set null');

            $table->foreign('stats_device_platform_id')
                ->references('id')
                ->on('stats_device_platforms')
                ->onDelete('set null');

            $table->foreign('stats_device_type_id')
                ->references('id')
                ->on('stats_device_types')
                ->onDelete('set null');

            $table->foreign('stats_location_city_id')
                ->references('id')
                ->on('stats_location_cities')
                ->onDelete('set null');

            $table->foreign('stats_location_country_id')
                ->references('id')
                ->on('stats_location_countries')
                ->onDelete('set null');

            $table->foreign('stats_location_region_id')
                ->references('id')
                ->on('stats_location_regions')
                ->onDelete('set null');

            $table->foreign('stats_page_entry_id')
                ->references('id')
                ->on('stats_page_entries')
                ->onDelete('set null');

            $table->foreign('stats_track_campaign_id')
                ->references('id')
                ->on('stats_track_campaigns')
                ->onDelete('set null');

            $table->foreign('stats_track_content_id')
                ->references('id')
                ->on('stats_track_contents')
                ->onDelete('set null');

            $table->foreign('stats_track_medium_id')
                ->references('id')
                ->on('stats_track_media')
                ->onDelete('set null');

            $table->foreign('stats_track_referer_id')
                ->references('id')
                ->on('stats_track_referers')
                ->onDelete('set null');

            $table->foreign('stats_track_source_id')
                ->references('id')
                ->on('stats_track_sources')
                ->onDelete('set null');

            $table->foreign('stats_track_term_id')
                ->references('id')
                ->on('stats_track_terms')
                ->onDelete('set null');

            $table->foreign('team_id')
                ->references('id')
                ->on('teams')
                ->onDelete('cascade');
        });
    }
};
