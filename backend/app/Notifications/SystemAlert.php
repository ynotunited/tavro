<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SystemAlert extends Notification
{
    use Queueable;

    public $message;
    public $type;
    public $metadata;

    /**
     * Create a new notification instance.
     */
    public function __construct($message, $type = 'info', $metadata = [])
    {
        $this->message = $message;
        $this->type = $type;
        $this->metadata = $metadata;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database']; // MVP: database only. Broadcast can be added later.
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => $this->message,
            'type' => $this->type,
            'metadata' => $this->metadata,
        ];
    }
}
