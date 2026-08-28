<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;
use App\Models\Enquete;

class EnqueteAssigned extends Notification
{
    use Queueable;

    public function __construct(protected Enquete $enquete)
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
            'message' => 'Vous avez été assigné à une enquête',
        ];
    }

    public function toArray($notifiable)
    {
        return $this->toDatabase($notifiable);
    }
}
