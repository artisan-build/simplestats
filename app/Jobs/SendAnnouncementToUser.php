<?php

namespace App\Jobs;

use App\Mail\AnnouncementMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendAnnouncementToUser implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $recipientName,
        public string $recipientEmail,
        public string $subject,
        public string $markdownContent,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Mail::to($this->recipientEmail)->send(
            new AnnouncementMail($this->recipientName, $this->subject, $this->markdownContent)
        );
    }
}
