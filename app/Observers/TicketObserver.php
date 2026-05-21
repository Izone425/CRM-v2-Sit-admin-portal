<?php

namespace App\Observers;

use App\Models\Ticket;
use App\Models\TicketingUser;
use App\Services\TicketNotificationService;
use Illuminate\Support\Facades\Log;

class TicketObserver
{
    private TicketNotificationService $service;

    public function __construct(TicketNotificationService $service)
    {
        $this->service = $service;
    }

    public function created(Ticket $ticket)
    {
        try {
            Log::info('TicketObserver::created fired', ['ticket_id' => $ticket->id, 'ticket_no' => $ticket->ticket_id]);

            $action = $this->getCreatedAction($ticket);
            $actionBy = $this->getAuthTicketingUser();

            $this->service->handleAction($action, $ticket, $actionBy);

            Log::info('TicketObserver::created completed successfully');
        } catch (\Exception $e) {
            Log::error('TicketObserver::created failed', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    public function updated(Ticket $ticket)
    {
        try {
            $actionBy = $this->getAuthTicketingUser();

            // Ticket Accepted
            if ($ticket->wasChanged('status') && strpos($ticket->status, 'In Progress') !== false) {
                $this->service->handleAction('accepted', $ticket, $actionBy);
            }

            // Ticket Closed
            if ($ticket->wasChanged('status') && $ticket->status === 'Closed') {
                $this->service->handleAction('closed', $ticket, $actionBy);
            }

            // Ticket On Hold
            if ($ticket->wasChanged('status') && $ticket->status === 'On Hold') {
                $this->service->handleAction('on_hold', $ticket, $actionBy);
            }

            // Ticket Reopened
            if ($ticket->wasChanged('status') && $ticket->status === 'Reopen') {
                $additionalData = [
                    'reopener' => auth()->user(),
                    'reopened_at' => now(),
                    'reopen_reason' => $ticket->reopen_reason ?? 'Not specified',
                ];
                $this->service->handleAction('reopened', $ticket, $actionBy, $additionalData);
            }

            // Rejected - System Configuration
            if ($ticket->wasChanged('status') && $ticket->status === 'Closed System Configuration') {
                $additionalData = [
                    'rejector' => auth()->user(),
                    'rejected_at' => now(),
                    'rejection_reason' => 'System Configuration',
                ];
                $this->service->handleAction('rejected_config', $ticket, $actionBy, $additionalData);
            }

            // Rejected - Change Request
            if ($ticket->wasChanged('status') && $ticket->status === 'Closed with New CR') {
                $additionalData = [
                    'rejector' => auth()->user(),
                    'rejected_at' => now(),
                    'rejection_reason' => 'Change Request',
                ];
                $this->service->handleAction('rejected_change_request', $ticket, $actionBy, $additionalData);
            }

            // PDT Rejected
            if ($ticket->wasChanged('status') && $ticket->status === 'PDT - Rejected') {
                $additionalData = [
                    'rejector' => auth()->user(),
                    'rejected_at' => now(),
                    'rejection_reason' => $ticket->rejection_reason,
                ];
                $this->service->handleAction('pdt_rejected_request', $ticket, $actionBy, $additionalData);
            }

            // PDT Manday Added
            if ($ticket->wasChanged('estimated_pdt_mandays')) {
                $this->service->handleAction('pdt_manday_added', $ticket, $actionBy);
            }

            // RND Manday Added
            if ($ticket->wasChanged('estimated_rnd_mandays')) {
                $this->service->handleAction('rnd_manday_added', $ticket, $actionBy);
            }

            // All Mandays Completed
            if ($ticket->wasChanged('status') && $ticket->status === 'Mandays Updated') {
                $additionalData = [
                    'pdt_mandays' => $ticket->estimated_pdt_mandays ?? 0,
                    'rnd_mandays' => $ticket->estimated_rnd_mandays ?? 0,
                    'total_mandays' => ($ticket->estimated_pdt_mandays ?? 0) + ($ticket->estimated_rnd_mandays ?? 0),
                ];
                $this->service->handleAction('all_mandays_completed', $ticket, $actionBy, $additionalData);
            }

            // Ticket Completed
            if ($ticket->wasChanged('status') && $ticket->status === 'Completed') {
                $this->service->handleAction('completed', $ticket, $actionBy);
            }
        } catch (\Exception $e) {
            Log::error('TicketObserver::updated failed', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Determine the created action based on ticket priority.
     */
    private function getCreatedAction(Ticket $ticket): string
    {
        $priority = $ticket->priority?->name ?? '';

        return match ($priority) {
            'Software Bugs' => 'created_p1',
            'Back End Assistance' => 'created_p2',
            'RFQ Customization' => 'created_p4a',
            'Critical Enhancement', 'Paid Customization', 'Non-Critical Enhancement' => 'created_p3_p5',
            default => 'created_p3_p5',
        };
    }

    /**
     * Get the current auth user as a TicketingUser (for exclusion from recipients).
     */
    private function getAuthTicketingUser(): ?TicketingUser
    {
        $authUser = auth()->user();
        if (!$authUser) {
            return null;
        }

        return TicketingUser::where('email', $authUser->email)->first();
    }
}
