<?php

use App\Models\Team;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Team::with('owner')->get()->each(function ($team) {
            if (empty($team->owner)) {
                return;
            }

            if ($team->currency === 'EUR') {
                $team->update(['timezone' => 'Europe/Berlin']);
            }
        });
    }
};
