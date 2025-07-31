<?php

namespace App\Notifications;

use App\Models\StatsPayment;
use App\Models\StatsUser;
use App\Models\StatsVisitor;
use App\Models\UserNotification;
use App\Services\StatsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProjectAlertPaymentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public StatsPayment $statsPayment,
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

        $subject = '💵 New payment received on '.$projectName;
        if ($this->statsPayment->statsVisitor) {
            $this->statsPayment->statsVisitor->load(StatsVisitor::statsRelations());
        } else {
            $this->statsPayment->statsUser->load(StatsUser::statsRelations());
        }

        return (new MailMessage)
            ->subject($subject)
            ->markdown('emails.project-alert-payment', [
                'projectName' => $projectName,
                'statsPerson' => $this->statsPayment->statsVisitor ?? $this->statsPayment->statsUser,
                'statsTypes' => StatsService::getStatsTypesCamelFlatMap(),
                'statsPayment' => $this->statsPayment,
            ]);
    }
}
