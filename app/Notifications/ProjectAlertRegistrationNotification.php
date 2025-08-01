<?php

namespace App\Notifications;

use App\Models\StatsUser;
use App\Models\UserNotification;
use App\Services\StatsService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * currently we can't use ShouldQueue here because the notification is already triggered by a queued job,
 * and we would get issues with the unserialization of the StatsPayment model, cause of missing team context
 */
class ProjectAlertRegistrationNotification extends Notification
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

        $mail = (new MailMessage)
            ->subject($subject)
            ->markdown('emails.project-alert-registration', [
                'projectName' => $projectName,
                'statsUser' => $this->statsUser,
                'statsTypes' => StatsService::getStatsTypesCamelFlatMap(),
            ]);

        $this->notification->update(['sent_at' => now()]);

        return $mail;
    }
}
