<?php

namespace App\Livewire\Ui;

use App\Models\User;
use App\Support\Notifications\InAppNotificationFeed;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\DatabaseNotification;
use Livewire\Attributes\On;
use Livewire\Component;

class NotificationBell extends Component
{
    public bool $open = false;

    public int $unreadCount = 0;

    /** @var Collection<int, DatabaseNotification> */
    public $recent;

    /** Último conteo de no leídas (para sonido al subir). */
    public int $lastUnreadSnapshot = 0;

    public function mount(): void
    {
        $viewer = auth()->user();
        $this->unreadCount = InAppNotificationFeed::unreadCountFor($viewer);
        $this->lastUnreadSnapshot = $this->unreadCount;
        $this->recent = InAppNotificationFeed::queryFor($viewer)
            ->limit(20)
            ->get();
    }

    #[On('notifications-updated')]
    public function onBroadcastNotification(): void
    {
        $this->syncInbox();
    }

    /**
     * Polling de respaldo (p. ej. sin Pusher) y refresco tras broadcast.
     */
    public function syncInbox(): void
    {
        $viewer = auth()->user();
        $count = InAppNotificationFeed::unreadCountFor($viewer);

        if ($count > $this->lastUnreadSnapshot) {
            $this->js('window.sjPlayNotificationBellSound && window.sjPlayNotificationBellSound()');
        }
        $this->lastUnreadSnapshot = $count;
        $this->unreadCount = $count;

        if ($this->open) {
            $this->recent = InAppNotificationFeed::queryFor($viewer)
                ->limit(20)
                ->get();
        }
    }

    public function toggle(): void
    {
        $this->open = ! $this->open;

        if ($this->open) {
            $this->loadRecentList();
        }
    }

    public function close(): void
    {
        $this->open = false;
    }

    public function loadRecentList(): void
    {
        $viewer = auth()->user();
        $this->recent = InAppNotificationFeed::queryFor($viewer)
            ->limit(20)
            ->get();
    }

    public function markOneRead(string $id): void
    {
        $notification = DatabaseNotification::query()->whereKey($id)->firstOrFail();
        $this->ensureCanInteract($notification);
        $notification->markAsRead();
        $this->syncInbox();
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
        $this->syncInbox();
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
        return view('livewire.ui.notification-bell', [
            'viewerIsAdmin' => InAppNotificationFeed::adminSeesEveryonesNotifications(auth()->user()),
        ]);
    }
}
