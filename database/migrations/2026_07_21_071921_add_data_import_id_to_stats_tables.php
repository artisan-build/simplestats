<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['stats_users', 'stats_payments'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->unsignedBigInteger('data_import_id')->nullable()->index();

                $table->foreign('data_import_id')
                    ->references('id')
                    ->on('data_imports')
                    ->nullOnDelete();
            });
        }
    }
};
