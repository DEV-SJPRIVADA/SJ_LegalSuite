<?php

namespace App\Support\Notifications;

use App\Support\Broadcasting\PusherBroadcasting;
use Illuminate\Notifications\Messages\BroadcastMessage;

/**
 * Añade canal broadcast (Pusher) con el mismo payload que database, para la campanita en tiempo casi real.
 * Sólo activa broadcast si la conexión por defecto es `pusher` y las credenciales están completas.
 */
trait BroadcastsInAppDatabaseNotification
{
    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];
        if (PusherBroadcasting::isEnabled()) {
            $channels[] = 'broadcast';
        }

        return $channels;
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }
}
