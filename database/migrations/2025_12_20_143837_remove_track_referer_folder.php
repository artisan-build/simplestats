<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // remove track_referer folder under /public/icons
        $refererFolder = public_path('icons/track_referer');

        if (is_dir($refererFolder)) {
            File::deleteDirectory($refererFolder);
        }
    }
};
