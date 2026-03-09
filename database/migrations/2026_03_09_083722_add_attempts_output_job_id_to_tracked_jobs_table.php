<?php

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
        Schema::table('tracked_jobs', function (Blueprint $table) {
            if (! Schema::hasColumn('tracked_jobs', 'job_id')) {
                $table->string('job_id')->nullable()->after('status');
            }
            if (! Schema::hasColumn('tracked_jobs', 'attempts')) {
                $table->integer('attempts')->default(0)->after('job_id');
            }
            if (! Schema::hasColumn('tracked_jobs', 'output')) {
                $table->json('output')->nullable()->after('attempts');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tracked_jobs', function (Blueprint $table) {
            $table->dropColumn(['job_id', 'attempts', 'output']);
        });
    }
};
