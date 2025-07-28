<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

class WelcomeMailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public User $user
    ) {
    }

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
        $appName = config('app.name');
        $subject = Lang::get('Welcome to :app_name!', ['app_name' => $appName]);

        return (new MailMessage)
            ->subject($subject)
            ->greeting(Lang::get('Hi :user_name,', ['user_name' => $this->user->name]))
            ->line(Lang::get('Welcome to :app_name! We\'re thrilled to have you with us.', ['app_name' => $appName]))
            ->line(Lang::get('If you have any questions or need assistance with getting started, we\'re just an email away. Feel free to reply [here](:support) or schedule a quick call with us using the following link: [Calendly](:call).', [
                'support' => 'https://tawk.to/chat/6601f04ea0c6737bd124880f/1hprp5d8g',
                'call' => 'https://calendly.com/simplestats/30min',
            ]))
            ->line(Lang::get('In the meantime, here\'s a quick start guide to help you set up :app_name right away: [Quick Start Guide](:guide).', [
                'app_name' => $appName,
                'guide' => config('app.url').'/docs/installation.html',
            ]))
            ->line(Lang::get('You can find your API token [here](:api_token) under **Project** > **Instance**.', [
                'api_token' => route('projects.index'),
            ]));
    }
}
