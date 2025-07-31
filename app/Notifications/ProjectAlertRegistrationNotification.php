<?php

namespace App\Notifications;

use App\Models\StatsUser;
use App\Models\UserNotification;
use App\Services\StatsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProjectAlertRegistrationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public StatsUser $statsUser,
        public UserNotification $notification,
    ) {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $projectName = $this->notification->notifiable->name;

        $subject = '🎉 New registration on '.$projectName;
        $this->statsUser->load(StatsUser::statsRelations());

        return (new MailMessage)
            ->subject($subject)
            ->markdown('emails.project-alert-registration', [
                'projectName' => $projectName,
                'statsUser' => $this->statsUser,
                'statsTypes' => StatsService::getStatsTypesCamelFlatMap(),
            ]);
    }
}
