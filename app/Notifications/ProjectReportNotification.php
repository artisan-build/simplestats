<?php

namespace App\Notifications;

use App\Models\UserNotification;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProjectReportNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public UserNotification $notification,
        public Carbon $time,
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
        $subject = config('app.name').' '.
            ucfirst($this->notification->frequency->value).' '.
            class_basename($this->notification->notifiable->name).' '.
            'Report';

        return (new MailMessage)
            ->subject($subject)
            ->markdown('emails.project-report', [
                //'notification' => $this->notification,
            ]);
    }
}
