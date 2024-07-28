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
        Schema::table('stats_visitors', function (Blueprint $table) {
            $table->dropUnique(['visitor_hash']);
            $table->unique(['team_id', 'instance_id', 'visitor_hash']);
        });
    }
};
