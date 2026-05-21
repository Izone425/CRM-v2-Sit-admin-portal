<?php

namespace App\Mail;

use App\Models\ResellerHandover;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Support\Facades\URL;

class ResellerHandoverStatusUpdate extends Mailable
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
    public $selfBilledEInvoiceUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(ResellerHandover $handover)
    {
        $this->handover = $handover;
        $this->status = $handover->status;
        $this->statusLabel = $this->getStatusLabel($handover->status);
        $this->ticketId = $handover->fb_id;
        $this->category = 'Renewal Quotation';

        // For pending_quotation_confirmation, generate invoice URL and action URLs
        if ($this->status === 'pending_quotation_confirmation') {
            $this->invoiceUrl = $handover->invoice_url;
            $this->proceedUrl = URL::signedRoute('reseller.handover.proceed', ['handover' => $handover->id]);
            $this->cancelUrl = URL::signedRoute('reseller.handover.cancel', ['handover' => $handover->id]);
        }

        // For pending_invoice_confirmation, generate file URLs and proceed URL
        if ($this->status === 'pending_invoice_confirmation') {
            $this->invoiceUrl = $handover->invoice_url;
            $this->proceedUrl = URL::signedRoute('reseller.handover.invoice-proceed', ['handover' => $handover->id]);

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

                $financeInvoice = \App\Models\FinanceInvoice::where('reseller_handover_id', $handover->id)->latest()->first();
                $this->selfBilledInvoiceNumber = $financeInvoice ? $financeInvoice->fc_number : null;
            }
        }

        // For completed, generate finance payment slip and self billed e-invoice URLs
        if ($this->status === 'completed') {
            if ($handover->finance_payment_slip) {
                $this->financePaymentSlipUrl = asset('storage/' . $handover->finance_payment_slip);
            }

            if ($handover->self_billed_einvoice) {
                $value = $handover->self_billed_einvoice;
                if (is_array($value)) {
                    $files = $value;
                } elseif (is_string($value) && json_decode($value)) {
                    $files = json_decode($value, true);
                } else {
                    $files = [$value];
                }
                $this->selfBilledEInvoiceUrl = !empty($files) ? asset('storage/' . $files[0]) : null;
            }
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
            view: 'emails.reseller-handover-status-update',
        );
    }

    /**
     * Get recipients based on status
     */
    private function getRecipients(): array
    {
        // For pending_timetec_finance, send to admin/finance team
        if ($this->status === 'pending_timetec_finance') {
            return [
                'faiz@timeteccloud.com',
            ];
        }

        // For all other statuses (pending_confirmation, pending_invoice_confirmation,
        // pending_reseller_payment, completed), send to reseller email
        $reseller = \App\Models\ResellerV2::where('reseller_id', $this->handover->reseller_id)->first();

        // Debug logging
        \Illuminate\Support\Facades\Log::info('Reseller Email Lookup Debug', [
            'handover_reseller_id' => $this->handover->reseller_id,
            'reseller_found' => $reseller ? 'Yes' : 'No',
            'reseller_email' => $reseller ? $reseller->email : 'N/A',
            'reseller_id_match' => $reseller ? $reseller->reseller_id : 'N/A',
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
        $cc = [];

        // For pending_timetec_finance, use CC instead of BCC
        if ($this->status === 'pending_timetec_finance') {
            $cc = [
                'faiz@timeteccloud.com',
            ];
        }

        return $cc;
    }

    /**
     * Get BCC addresses based on status
     */
    private function getBccAddresses(): array
    {
        $bcc = [];

        switch ($this->status) {
            case 'pending_quotation_confirmation':
            case 'pending_invoice_confirmation':
            case 'completed':
                // Only BCC faiz
                $bcc = ['faiz@timeteccloud.com'];
                break;

            case 'pending_reseller_payment':
                $bcc = [
                    'faiz@timeteccloud.com',
                ];
                break;

            case 'pending_timetec_finance':
                // Use CC instead of BCC (handled in getCcAddresses)
                $bcc = [];
                break;
        }

        return $bcc;
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
        ];

        return $labels[$status] ?? ucwords(str_replace('_', ' ', $status));
    }

    /**
     * Log email sending details
     */
    private function logEmailDetails(array $recipients, array $bccAddresses, array $ccAddresses = []): void
    {
        \Illuminate\Support\Facades\Log::info('Reseller Handover Status Email Sent', [
            'handover_id' => $this->handover->id,
            'fb_id' => $this->handover->fb_id,
            'status' => $this->status,
            'status_label' => $this->statusLabel,
            'reseller_company' => $this->handover->reseller_company_name,
            'subscriber_company' => $this->handover->subscriber_name,
            'email_to' => $recipients,
            'email_cc' => $ccAddresses,
            'email_bcc' => $bccAddresses,
            'subject' => "{$this->handover->fb_id} | {$this->handover->reseller_company_name}",
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
