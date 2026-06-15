<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Custom events no longer use a tracking usage bit (display is now driven
     * dynamically by StatsCustomEventService::hasCustomEvents). Clear the
     * retired events bit (8) from every project's usage bitmask so no value
     * exceeds the new max (ProjectRequest validates against
     * StatsService::getTrackingUsageBitsCount(), now 7).
     */
    public function up(): void
    {
        DB::table('projects')
            ->whereRaw('(usage & 8) = 8')
            ->update(['usage' => DB::raw('usage - 8')]);
    }

    /**
     * Best-effort restore: set the events bit where it is missing.
     */
    public function down(): void
    {
        DB::table('projects')
            ->whereRaw('(usage & 8) = 0')
            ->update(['usage' => DB::raw('usage + 8')]);
    }
};
