<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use App\Models\TicketLog;
use App\Models\TicketingUser;
use App\Services\TicketNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CheckTicketUpdates extends Command
{
    protected $signature = 'tickets:check-updates';
    protected $description = 'Poll ticket_logs for external changes and send notifications';

    /**
     * CRM-originated sources — skip these to avoid duplicate notifications
     * (the TicketObserver already handles them).
     */
    private const CRM_SOURCES = [
        'crm', 'crm_modal', 'crm_reopen',                        // TicketList
        'dashboard_pass_action',                                    // TicketDashboard
        'ticket_modal', 'ticket_modal_pass_action', 'ticket_modal_reopen', // TicketModal
        'ticket_reminder', 'ticket_reminder_modal',                 // TicketReminder
        'auto',                                                     // Auto-transitions triggered by CRM actions
    ];

    public function handle(TicketNotificationService $service)
    {
        $lastId = Cache::get('ticket_updates_last_log_id', 0);

        $logs = TicketLog::where('id', '>', $lastId)
            ->where(function ($query) {
                $query->whereNotIn('source', self::CRM_SOURCES)
                    ->orWhereNull('source');
            })
            ->orderBy('id')
            ->get();

        if ($logs->isEmpty()) {
            return;
        }

        $this->info("Processing {$logs->count()} external ticket log(s)...");

        foreach ($logs as $log) {
            $this->processLog($log, $service);
        }

        // Update last processed ID to the highest log ID we've seen
        $maxId = $logs->max('id');
        Cache::put('ticket_updates_last_log_id', $maxId);

        $this->info("Done. Last processed log ID: {$maxId}");
    }

    private function processLog(TicketLog $log, TicketNotificationService $service)
    {
        // Status changes
        if ($log->field_name === 'status') {
            $action = $service->determineActionFromStatus($log->new_value);

            if (!$action) {
                return;
            }

            $ticket = Ticket::find($log->ticket_id);
            if (!$ticket) {
                return;
            }

            $actionBy = $log->updated_by ? TicketingUser::find($log->updated_by) : null;
            $additionalData = $this->buildAdditionalData($action, $log, $ticket, $actionBy);

            Log::info('CheckTicketUpdates: processing external status change', [
                'log_id' => $log->id,
                'ticket_id' => $ticket->ticket_id,
                'action' => $action,
                'old_status' => $log->old_value,
                'new_status' => $log->new_value,
                'source' => $log->source,
            ]);

            $service->handleAction($action, $ticket, $actionBy, $additionalData);
        }

        // PDT Manday changes
        if ($log->field_name === 'estimated_pdt_mandays') {
            $ticket = Ticket::find($log->ticket_id);
            if (!$ticket) return;

            $actionBy = $log->updated_by ? TicketingUser::find($log->updated_by) : null;
            $service->handleAction('pdt_manday_added', $ticket, $actionBy);
        }

        // RND Manday changes
        if ($log->field_name === 'estimated_rnd_mandays') {
            $ticket = Ticket::find($log->ticket_id);
            if (!$ticket) return;

            $actionBy = $log->updated_by ? TicketingUser::find($log->updated_by) : null;
            $service->handleAction('rnd_manday_added', $ticket, $actionBy);
        }
    }

    /**
     * Build additional data based on the action type.
     */
    private function buildAdditionalData(string $action, TicketLog $log, Ticket $ticket, ?TicketingUser $actionBy): array
    {
        $crmUser = $actionBy?->getCrmUser();

        return match ($action) {
            'reopened' => [
                'reopener' => $crmUser,
                'reopened_at' => $log->created_at,
                'reopen_reason' => $log->change_reason ?? $ticket->reopen_reason ?? 'Not specified',
            ],
            'rejected_config' => [
                'rejector' => $crmUser,
                'rejected_at' => $log->created_at,
                'rejection_reason' => 'System Configuration',
            ],
            'rejected_change_request' => [
                'rejector' => $crmUser,
                'rejected_at' => $log->created_at,
                'rejection_reason' => 'Change Request',
            ],
            'pdt_rejected_request' => [
                'rejector' => $crmUser,
                'rejected_at' => $log->created_at,
                'rejection_reason' => $ticket->rejection_reason ?? $log->change_reason ?? 'Not specified',
            ],
            'all_mandays_completed' => [
                'pdt_mandays' => $ticket->estimated_pdt_mandays ?? 0,
                'rnd_mandays' => $ticket->estimated_rnd_mandays ?? 0,
                'total_mandays' => ($ticket->estimated_pdt_mandays ?? 0) + ($ticket->estimated_rnd_mandays ?? 0),
            ],
            default => [],
        };
    }
}
