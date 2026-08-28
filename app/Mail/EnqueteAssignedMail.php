<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Enquete;

class EnqueteAssignedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Enquete $enquete)
    {
    }

    public function build()
    {
        return $this->subject('Nouvelle assignation d\'enquête')
                    ->view('emails.enquete_assigned')
                    ->with(['enquete' => $this->enquete]);
    }
}
