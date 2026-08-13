<?php

namespace App\Support\Notifications;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\DatabaseNotification;

final class InAppNotificationFeed
{
    /** Usuarios con rol «admin» consultan todas las filas dirigidas a usuarios de la aplicación. */
    public static function adminSeesEveryonesNotifications(User $viewer): bool
    {
        return $viewer->hasRole('nivel1');
    }

    /**
     * @return Builder<int, DatabaseNotification>
     */
    public static function queryFor(User $viewer): Builder
    {
        /** @var Builder<int, DatabaseNotification> $q */
        $q = DatabaseNotification::query()->where('notifiable_type', User::class);

        if (! self::adminSeesEveryonesNotifications($viewer)) {
            $q->where('notifiable_id', $viewer->id);
        }

        return $q->latest();
    }

    public static function unreadCountFor(User $viewer): int
    {
        return (int) self::queryFor($viewer)->whereNull('read_at')->count();
    }
}
