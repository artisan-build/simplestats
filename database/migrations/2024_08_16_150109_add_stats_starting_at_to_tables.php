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
        Schema::table('teams', function (Blueprint $table) {
            $table->date('stats_starting_at')->nullable();
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->date('stats_starting_at')->nullable();
        });

        Schema::table('instances', function (Blueprint $table) {
            $table->date('stats_starting_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn('stats_starting_at');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('stats_starting_at');
        });

        Schema::table('instances', function (Blueprint $table) {
            $table->dropColumn('stats_starting_at');
        });
    }
};
