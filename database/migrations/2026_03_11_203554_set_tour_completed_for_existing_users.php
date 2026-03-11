<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        User::query()
            ->whereNull('settings->tourCompleted')
            ->orWhere('settings->tourCompleted', false)
            ->each(function (User $user) {
                $user->updateSettings('tourCompleted', true);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
