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
            $table->id();
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('instance_id')->constrained()->onDelete('cascade');
            // tracking
            $table->string('track_source')->index()->nullable();
            $table->string('track_medium')->index()->nullable();
            $table->string('track_campaign')->nullable();
            $table->string('track_term')->nullable();
            $table->string('track_content')->nullable();
            // we only need created_at here
            $table->timestamp('created_at');
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
