<?php

namespace App\Filament\Pages\PdtTicketing;

use App\Filament\Pages\QcTicketing\QcTicketingMyWorkspace;
use App\Models\Ticket;
use App\Models\TicketingUser;
use App\Models\TicketLog;
use App\Models\TicketPriority;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class PdtTicketingMyWorkspace extends QcTicketingMyWorkspace
{
    protected static ?string $navigationLabel = 'My Workspace';
    protected static ?string $slug = 'pdt-ticketing/my-workspace';

    public function showBugsTab(): bool
    {
        return false;
    }

    public function getTaskStatusesProperty(): array
    {
        return [
            ['id' => 'new', 'title' => 'New', 'status' => 'New', 'color' => '#6b7280'],
            ['id' => 'inProgress', 'title' => 'In Progress', 'status' => 'PDT - In Progress', 'color' => '#ea580c'],
            ['id' => 'onHold', 'title' => 'On Hold', 'status' => 'PDT - On Hold', 'color' => '#f59e0b'],
            ['id' => 'cancelled', 'title' => 'Cancelled', 'status' => 'PDT - Cancel', 'color' => '#dc2626'],
            ['id' => 'reopen', 'title' => 'Reopen', 'status' => 'Reopen', 'color' => '#ef4444'],
            ['id' => 'readyForDevelopment', 'title' => 'Ready for Development', 'status' => 'Ready For Development', 'color' => '#3b82f6'],
        ];
    }

    public function getAnalyticsProperty(): array
    {
        $base = parent::getAnalyticsProperty();

        $eligibleIds = $this->newTicketPriorityIds();

        $newTicketsCount = Ticket::query()
            ->whereIn('product_id', [1, 2])
            ->whereIn('status', ['New', 'Reopen'])
            ->whereIn('priority_id', $eligibleIds)
            ->count();

        $mandaysRequestsCount = Ticket::query()
            ->whereIn('product_id', [1, 2])
            ->where('status', 'Pending Mandays')
            ->count();

        return array_merge($base, [
            'newTicketsCount' => $newTicketsCount,
            'mandaysRequestsCount' => $mandaysRequestsCount,
        ]);
    }

    public function getDueDrawerItemsProperty()
    {
        if ($this->dueDrawerType === 'new_tickets') {
            return Ticket::query()
                ->with(['product:id,name'])
                ->whereIn('product_id', [1, 2])
                ->whereIn('status', ['New', 'Reopen'])
                ->whereIn('priority_id', $this->newTicketPriorityIds())
                ->orderBy('created_at', 'desc')
                ->get();
        }

        if ($this->dueDrawerType === 'mandays_requests') {
            return Ticket::query()
                ->with(['product:id,name'])
                ->whereIn('product_id', [1, 2])
                ->where('status', 'Pending Mandays')
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return parent::getDueDrawerItemsProperty();
    }

    public function getDrawerDatasetsProperty(): array
    {
        $base = parent::getDrawerDatasetsProperty();
        $eligible = $this->newTicketPriorityIds();

        $mapTicket = function ($t, bool $withActions, bool $mandaysBanner = false) {
            $priority = $t->priority;
            $priorityCode = null;
            $priorityLabel = null;
            if ($priority) {
                $suffix = $priority->sort_order_suffix ?: '';
                $priorityCode = 'P' . $priority->sort_order . $suffix;
                $priorityLabel = $priorityCode . ' - ' . strtoupper($priority->name);
            }

            return [
                'id' => $t->id,
                'code' => $t->ticket_id,
                'title' => (string) $t->title,
                'status' => (string) $t->status,
                'product' => $t->product?->name,
                'module' => $t->module?->name,
                'priority_label' => $priorityLabel,
                'priority_color' => $priority?->color,
                'due_date' => null,
                'is_overdue' => false,
                'overdue_days' => 0,
                'created_date' => $t->created_at?->format('d/m/Y'),
                'dispatch' => 'openTicketModal',
                'show_pdt_actions' => $withActions,
                'show_mandays_banner' => $mandaysBanner,
            ];
        };

        $newTickets = Ticket::query()
            ->with(['product:id,name', 'module:id,name', 'priority:id,name,color,sort_order,sort_order_suffix'])
            ->whereIn('product_id', [1, 2])
            ->whereIn('status', ['New', 'Reopen'])
            ->whereIn('priority_id', $eligible)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($t) => $mapTicket($t, true, false))->values()->all();

        $mandaysRequests = Ticket::query()
            ->with(['product:id,name', 'module:id,name', 'priority:id,name,color,sort_order,sort_order_suffix'])
            ->whereIn('product_id', [1, 2])
            ->where('status', 'Pending Mandays')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($t) => $mapTicket($t, false, true))->values()->all();

        return array_merge($base, [
            'new_tickets' => ['title' => 'New Tickets', 'empty' => 'No tickets', 'items' => $newTickets],
            'mandays_requests' => ['title' => 'Mandays Requests', 'empty' => 'No tickets', 'items' => $mandaysRequests],
        ]);
    }

    public function acceptTicket(int $ticketId): void
    {
        // Mirrors dt-dev ViewTicketDrawer::handleAccept — open CreateTaskDrawer
        // pre-filled with the ticket; task creation will transition ticket status.
        $this->dispatch('openCreateTaskModal', releaseId: null, ticketId: $ticketId);
    }

    public function submitPdtMandays(int $ticketId, $mandays): void
    {
        $mandays = is_numeric($mandays) ? (float) $mandays : 0;
        if ($mandays <= 0) {
            Notification::make()->title('Enter a valid mandays value')->warning()->send();
            return;
        }

        try {
            $ticket = Ticket::findOrFail($ticketId);
            $ticket->update(['estimated_pdt_mandays' => $mandays]);

            Notification::make()
                ->title("PDT mandays saved for {$ticket->ticket_id}")
                ->body("{$mandays} days")
                ->success()
                ->send();

            $this->dispatch('ticket-status-updated', ticketId: (int) $ticket->id);
        } catch (\Throwable $e) {
            Log::error('PDT mandays submit failed: ' . $e->getMessage());
            Notification::make()->title('Error saving mandays')->body($e->getMessage())->danger()->send();
        }
    }

    public function rejectTicket(int $ticketId, ?string $reason = null): void
    {
        $reason = trim((string) $reason);
        if ($reason === '') {
            Notification::make()->title('Rejection reason is required')->warning()->send();
            return;
        }
        $this->changeTicketStatus($ticketId, 'PDT - Rejected', $reason, ['rejection_reason' => $reason]);
    }

    public function holdTicket(int $ticketId, ?string $reason = null): void
    {
        $reason = trim((string) $reason);
        if ($reason === '') {
            Notification::make()->title('On Hold reason is required')->warning()->send();
            return;
        }
        $this->changeTicketStatus($ticketId, 'On Hold', $reason, ['kiv_reason' => $reason]);
    }

    private function changeTicketStatus(int $ticketId, string $newStatus, ?string $changeReason, array $extra): void
    {
        try {
            $ticket = Ticket::findOrFail($ticketId);
            $oldStatus = $ticket->status;

            $ticket->update(array_merge($extra, ['status' => $newStatus]));

            $email = auth()->user()?->email;
            $tsUser = $email ? TicketingUser::where('email', $email)->first() : null;

            TicketLog::create([
                'ticket_id' => $ticket->id,
                'old_value' => $oldStatus,
                'new_value' => $newStatus,
                'action' => "Changed status from '{$oldStatus}' to '{$newStatus}' for ticket {$ticket->ticket_id}.",
                'field_name' => 'status',
                'change_reason' => $changeReason,
                'updated_by' => $tsUser?->id,
                'user_name' => $tsUser?->name ?? 'HRcrm User',
                'user_role' => $tsUser?->role ?? 'PDT',
                'change_type' => 'status_change',
                'source' => 'pdt_workspace',
                'created_at' => now()->subHours(8),
                'updated_at' => now()->subHours(8),
            ]);

            // Notification skipped here on purpose — the drawer already removed the
            // ticket optimistically, so the user sees instant feedback. A server-side
            // notification would arrive only after the Livewire round-trip completes.
            $this->dispatch('ticket-status-updated', ticketId: (int) $ticket->id);
        } catch (\Throwable $e) {
            Log::error('PDT ticket status change failed: ' . $e->getMessage());
            Notification::make()->title('Error updating ticket')->body($e->getMessage())->danger()->send();
        }
    }

    // P3, P4a, P4b, P5 — exclude P1 (Software Bugs) and P2 (Back End Assistance) from PDT's new-ticket queue.
    private function newTicketPriorityIds(): array
    {
        return TicketPriority::where('is_active', true)
            ->whereIn('sort_order', [3, 4, 5])
            ->where(function ($q) {
                $q->whereNull('sort_order_suffix')
                    ->orWhereIn('sort_order_suffix', ['a', 'b']);
            })
            ->pluck('id')
            ->all();
    }
}
