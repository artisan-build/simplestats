<?php

namespace App\Mail;

use App\Enums\FeedbackType;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FeedbackMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public FeedbackType $type,
        public string $feedbackMessage,
    ) {}

    public function build()
    {
        return $this->subject('['.config('app.name').'] '.$this->type->title().' from '.$this->user->name)
            ->replyTo($this->user->email, $this->user->name)
            ->markdown('emails.feedback', [
                'user' => $this->user,
                'type' => $this->type,
                'feedbackMessage' => $this->feedbackMessage,
            ]);
    }
}
