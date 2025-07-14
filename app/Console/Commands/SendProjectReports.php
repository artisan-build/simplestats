<?php

namespace App\Console\Commands;

use App\Enums\UserNotificationFrequency;
use App\Models\Project;
use App\Models\UserNotification;
use App\Notifications\ProjectReportNotification;
use Illuminate\Console\Command;

class SendProjectReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-project-reports';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send stats project reports to users';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        // avoid bugs if the command takes longer than a minute...
        $stickyNow = now()->utc();

        Project::withWhereHas('userNotifications.user:id,name,email')
            ->with(['team:id,name,timezone,currency', 'userNotifications.notifiable'])
            ->each(function ($project) use ($stickyNow) {
                $project->userNotifications->each(function (UserNotification $userNotification) use ($stickyNow, $project) {
                    $tzNow = $stickyNow->copy()->setTimezone($project->team->timezone ?? config('app.timezone'));

                    $shouldSend = match ($userNotification->frequency) {
                        UserNotificationFrequency::DAILY => $tzNow->format('H:i') === '00:00',
                        UserNotificationFrequency::WEEKLY => $tzNow->format('H:i') === '00:00' && $tzNow->isMonday(),
                        UserNotificationFrequency::MONTHLY => $tzNow->format('H:i') === '00:00' && $tzNow->isSameDay($tzNow->copy()->startOfMonth()),
                        default => false,
                    };

                    if ($shouldSend) {
                        $userNotification->user->notify(new ProjectReportNotification($userNotification, $tzNow->copy(), $project->team));
                    }
                });
            });
    }
}
