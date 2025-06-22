<?php

use App\Services\StatsService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Artisan::call('app:merge-stats-types');

        StatsService::getStatsTypes()->each(function ($types) {
            collect($types)->each(function ($statsType) {
                Schema::table((new $statsType['model'])->getTable(), function (Blueprint $table) {
                    $table->dropForeign(['project_id']);
                    $table->dropColumn('project_id');
                });
            });
        });
    }
};
