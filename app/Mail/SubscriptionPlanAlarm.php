<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionPlanAlarm extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public User $teamOwner,
        public array $alarms,
    ) {}

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $subject = __('You have almost reached your tracking limit!');

        return $this->markdown('emails.subscription-plan-alarm')->subject($subject);
    }
}
