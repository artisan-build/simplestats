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
        Schema::create('projects', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('team_id');
            $table->timestamps();
            $table->smallInteger('usage')->default(7);
            $table->date('stats_starting_at')->nullable();
            $table->string('client_version', 32)->nullable();
            $table->integer('events_count_monthly')->default(0);
            $table->string('domain')->nullable();

            $table->foreign('team_id')
                ->references('id')
                ->on('teams')
                ->onDelete('cascade');
        });
    }
};
