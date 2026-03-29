<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MaintenanceNoteUpdated extends Notification
{
    use Queueable;

    protected array $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function via(mixed $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(mixed $notifiable): array
    {
        return $this->payload;
    }
}
