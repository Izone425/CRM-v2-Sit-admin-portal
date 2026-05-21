<?php

namespace App\Livewire;

use Livewire\Component;

class BrowserNotificationPoller extends Component
{
    public $shownIds = [];

    public function mount()
    {
        // Pre-fill with recent unread notification IDs to avoid showing old ones on page load
        $user = auth()->user();
        if ($user) {
            $this->shownIds = $user->unreadNotifications()
                ->latest()
                ->limit(50)
                ->pluck('id')
                ->toArray();
        }
    }

    public function checkNewNotifications()
    {
        $user = auth()->user();
        if (!$user) {
            return;
        }

        $newNotifications = $user->unreadNotifications()
            ->whereNotIn('id', $this->shownIds)
            ->latest()
            ->limit(10)
            ->get();

        if ($newNotifications->isNotEmpty()) {
            foreach ($newNotifications as $notification) {
                $this->shownIds[] = $notification->id;

                $data = is_array($notification->data) ? $notification->data : json_decode($notification->data, true);

                // Extract URL from action_url or from Filament actions array
                $url = $data['action_url'] ?? null;
                if (!$url && !empty($data['actions'])) {
                    $url = $data['actions'][0]['url'] ?? null;
                }

                $this->dispatch('show-browser-notification', [
                    'title' => $data['title'] ?? 'New Notification',
                    'body' => $data['body'] ?? $data['message'] ?? '',
                    'url' => $url,
                    'tag' => $notification->id,
                ]);
            }

            // Keep shownIds from growing too large
            if (count($this->shownIds) > 200) {
                $this->shownIds = array_slice($this->shownIds, -100);
            }
        }
    }

    public function render()
    {
        return view('livewire.browser-notification-poller');
    }
}
