<?php

namespace App\Mail;

use App\Models\ResellerInquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Support\Facades\Log;

class ResellerInquiryStatusUpdate extends Mailable
{
    use Queueable, SerializesModels;

    public $inquiry;
    public $status;
    public $statusLabel;

    public function __construct(ResellerInquiry $inquiry)
    {
        $this->inquiry = $inquiry;
        $this->status = $inquiry->status;
        $this->statusLabel = $this->getStatusLabel($inquiry->status);
    }

    public function envelope(): Envelope
    {
        $recipients = $this->getRecipients();

        Log::info('Reseller Inquiry Status Email Sent', [
            'inquiry_id' => $this->inquiry->id,
            'formatted_id' => $this->inquiry->formatted_id,
            'status' => $this->status,
            'email_to' => $recipients,
            'email_cc' => ['faiz@timeteccloud.com'],
            'timestamp' => now()->toDateTimeString(),
        ]);

        return new Envelope(
            from: new Address(config('mail.from.address', 'noreply@timeteccloud.com'), 'TimeTec HR CRM'),
            to: $recipients,
            bcc: ['faiz@timeteccloud.com'],
            subject: "{$this->inquiry->formatted_id} | " . strtoupper($this->inquiry->reseller_company_name),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reseller-inquiry-status-update',
        );
    }

    private function getRecipients(): array
    {
        $reseller = \App\Models\ResellerV2::where('reseller_id', $this->inquiry->reseller_id)->first();

        if ($reseller && $reseller->email) {
            return [$reseller->email];
        }

        return ['faiz@timeteccloud.com'];
    }

    private function getStatusLabel(string $status): string
    {
        $labels = [
            'completed' => 'Completed',
            'rejected' => 'Rejected',
        ];

        return $labels[$status] ?? ucwords(str_replace('_', ' ', $status));
    }

    public function attachments(): array
    {
        return [];
    }
}
