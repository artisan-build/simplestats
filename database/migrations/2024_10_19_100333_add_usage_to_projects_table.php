<?php

use App\Services\StatsService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->unsignedTinyInteger('usage')->default(StatsService::getTrackingUsageBitsCount())->after('team_id');
        });
    }
};
