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
        Schema::table('health_check_result_history_items', function (Blueprint $table): void {
            $table->string('check_name')->default('');
            $table->string('check_label')->default('');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('health_check_result_history_items', function (Blueprint $table): void {
            $table->dropColumn(['check_name', 'check_label']);
        });
    }
};
