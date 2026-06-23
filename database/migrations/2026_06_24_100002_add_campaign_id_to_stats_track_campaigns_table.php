<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stats_track_campaigns', function (Blueprint $table) {
            // Links a tracked utm_campaign dimension to a user-managed Campaign.
            // Null = not (yet) attributed to any campaign.
            $table->unsignedBigInteger('campaign_id')->nullable()->after('name');

            $table->foreign('campaign_id')->references('id')->on('campaigns')->nullOnDelete();
        });
    }
};
