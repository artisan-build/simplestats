<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The default backfills every existing row to 'period' synchronously, so it
        // is in place before the unique key is widened to include kind below.
        Schema::table('stats_aggregations', function (Blueprint $table): void {
            $table->string('kind')->default('period')->after('filter_hash');
        });

        // The production DB was converted from MySQL via pgloader, which renamed all
        // indexes (idx_<oid>_... prefix) and turned unique constraints into plain
        // unique indexes. dropUnique() would guess the conventional name, so resolve
        // the actual name and drop it as constraint or index, whichever it is.
        $uniqueIndex = collect(Schema::getIndexes('stats_aggregations'))
            ->firstOrFail(fn (array $index): bool => $index['unique'] && $index['columns'] === ['filter_hash', 'time']);

        $isConstraint = DB::selectOne('select 1 from pg_constraint where conname = ?', [$uniqueIndex['name']]) !== null;

        DB::statement($isConstraint
            ? 'alter table stats_aggregations drop constraint "'.$uniqueIndex['name'].'"'
            : 'drop index "'.$uniqueIndex['name'].'"');

        Schema::table('stats_aggregations', function (Blueprint $table): void {
            $table->unique(['filter_hash', 'time', 'kind']);

            $table->index(['team_id', 'project_id', 'type', 'kind', 'time']);
        });
    }
};
