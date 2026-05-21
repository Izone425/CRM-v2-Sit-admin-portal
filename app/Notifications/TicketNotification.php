<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\Ticket;
use App\Models\User;

class TicketNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $ticket;
    public $action;
    public $actionBy;
    public $additionalData;

    public function __construct(Ticket $ticket, string $action, ?User $actionBy = null, array $additionalData = [])
    {
        $this->ticket = $ticket;
        $this->action = $action;
        $this->actionBy = $actionBy;
        $this->additionalData = $additionalData;
    }

    public function via($notifiable)
    {
        $config = config("notification-scenarios.ticket.{$this->action}");
        $channels = $config['channels'] ?? [];

        $result = [];
        if (in_array('in_app', $channels)) {
            $result[] = 'database';
        }

        return $result;
    }

    public function toDatabase($notifiable)
    {
        $config = config("notification-scenarios.ticket.{$this->action}");
        return [
            'type' => 'ticket.' . $this->action,
            'title' => $this->parseTemplate($config['title'] ?? 'Ticket Update'),
            'message' => $this->parseTemplate($config['message'] ?? 'Ticket {ticket_id} updated'),
            'action_url' => $this->parseTemplate($config['action_url'] ?? '/tickets/{ticket_id}'),
            'priority' => $config['priority'] ?? 'normal',
            'entity_type' => 'ticket',
            'entity_id' => $this->ticket->id,
            'ticket_id' => $this->ticket->ticket_id,
            'action_by' => $this->actionBy ? [
                'id' => $this->actionBy->id,
                'name' => $this->actionBy->name,
                'avatar' => $this->actionBy->avatar ?? null,
            ] : null,
            'metadata' => $this->additionalData,
        ];
    }

    private function parseTemplate(string $template): string
    {
        $replacements = [
            '{ticket_id}' => $this->ticket->ticket_id,
            '{title}' => $this->ticket->title,
            '{status}' => $this->ticket->status,
            '{priority}' => $this->ticket->priority?->name ?? 'N/A',
            '{user_name}' => $this->actionBy?->name ?? 'Someone',
        ];

        foreach ($this->additionalData as $key => $value) {
            $replacements['{' . $key . '}'] = is_string($value) ? $value : json_encode($value);
        }

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $template
        );
    }
}
