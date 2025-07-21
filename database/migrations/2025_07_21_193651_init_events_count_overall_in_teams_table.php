<?php

use App\Models\Team;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Team::all()->each(function (Team $team) {
            $team->update(['events_count_overall' => 1]);
        });
    }
};
