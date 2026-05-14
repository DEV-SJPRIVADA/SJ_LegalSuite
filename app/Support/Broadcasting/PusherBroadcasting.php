<?php

namespace App\Support\Broadcasting;

final class PusherBroadcasting
{
    public static function isEnabled(): bool
    {
        if (config('broadcasting.default') !== 'pusher') {
            return false;
        }

        $connection = config('broadcasting.connections.pusher');

        return filled($connection['key'] ?? null)
            && filled($connection['secret'] ?? null)
            && filled($connection['app_id'] ?? null)
            && filled($connection['options']['cluster'] ?? null);
    }
}
