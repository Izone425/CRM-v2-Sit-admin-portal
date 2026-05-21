<?php

namespace App\Mail;

use App\Models\Ticket;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EntityNotificationMail extends Mailable
{
    use SerializesModels;

    public $ticket;
    public $entityType;
    public $action;
    public $actionBy;
    public $additionalData;
    public $recipient;
    public $config;

    public function __construct($entity, string $entityType, string $action, $actionBy = null, array $additionalData = [], $recipient = null)
    {
        $this->ticket = $entity;
        $this->entityType = $entityType;
        $this->action = $action;
        $this->actionBy = $actionBy;
        $this->additionalData = $additionalData;
        $this->recipient = $recipient;
        $this->config = config("notification-scenarios.{$entityType}.{$action}.email", []);
    }

    public function envelope(): Envelope
    {
        $subject = $this->parseTemplate($this->config['subject'] ?? 'Ticket Notification');

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.entity-notification',
            with: [
                'ticket' => $this->ticket,
                'action' => $this->action,
                'config' => $this->config,
                'actionBy' => $this->actionBy,
                'additionalData' => $this->additionalData,
                'recipient' => $this->recipient,
                'reopener' => $this->additionalData['reopener'] ?? null,
                'reopenedAt' => $this->additionalData['reopened_at'] ?? null,
                'reopenReason' => $this->additionalData['reopen_reason'] ?? null,
                'rejector' => $this->additionalData['rejector'] ?? null,
                'rejectedAt' => $this->additionalData['rejected_at'] ?? null,
                'rejectionReason' => $this->additionalData['rejection_reason'] ?? null,
                'pdtMandays' => $this->additionalData['pdt_mandays'] ?? null,
                'rndMandays' => $this->additionalData['rnd_mandays'] ?? null,
                'qcMandays' => $this->additionalData['qc_mandays'] ?? null,
                'totalMandays' => $this->additionalData['total_mandays'] ?? null,
            ],
        );
    }

    private function parseTemplate(string $template): string
    {
        if ($this->ticket instanceof Ticket) {
            $replacements = [
                '{ticket_id}' => $this->ticket->ticket_id,
                '{title}' => $this->ticket->title,
                '{status}' => $this->ticket->status,
                '{priority}' => $this->ticket->priority?->name ?? 'N/A',
                '{user_name}' => $this->actionBy?->name ?? 'Someone',
            ];
        } else {
            $replacements = [
                '{user_name}' => $this->actionBy?->name ?? 'Someone',
            ];
        }

        foreach ($this->additionalData as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $replacements['{' . $key . '}'] = $value;
            }
        }

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $template
        );
    }
}
