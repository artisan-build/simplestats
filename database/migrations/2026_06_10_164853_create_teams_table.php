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
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name');
            $table->boolean('personal_team');
            $table->timestamps();
            $table->string('currency', 3)->nullable();
            $table->string('timezone')->nullable();
            $table->timestamp('events_reset_at')->nullable();
            $table->integer('events_count_monthly')->default(0);
            $table->integer('events_count_overall')->default(0);

            $table->index('user_id');
        });
    }
};
