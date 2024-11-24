<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_login_at')->after('updated_at')->default(DB::raw('CURRENT_TIMESTAMP'));
        });

        DB::statement('UPDATE users SET last_login_at = CURRENT_TIMESTAMP - INTERVAL 50 DAY');
    }
};
