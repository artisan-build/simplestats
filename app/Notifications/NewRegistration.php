<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

class NewRegistration extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(
        private User $user
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $subject = Lang::get('New registration for :app_name', ['app_name' => config('app.name')]);

        return (new MailMessage)
            ->subject($subject)
            ->greeting($subject)
            ->line('_')
            ->line(Lang::get('Name: :username', ['username' => $this->user->name]))
            ->line(Lang::get('E-Mail-Address: :usermail', ['usermail' => $this->user->email]));
    }
}
