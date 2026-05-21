<?php

namespace App\Console\Commands;

use App\Models\TicketingNotification;
use App\Models\TicketingUser;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncTicketingNotifications extends Command
{
    protected $signature = 'notifications:sync-ticketing';
    protected $description = 'Poll ticketingsystem_live.notifications and sync to CRM Filament bell';

    /**
     * Sources created by this CRM — skip these since the NotificationObserver already handles them.
     */
    private const CRM_TYPE = 'App\\Notifications\\TicketNotification';

    public function handle()
    {
        $lastSyncedAt = Cache::get('ticketing_notifications_last_synced_at');

        $query = TicketingNotification::query()
            ->orderBy('created_at');

        if ($lastSyncedAt) {
            $query->where('created_at', '>', $lastSyncedAt);
        }

        $notifications = $query->limit(500)->get();

        if ($notifications->isEmpty()) {
            return;
        }

        $synced = 0;
        $skipped = 0;

        foreach ($notifications as $notification) {
            if ($this->syncToCrm($notification)) {
                $synced++;
            } else {
                $skipped++;
            }
        }

        // Track by latest created_at to avoid re-processing
        $latestAt = $notifications->max('created_at');
        Cache::forever('ticketing_notifications_last_synced_at', $latestAt);

        $this->info("Synced {$synced}, skipped {$skipped}. Last: {$latestAt}");
    }

    private function syncToCrm(TicketingNotification $notification): bool
    {
        try {
            $ticketingUser = TicketingUser::find($notification->notifiable_id);
            if (!$ticketingUser) {
                return false;
            }

            $crmUser = $ticketingUser->getCrmUser();
            if (!$crmUser) {
                return false;
            }

            // Ticketing DB stores UTC (freshTimestamp subtracts 8h), CRM stores local time (UTC+8)
            $localCreatedAt = Carbon::parse($notification->created_at)->addHours(8);
            $localUpdatedAt = Carbon::parse($notification->updated_at ?? $notification->created_at)->addHours(8);

            // Check if already synced (avoid duplicates)
            $exists = DB::table('notifications')
                ->where('type', 'Filament\\Notifications\\DatabaseNotification')
                ->where('notifiable_id', $crmUser->id)
                ->where('created_at', $localCreatedAt)
                ->exists();

            if ($exists) {
                return false;
            }

            $data = is_array($notification->data) ? $notification->data : json_decode($notification->data, true);

            $title = $data['title'] ?? 'Ticket Update';

            // Skip Daily Standup Reminder notifications
            if ($title === 'Daily Standup Reminder') {
                return false;
            }
            $body = $data['message'] ?? '';
            $ticketId = $data['ticket_id'] ?? null;

            // Color based on priority
            $priority = $data['priority'] ?? 'normal';
            $iconColor = match ($priority) {
                'urgent' => 'danger',
                'high' => 'warning',
                default => 'info',
            };

            // Build Filament-compatible notification data
            $filamentData = [
                'actions' => [],
                'body' => $body,
                'color' => null,
                'duration' => 'persistent',
                'icon' => 'heroicon-o-ticket',
                'iconColor' => $iconColor,
                'status' => $iconColor,
                'title' => $title,
                'view' => 'filament-notifications::notification',
                'viewData' => [],
                'format' => 'filament',
            ];

            // Add action URL if ticket_id available
            if ($ticketId) {
                $filamentData['actions'][] = [
                    'name' => 'view',
                    'color' => null,
                    'event' => null,
                    'eventData' => [],
                    'dispatchDirection' => false,
                    'dispatchToComponent' => null,
                    'extraAttributes' => [],
                    'icon' => null,
                    'iconPosition' => 'before',
                    'iconSize' => null,
                    'isOutlined' => false,
                    'isDisabled' => false,
                    'label' => 'View Ticket',
                    'shouldClose' => false,
                    'shouldMarkAsRead' => true,
                    'shouldMarkAsUnread' => false,
                    'shouldOpenUrlInNewTab' => false,
                    'size' => 'sm',
                    'tooltip' => null,
                    'url' => "/admin/ticket-list?ticket={$ticketId}",
                    'view' => 'filament-actions::link-action',
                ];
            }

            // Insert directly with correct timestamps
            DB::table('notifications')->insert([
                'id' => Str::uuid()->toString(),
                'type' => 'Filament\\Notifications\\DatabaseNotification',
                'notifiable_type' => get_class($crmUser),
                'notifiable_id' => $crmUser->id,
                'data' => json_encode($filamentData),
                'read_at' => null,
                'created_at' => $localCreatedAt,
                'updated_at' => $localUpdatedAt,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('SyncTicketingNotifications: failed to sync', [
                'notification_id' => $notification->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
