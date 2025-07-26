<?php

use App\Models\StatsAggregation;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        StatsAggregation::truncate();
    }
};
