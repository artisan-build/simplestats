<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('data_imports', function (Blueprint $table) {
            $table->dropColumn(['id_from', 'id_to']);
        });
    }
};
