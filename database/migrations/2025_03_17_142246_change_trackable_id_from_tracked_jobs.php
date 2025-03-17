<?php

use App\Helpers\AppHelper;
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
        if (AppHelper::getDBDriver() === 'pgsql') {
            DB::statement('ALTER TABLE tracked_jobs ALTER COLUMN trackable_id TYPE INTEGER USING trackable_id::INTEGER;');
        }
    }
};
