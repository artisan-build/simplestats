<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

class UserInactiveNotification extends Notification
{
    public function __construct(
        public User $user
    ) {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Action Required: Your Account Will Be Deleted Soon Due to Inactivity')
            ->greeting(Lang::get('Hi :user,', ['user' => $this->user->name]))
            ->line('We noticed that you haven’t logged into your account for quite some time.
            To ensure our platform remains active and efficient for all users,
            we periodically delete accounts that have been inactive for extended periods.')
            ->line('Your account is scheduled for deletion in 10 days due to inactivity.')
            ->line('If you wish to keep your account, simply log in before that day.
            This will confirm that you’d like to continue using our service.');
    }

    public function toArray($notifiable): array
    {
        return [];
    }
}
