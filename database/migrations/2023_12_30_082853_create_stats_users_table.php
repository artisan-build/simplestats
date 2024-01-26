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
        Schema::create('stats_users', function (Blueprint $table) {
            /**
             * We can't make a primary key "id" here, cause as this is a SaaS where many instances
             * will send their users, a stats_user_id will only be unique on a stats_user/instance basis...
             */
            $table->unsignedBigInteger('id');
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('instance_id')->constrained()->cascadeOnDelete();
            // tracking
            $table->foreignId('stats_track_source_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('stats_track_medium_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('stats_track_campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('stats_track_term_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('stats_track_content_id')->nullable()->constrained()->nullOnDelete();

            $table->date('time')->index();

            $table->primary(['id', 'instance_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stats_users');
    }
};
