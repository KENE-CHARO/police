<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Enquete;

class EnqueteStatusChanged extends Notification
{
    use Queueable;

    public function __construct(protected Enquete $enquete, protected string $statut)
    {
    }

    public function via($notifiable)
    {
        return ['database', 'log'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'enquete_id' => $this->enquete->id,
            'plainte_id' => $this->enquete->plainte_id,
            'statut' => $this->statut,
            'message' => 'Le statut de l\'enquête a changé',
        ];
    }

    public function toArray($notifiable)
    {
        return $this->toDatabase($notifiable);
    }
}
