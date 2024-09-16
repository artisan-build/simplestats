<?php

use App\Models\StatsTrackReferer;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        StatsTrackReferer::where('name', 'www.google.com')->update(['name' => 'google.com']);
    }
};
