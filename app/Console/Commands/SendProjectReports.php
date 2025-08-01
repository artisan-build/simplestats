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
    protected $signature = 'app:send-project-reports {project? : The ID of the project to send reports for}';

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
        // avoid bugs if the command takes longer
        $stickyNow = now()->utc();

        Project::withWhereHas('userReportNotifications.user')
            ->when($this->argument('project'), fn ($query, $project) => $query->where('id', $project))
            ->with(['team:id,name,timezone,currency', 'userReportNotifications.notifiable'])
            ->each(function ($project) use ($stickyNow) {
                $project->userReportNotifications->each(function (UserNotification $userNotification) use ($stickyNow, $project) {
                    $lastSent = $userNotification->sent_at;
                    $tzNow = $stickyNow->copy()->setTimezone($project->team->timezone ?? config('app.timezone'));

                    $alreadySent = match ($userNotification->frequency) {
                        UserNotificationFrequency::DAILY => $lastSent?->isSameDay($tzNow),
                        UserNotificationFrequency::WEEKLY => $lastSent?->isSameWeek($tzNow),
                        UserNotificationFrequency::MONTHLY => $lastSent?->format('Y-m') === $tzNow->format('Y-m'),
                        default => false,
                    };

                    $shouldSend = ! $alreadySent && match ($userNotification->frequency) {
                        UserNotificationFrequency::DAILY => true,
                        UserNotificationFrequency::WEEKLY => $tzNow->isMonday(),
                        UserNotificationFrequency::MONTHLY => $tzNow->day === 1,
                        default => false,
                    };

                    // If the user has not set any tracking settings, we skip sending the notification
                    if ($shouldSend && array_filter($userNotification->settings['tracking'] ?? [])) {
                        $userNotification->user->notify(
                            new ProjectReportNotification($userNotification, $tzNow->copy(), $project->team)
                        );
                    }
                });
            });
    }
}
