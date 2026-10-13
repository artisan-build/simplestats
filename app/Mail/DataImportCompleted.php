<?php

namespace App\Mail;

use App\Models\DataImport;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DataImportCompleted extends Mailable
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
        $subject = ucfirst($this->dataImport->type->value).' '.__('Data Import Completed');

        return $this->markdown('emails.data-import-completed')->subject($subject);
    }
}
