<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dateTime('events_reset_at')->nullable();
            $table->integer('events_count_monthly')->default(0);
            $table->integer('events_count_overall')->default(0);
        });
    }
};
