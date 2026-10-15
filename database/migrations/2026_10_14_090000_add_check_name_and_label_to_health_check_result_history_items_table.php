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
            if (! Schema::hasColumn('health_check_result_history_items', 'check_name')) {
                $table->string('check_name')->default('');
            }

            if (! Schema::hasColumn('health_check_result_history_items', 'check_label')) {
                $table->string('check_label')->default('');
            }
        });
    }
};
