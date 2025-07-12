<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

class UserDeletedNotification extends Notification
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
            ->subject('Your Account Has Been Deleted')
            ->greeting(Lang::get('Hi :user,', ['user' => $this->user->name]))
            ->line('We wanted to inform you that your account has been deleted due to prolonged inactivity.')
            ->line('This means that all data associated with your account has been permanently removed and can no longer be recovered.');
    }
}
