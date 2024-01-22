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
            $table->string('track_source')->index()->nullable();
            $table->string('track_medium')->index()->nullable();
            $table->string('track_campaign')->nullable();
            $table->string('track_term')->nullable();
            $table->string('track_content')->nullable();
            $table->dateTime('time');

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
