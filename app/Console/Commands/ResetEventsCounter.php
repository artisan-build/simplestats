<?php

namespace App\Console\Commands;

use App\Models\Team;
use Illuminate\Console\Command;

class ResetEventsCounter extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:reset-events-counter';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset events_count_monthly for teams based on their timezone';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        // avoid bugs if the command takes longer
        $stickyNow = now()->utc();

        Team::with('owner')->get()->each(function (Team $team) use ($stickyNow) {
            if (empty($team->owner)) {
                return;
            }

            $tzNow = $stickyNow->copy()->setTimezone($team->timezone ?? config('app.timezone'));

            $lastReset = $team->events_reset_at;

            $wasResetToday = $lastReset ? $lastReset->isSameDay($tzNow) : false;

            if (! $wasResetToday && $tzNow->day === 1) {
                $team->update([
                    'events_count_monthly' => 0,
                    'events_reset_at' => $tzNow,
                ]);
            }
        });
    }
}
