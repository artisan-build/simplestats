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
        StatsUser::query()->update(['time_utc' => DB::raw('time')]);
        StatsVisitor::query()->update(['time_utc' => DB::raw('time')]);
        StatsLogin::query()->update(['time_utc' => DB::raw('time')]);
        StatsPayment::query()->update(['time_utc' => DB::raw('time')]);
    }
};
