<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Enquete;

class EnqueteStatusChangedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Enquete $enquete, public string $statut)
    {
    }

    public function build()
    {
        return $this->subject('Statut de votre enquête modifié')
                    ->view('emails.enquete_status_changed')
                    ->with(['enquete' => $this->enquete, 'statut' => $this->statut]);
    }
}
