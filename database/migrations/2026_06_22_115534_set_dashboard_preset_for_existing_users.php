<?php

use App\Enums\DashboardPreset;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->orderBy('id')->chunkById(500, function ($users): void {
            foreach ($users as $user) {
                $settings = json_decode($user->settings ?? '{}', true) ?: [];
                $settings['dashboardPreset'] = DashboardPreset::EXPERT->value;

                DB::table('users')->where('id', $user->id)->update([
                    'settings' => json_encode($settings),
                ]);
            }
        });
    }
};
