<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Artisan;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        if (!app()->isProduction() && app()->environment('testing')) {
            $schedule->command('migrate:fresh --seed')
                ->daily()
                ->before(fn() => Artisan::call('down'))
                ->after(fn() => Artisan::call('up'))
                ->runInBackground()
                ->withoutOverlapping()
                ->onOneServer();
        }
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
