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
        Schema::create('stats_aggregations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('team_id');
            $table->unsignedBigInteger('project_id')->nullable();
            $table->string('filter_hash', 32);
            $table->timestamp('time');
            $table->string('type');
            $table->json('data');
            $table->timestamp('selected_at')->nullable();

            $table->unique(['filter_hash', 'time']);

            $table->index('project_id');
            $table->index('team_id');

            $table->foreign('project_id')
                ->references('id')
                ->on('projects')
                ->onDelete('cascade');

            $table->foreign('team_id')
                ->references('id')
                ->on('teams')
                ->onDelete('cascade');
        });
    }
};
