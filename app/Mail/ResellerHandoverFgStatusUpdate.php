<?php

namespace App\Mail;

use App\Models\ResellerHandoverFg;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Support\Facades\URL;


class ResellerHandoverFgStatusUpdate extends Mailable
{
    use Queueable, SerializesModels;

    public $handover;
    public $status;
    public $statusLabel;
    public $ticketId;
    public $category;
    public $invoiceUrl;
    public $proceedUrl;
    public $cancelUrl;
    public $autocountInvoiceUrl;
    public $autocountInvoiceNumber;
    public $selfBilledInvoiceUrl;
    public $selfBilledInvoiceNumber;
    public $financePaymentSlipUrl;
    public $rejectionReason;

    /**
     * Create a new message instance.
     */
    public function __construct(ResellerHandoverFg $handover)
    {
        $this->handover = $handover;
        $this->status = $handover->status;
        $this->statusLabel = $this->getStatusLabel($handover->status);
        $this->ticketId = $handover->fg_id;
        $this->category = 'Renewal Quotation';
        $this->invoiceUrl = $handover->invoice_url;

        if ($this->status === 'pending_quotation_confirmation') {
            $this->proceedUrl = URL::signedRoute('reseller.fg-handover.proceed', ['handover' => $handover->id]);
            $this->cancelUrl = URL::signedRoute('reseller.fg-handover.cancel', ['handover' => $handover->id]);
        }

        // For pending_invoice_confirmation, populate invoice data
        if (in_array($this->status, ['pending_invoice_confirmation', 'pending_reseller_payment', 'completed'])) {
            $this->autocountInvoiceNumber = $handover->autocount_invoice_number;

            if ($handover->autocount_invoice) {
                $value = $handover->autocount_invoice;
                if (is_array($value)) {
                    $files = $value;
                } elseif (is_string($value) && json_decode($value)) {
                    $files = json_decode($value, true);
                } else {
                    $files = [$value];
                }
                $this->autocountInvoiceUrl = !empty($files) ? asset('storage/' . $files[0]) : null;
            }

            if ($handover->reseller_invoice) {
                $value = $handover->reseller_invoice;
                if (is_array($value)) {
                    $files = $value;
                } elseif (is_string($value) && json_decode($value)) {
                    $files = json_decode($value, true);
                } else {
                    $files = [$value];
                }
                $this->selfBilledInvoiceUrl = !empty($files) ? asset('storage/' . $files[0]) : null;

                $financeInvoice = \App\Models\FinanceInvoice::where('handover_id', $handover->id)
                    ->where('portal_type', 'reseller_usd')
                    ->latest()
                    ->first();
                $this->selfBilledInvoiceNumber = $financeInvoice ? $financeInvoice->fc_number : null;
            }
        }

        // For completed, generate finance payment slip URL
        if ($this->status === 'completed') {
            if ($handover->reseller_payment_slip) {
                $this->financePaymentSlipUrl = asset('storage/' . $handover->reseller_payment_slip);
            }
        }

        // For rejected, include rejection reason
        if ($this->status === 'pending_reseller_payment' && $handover->rejection_reason) {
            $this->rejectionReason = $handover->rejection_reason;
        }
    }

    /**
     * Check if email should be sent for this status
     */
    public static function shouldSend(string $status): bool
    {
        $skipStatuses = [
            'new',
            'pending_timetec_invoice',
            'pending_timetec_license',
            'pending_timetec_finance',
            'completed',
        ];

        return !in_array($status, $skipStatuses);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $recipients = $this->getRecipients();
        $ccAddresses = $this->getCcAddresses();
        $bccAddresses = $this->getBccAddresses();

        // Log email details
        $this->logEmailDetails($recipients, $bccAddresses, $ccAddresses);

        return new Envelope(
            from: new Address(config('mail.from.address', 'noreply@timeteccloud.com'), 'TimeTec HR CRM'),
            to: $recipients,
            cc: $ccAddresses,
            bcc: $bccAddresses,
            subject: "{$this->ticketId} | {$this->handover->reseller_company_name}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.reseller-handover-fg-status-update',
        );
    }

    /**
     * Get recipients based on status
     */
    private function getRecipients(): array
    {
        // For all other statuses, send to reseller email
        $reseller = \App\Models\ResellerV2::where('reseller_id', $this->handover->reseller_id)->first();

        \Illuminate\Support\Facades\Log::info('FG Reseller Email Lookup Debug', [
            'handover_reseller_id' => $this->handover->reseller_id,
            'reseller_found' => $reseller ? 'Yes' : 'No',
            'reseller_email' => $reseller ? $reseller->email : 'N/A',
        ]);

        if ($reseller && $reseller->email) {
            return [$reseller->email];
        }

        // If no reseller email found, send to admin as fallback
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
        return ['faiz@timeteccloud.com'];
    }

    /**
     * Get formatted status label
     */
    private function getStatusLabel(string $status): string
    {
        $labels = [
            'pending_quotation_confirmation' => 'Pending Quotation Confirmation',
            'pending_invoice_confirmation' => 'Pending Invoice Confirmation',
            'pending_reseller_payment' => 'Pending Reseller Payment',
            'pending_timetec_finance' => 'Pending TimeTec Finance',
            'completed' => 'Completed',
            'rejected' => 'Rejected',
        ];

        return $labels[$status] ?? ucwords(str_replace('_', ' ', $status));
    }

    /**
     * Log email sending details
     */
    private function logEmailDetails(array $recipients, array $bccAddresses, array $ccAddresses = []): void
    {
        \Illuminate\Support\Facades\Log::info('FG Reseller Handover Status Email Sent', [
            'handover_id' => $this->handover->id,
            'fg_id' => $this->handover->fg_id,
            'status' => $this->status,
            'status_label' => $this->statusLabel,
            'reseller_company' => $this->handover->reseller_company_name,
            'subscriber_company' => $this->handover->subscriber_name,
            'email_to' => $recipients,
            'email_cc' => $ccAddresses,
            'email_bcc' => $bccAddresses,
            'subject' => "{$this->handover->fg_id} | {$this->handover->reseller_company_name}",
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
