<?php

namespace App\Livewire\Ui;

use App\Models\User;
use App\Support\Notifications\InAppNotificationFeed;
use Illuminate\Contracts\View\View;
use Illuminate\Notifications\DatabaseNotification;
use Livewire\Component;

class NotificationBell extends Component
{
    public bool $open = false;

    public function toggle(): void
    {
        $this->open = ! $this->open;
    }

    public function close(): void
    {
        $this->open = false;
    }

    public function markOneRead(string $id): void
    {
        $notification = DatabaseNotification::query()->whereKey($id)->firstOrFail();
        $this->ensureCanInteract($notification);
        $notification->markAsRead();
    }

    public function openAndMark(string $id): void
    {
        $notification = DatabaseNotification::query()->whereKey($id)->firstOrFail();
        $this->ensureCanInteract($notification);
        $notification->markAsRead();

        $data = is_array($notification->data) ? $notification->data : [];
        $url = data_get($data, 'action_url');

        if (filled($url)) {
            $this->redirect((string) $url, navigate: true);

            return;
        }

        $this->open = false;
    }

    public function markOwnUnreadRead(): void
    {
        $viewer = auth()->user();

        if (InAppNotificationFeed::adminSeesEveryonesNotifications($viewer)) {
            return;
        }

        $viewer->unreadNotifications()->update(['read_at' => now()]);
    }

    private function ensureCanInteract(DatabaseNotification $notification): void
    {
        if ($notification->notifiable_type !== User::class) {
            abort(403);
        }

        $viewer = auth()->user();

        if (InAppNotificationFeed::adminSeesEveryonesNotifications($viewer)) {
            return;
        }

        if ((int) $notification->notifiable_id !== (int) $viewer->getKey()) {
            abort(403);
        }
    }

    public function render(): View
    {
        $viewer = auth()->user();
        $unreadCount = InAppNotificationFeed::unreadCountFor($viewer);
        $recent = InAppNotificationFeed::queryFor($viewer)
            ->limit(20)
            ->get();

        return view('livewire.ui.notification-bell', [
            'unreadCount' => $unreadCount,
            'recent' => $recent,
            'viewerIsAdmin' => InAppNotificationFeed::adminSeesEveryonesNotifications($viewer),
        ]);
    }
}
