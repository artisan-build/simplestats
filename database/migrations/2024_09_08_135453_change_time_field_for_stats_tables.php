<?php

use App\Models\StatsLogin;
use App\Models\StatsPayment;
use App\Models\StatsUser;
use App\Models\StatsVisitor;
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
        Schema::table('stats_users', function (Blueprint $table) {
            $table->datetime('time')->change();
        });

        Schema::table('stats_visitors', function (Blueprint $table) {
            $table->datetime('time')->change();
        });

        Schema::table('stats_logins', function (Blueprint $table) {
            $table->datetime('time')->change();
        });

        Schema::table('stats_payments', function (Blueprint $table) {
            $table->datetime('time')->change();
        });
    }
};
