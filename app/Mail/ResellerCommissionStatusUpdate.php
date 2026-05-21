<?php

namespace App\Mail;

use App\Models\ResellerCommissionHandover;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class ResellerCommissionStatusUpdate extends Mailable
{
    use Queueable, SerializesModels;

    public $handover;
    public $status;
    public $statusLabel;
    public $ticketId;
    public $apInvoiceUrl;
    public $ttInvoiceUrl;
    public $paymentSlipUrl;
    public $selfBilledEinvoiceUrl;
    public $proceedUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(ResellerCommissionHandover $handover)
    {
        $this->handover = $handover;
        $this->status = $handover->status;
        $this->statusLabel = $this->getStatusLabel($handover->status);
        $this->ticketId = $handover->fh_id;
        $this->apInvoiceUrl = $handover->ap_invoice_url;
        $this->ttInvoiceUrl = $handover->tt_invoice_url;

        if ($this->status === 'completed' && $handover->payment_slip) {
            $this->paymentSlipUrl = asset('storage/' . $handover->payment_slip);
        }

        if ($this->status === 'completed' && $handover->self_billed_einvoice) {
            $this->selfBilledEinvoiceUrl = asset('storage/' . $handover->self_billed_einvoice);
        }

        // Email-action Proceed link — only valid while waiting on the reseller.
        if ($this->status === 'pending_reseller') {
            $this->proceedUrl = URL::signedRoute(
                'reseller.commission-handover.proceed',
                ['handover' => $handover->id],
            );
        }
    }

    /**
     * Check if email should be sent for this status
     */
    public static function shouldSend(string $status): bool
    {
        $sendStatuses = [
            'pending_reseller',
            'pending_finance',
            'completed',
        ];

        return in_array($status, $sendStatuses);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $recipients = $this->getRecipients();
        $ccAddresses = $this->getCcAddresses();
        $bccAddresses = $this->getBccAddresses();

        $this->logEmailDetails($recipients, $bccAddresses, $ccAddresses);

        return new Envelope(
            from: new Address(config('mail.from.address', 'noreply@timeteccloud.com'), 'TimeTec HR CRM'),
            to: $recipients,
            cc: $ccAddresses,
            bcc: $bccAddresses,
            subject: strtoupper("{$this->ticketId} | {$this->handover->reseller_name}"),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.reseller-commission-status-update',
        );
    }

    /**
     * Get recipients based on status
     */
    private function getRecipients(): array
    {
        $reseller = \App\Models\ResellerV2::where('reseller_id', $this->handover->reseller_id)->first();

        Log::info('Commission Reseller Email Lookup Debug', [
            'handover_reseller_id' => $this->handover->reseller_id,
            'reseller_found' => $reseller ? 'Yes' : 'No',
            'reseller_email' => $reseller ? $reseller->email : 'N/A',
        ]);

        if ($reseller && $reseller->email) {
            return [$reseller->email];
        }

        return ['faiz@timeteccloud.com'];
    }

    /**
     * Get CC addresses based on status
     */
    private function getCcAddresses(): array
    {
        return [];
    }

    /**
     * Get BCC addresses based on status
     */
    private function getBccAddresses(): array
    {
        return ['faiz@timeteccloud.com', 'renewal@timeteccloud.com'];
    }

    /**
     * Get formatted status label
     */
    private function getStatusLabel(string $status): string
    {
        $labels = [
            'pending_reseller' => 'Pending Reseller',
            'pending_finance' => 'Pending Finance',
            'completed' => 'Completed',
        ];

        return $labels[$status] ?? ucwords(str_replace('_', ' ', $status));
    }

    /**
     * Log email sending details
     */
    private function logEmailDetails(array $recipients, array $bccAddresses, array $ccAddresses = []): void
    {
        Log::info('Commission Reseller Handover Status Email Sent', [
            'handover_id' => $this->handover->id,
            'fh_id' => $this->ticketId,
            'status' => $this->status,
            'status_label' => $this->statusLabel,
            'reseller_name' => $this->handover->reseller_name,
            'subscriber_name' => $this->handover->subscriber_name,
            'email_to' => $recipients,
            'email_cc' => $ccAddresses,
            'email_bcc' => $bccAddresses,
            'subject' => "{$this->ticketId} | {$this->handover->reseller_name}",
            'timestamp' => now()->toDateTimeString(),
        ]);
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
