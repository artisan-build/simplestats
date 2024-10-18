<?php

namespace App\Mail;

use App\Models\DataImport;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CustomMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public User $user,
        public string $message,
    )
    {}

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Hello!')
            ->markdown('emails.custom-mail', [
                'user' => $this->user,
                'message' => $this->message,
            ]);
    }
}
