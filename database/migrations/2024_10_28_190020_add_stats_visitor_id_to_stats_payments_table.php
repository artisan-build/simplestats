<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stats_payments', function (Blueprint $table) {
            $table->foreignId('stats_visitor_id')->after('stats_user_id')->nullable()->constrained()->cascadeOnDelete();
        });
    }
};
