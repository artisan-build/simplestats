<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_spends', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('team_id');
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('stats_track_campaign_id');
            $table->unsignedBigInteger('amount');
            $table->string('currency', 3);
            $table->date('starts_on');
            $table->date('ends_on');
            $table->timestamps();

            $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('stats_track_campaign_id')->references('id')->on('stats_track_campaigns')->onDelete('cascade');

            $table->index(['team_id', 'project_id']);
            $table->index(['starts_on', 'ends_on']);
        });
    }
};
