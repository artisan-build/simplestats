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
        Schema::create('stats_visitors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id');
            $table->unsignedBigInteger('project_id');
            $table->string('visitor_hash');
            $table->unsignedBigInteger('stats_location_country_id')->nullable();
            $table->unsignedBigInteger('stats_location_region_id')->nullable();
            $table->unsignedBigInteger('stats_location_city_id')->nullable();
            $table->unsignedBigInteger('stats_device_type_id')->nullable();
            $table->unsignedBigInteger('stats_device_platform_id')->nullable();
            $table->unsignedBigInteger('stats_device_browser_id')->nullable();
            $table->unsignedBigInteger('stats_track_referer_id')->nullable();
            $table->unsignedBigInteger('stats_track_source_id')->nullable();
            $table->unsignedBigInteger('stats_track_medium_id')->nullable();
            $table->unsignedBigInteger('stats_track_campaign_id')->nullable();
            $table->unsignedBigInteger('stats_track_term_id')->nullable();
            $table->unsignedBigInteger('stats_track_content_id')->nullable();
            $table->unsignedBigInteger('stats_page_entry_id')->nullable();
            $table->timestamp('time');
            $table->timestamp('time_utc')->default(DB::raw('CURRENT_TIMESTAMP'));

            $table->index(['team_id', 'project_id', 'time']);
            $table->index(['team_id', 'time']);
            $table->unique(['team_id', 'project_id', 'visitor_hash']);

            // Postgres does not auto-create an index for FK columns (unlike MySQL).
            // project_id needs its own index for the ON DELETE CASCADE on project removal;
            // the composites above only cover it as a non-leading column.
            $table->index('project_id');

            // The lookup-dimension FKs (stats_track_*, stats_location_*, stats_device_*,
            // stats_page_entry_id) are intentionally left unindexed: they are ON DELETE SET NULL
            // on append-only lookup tables (cascade never fires) and are only ever secondary
            // WHERE refinements on top of the (team_id, project_id, time) scan, whose results are
            // cached in stats_aggregations. Indexing all of them would be pure insert overhead.

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

        // BRIN index on the append-only, time-ordered table: tiny on disk and very cheap on
        // insert, effective for the time range filters. Additive to the B-tree composites above.
        DB::statement('CREATE INDEX stats_visitors_time_brin_index ON stats_visitors USING brin (time)');
    }
};
