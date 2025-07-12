<?php

namespace App\Notifications;

use App\Enums\UserNotificationFrequency;
use App\Models\UserNotification;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use DateTimeInterface;
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
        public CarbonImmutable $time,
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
        $projectName = class_basename($this->notification->notifiable->name);

        $subject = config('app.name').' '.
            $this->notification->frequency->title().' '.
            $projectName.' '.
            'Report';

        return (new MailMessage)
            ->subject($subject)
            ->markdown('emails.project-report', [
                'frequency' => $this->notification->frequency,
                'timeRange' => $this->getTimeRange(),
                'projectName' => $projectName,
            ]);
    }

    private function getTimeRange(): string
    {
        return match ($this->notification->frequency) {
            UserNotificationFrequency::HOURLY => $this->time->format('M jS g:00a'),
            UserNotificationFrequency::DAILY => $this->time->subDay()->format('M jS'),
            UserNotificationFrequency::WEEKLY => $this->time->subWeek()->startOfWeek()->format('M jS').' - '.$this->time->subWeek()->endOfWeek()->format('M jS'),
            UserNotificationFrequency::MONTHLY => $this->time->subMonth()->startOfMonth()->format('M jS').' - '.$this->time->subMonth()->endOfMonth()->format('M jS'),
            default => '',
        };
    }
}
