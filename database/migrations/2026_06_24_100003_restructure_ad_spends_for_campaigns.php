<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ad spend now hangs off a Campaign (which carries the project), not the
        // raw utm dimension. Existing rows are demo-only and get reseeded.
        DB::table('ad_spends')->delete();

        Schema::table('ad_spends', function (Blueprint $table): void {
            $table->dropForeign(['project_id']);
            $table->dropForeign(['stats_track_campaign_id']);
            $table->dropIndex(['team_id', 'project_id']);
            $table->dropColumn(['project_id', 'stats_track_campaign_id']);

            $table->unsignedBigInteger('campaign_id')->after('team_id');
            $table->foreign('campaign_id')->references('id')->on('campaigns')->onDelete('cascade');
            $table->index(['team_id', 'campaign_id']);
        });
    }
};
