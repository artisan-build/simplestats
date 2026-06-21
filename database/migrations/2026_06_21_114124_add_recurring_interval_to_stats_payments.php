<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stats_payments', function (Blueprint $table) {
            $table->string('recurring_interval', 10)->nullable()->after('currency');
        });
    }
};
