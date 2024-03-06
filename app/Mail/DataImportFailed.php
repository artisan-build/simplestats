<?php

namespace App\Mail;

use App\Models\DataImport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DataImportFailed extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public DataImport $dataImport
    ) {}

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $subject = ucfirst($this->dataImport->type->value).' '.__('Data Import Failed');

        return $this->markdown('emails.data-import-failed')->subject($subject);
    }
}
