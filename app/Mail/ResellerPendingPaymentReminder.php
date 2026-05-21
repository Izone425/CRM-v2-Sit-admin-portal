<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Support\Collection;

class ResellerPendingPaymentReminder extends Mailable
{
    use Queueable, SerializesModels;

    public Collection $handovers;
    public string $resellerCompanyName;

    public function __construct(Collection $handovers, string $resellerCompanyName)
    {
        $this->handovers = $handovers;
        $this->resellerCompanyName = $resellerCompanyName;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('no-reply@timeteccloud.com', 'TimeTec HR CRM'),
            subject: "TIMETEC | PENDING RESELLER PAYMENT | {$this->resellerCompanyName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reseller-pending-payment-reminder',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
