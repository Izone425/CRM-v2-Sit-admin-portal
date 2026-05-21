<?php

namespace App\Mail;

use App\Models\ImplementerHandoverRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProjectClosingNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $handoverRequest;
    public $status;

    /**
     * Create a new message instance.
     */
    public function __construct(ImplementerHandoverRequest $handoverRequest, string $status)
    {
        $this->handoverRequest = $handoverRequest;
        $this->status = $status;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $swId = $this->handoverRequest->softwareHandover?->formatted_handover_id ?? 'N/A';
        $statusText = strtoupper($this->status);

        return new Envelope(
            subject: "{$swId} | PROJECT CLOSING | {$statusText}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.project-closing-notification',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
