<?php

namespace App\Mail;

use App\Models\TrainingSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TrainingCompletionNotification extends Mailable
{
    use Queueable, SerializesModels;

    public TrainingSession $session;
    public string $trainingType;
    public string $attendeeName;
    public array $recordingLinks;
    public array $surveyLinks;

    public function __construct(
        TrainingSession $session,
        string $trainingType,
        string $attendeeName,
        array $recordingLinks = [],
        array $surveyLinks = []
    ) {
        $this->session = $session;
        $this->trainingType = $trainingType;
        $this->attendeeName = $attendeeName;
        $this->recordingLinks = $recordingLinks;
        $this->surveyLinks = $surveyLinks;
    }

    public function envelope(): Envelope
    {
        $typeLabel = $this->trainingType === 'HRDF' ? 'HRDF' : 'Webinar';

        return new Envelope(
            subject: "TimeTec Online {$typeLabel} Training Completion - {$this->session->session_number}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.training-completion-notification',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
