<?php

namespace Modules\Crm\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MaintenanceReminderNotification extends Notification
{
    use Queueable;

    public $prediction;
    public $message;

    public function __construct($prediction, $message)
    {
        $this->prediction = $prediction;
        $this->message = $message;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('تذكير صيانة سيارتك')
            ->greeting('عزيزي ' . $notifiable->name)
            ->line($this->message)
            ->action('احجز الآن', url('/'));
    }

    public function toArray($notifiable)
    {
        return [
            'prediction_id' => $this->prediction->id,
            'contact_id' => $this->prediction->contact_id,
            'device_id' => $this->prediction->device_id,
            'status' => $this->prediction->status,
            'message' => $this->message,
        ];
    }
}
