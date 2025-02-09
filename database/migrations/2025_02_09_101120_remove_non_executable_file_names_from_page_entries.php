<?php

use App\Helpers\FileHelper;
use App\Models\StatsPageEntry;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        StatsPageEntry::where(function ($query) {
            foreach (FileHelper::getFileExtensions4NonWebExecutables() as $ext) {
                $query->orWhere('name', 'like', "%.{$ext}");
            }
        })->delete();
    }
};
