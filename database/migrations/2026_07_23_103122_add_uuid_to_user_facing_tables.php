<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const array TABLES = [
        'teams',
        'campaigns',
        'stats_track_campaigns',
        'ad_spends',
        'data_imports',
        'custom_stats_range_presets',
        'user_notifications',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->uuid('uuid')->nullable()->after('id');
            });

            DB::table($tableName)->whereNull('uuid')->update(['uuid' => DB::raw('gen_random_uuid()')]);

            Schema::table($tableName, function (Blueprint $table): void {
                $table->uuid('uuid')->nullable(false)->change();
                $table->unique('uuid');
            });
        }
    }
};
