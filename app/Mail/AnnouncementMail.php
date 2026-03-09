<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AnnouncementMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public string $recipientName,
        public string $title,
        public string $markdownContent,
    ) {}

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): static
    {
        return $this->subject($this->title)
            ->markdown('emails.announcement', [
                'recipientName' => $this->recipientName,
                'markdownContent' => $this->markdownContent,
            ]);
    }
}
