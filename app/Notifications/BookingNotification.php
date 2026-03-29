<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BookingNotification extends Notification
{
    use Queueable;

    protected $data;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toDatabase($notifiable)
    {
        return [
            'title' => $this->data['title'] ?? 'New Booking',
            'message' => $this->data['message'] ?? '',
            'booking_id' => $this->data['booking_id'] ?? null,
            'contact_id' => $this->data['contact_id'] ?? null,
            'location_id' => $this->data['location_id'] ?? null,
            'type' => 'booking_created',
            'icon_class' => 'fas fa-calendar bg-blue',
            'link' => '/restaurant/booking',
        ];
    }
}
