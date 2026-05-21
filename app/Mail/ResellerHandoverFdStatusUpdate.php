<?php

namespace App\Mail;

use App\Models\ResellerHandoverFd;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Support\Facades\URL;

class ResellerHandoverFdStatusUpdate extends Mailable
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
    public $selfBilledEInvoiceUrl;
    public $isRejected;
    public $rejectReason;

    public function __construct(ResellerHandoverFd $handover, ?string $overrideStatus = null, ?string $rejectReason = null)
    {
        $this->handover = $handover;
        $this->isRejected = $overrideStatus === 'rejected';
        $this->rejectReason = $rejectReason;
        $this->status = $this->isRejected ? 'rejected' : $handover->status;
        $this->statusLabel = $this->isRejected ? 'Rejected' : $this->getStatusLabel($handover->status);
        $this->ticketId = $handover->fd_id;

        $this->category = 'Bill as Reseller';

        if ($this->status === 'pending_quotation_confirmation') {
            $this->invoiceUrl = $handover->invoice_url;
            $this->proceedUrl = URL::signedRoute('reseller.fd-handover.proceed', ['handover' => $handover->id]);
            $this->cancelUrl = URL::signedRoute('reseller.fd-handover.cancel', ['handover' => $handover->id]);
        }

        if ($this->status === 'completed' && $handover->self_billed_einvoice) {
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

        if ($this->status === 'pending_invoice_confirmation') {
            $this->invoiceUrl = $handover->invoice_url;
            $this->proceedUrl = URL::signedRoute('reseller.fd-handover.invoice-proceed', ['handover' => $handover->id]);

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
        }
    }

    public static function shouldSend(string $status): bool
    {
        $skipStatuses = [
            'new',
            'pending_timetec_invoice',
            'pending_timetec_license',
        ];

        return !in_array($status, $skipStatuses);
    }

    public function envelope(): Envelope
    {
        $recipients = $this->getRecipients();
        $bccAddresses = $this->getBccAddresses();

        \Illuminate\Support\Facades\Log::info('Reseller Handover FD Status Email Sent', [
            'handover_id' => $this->handover->id,
            'fd_id' => $this->ticketId,
            'status' => $this->status,
            'email_to' => $recipients,
            'email_bcc' => $bccAddresses,
            'timestamp' => now()->toDateTimeString(),
        ]);

        return new Envelope(
            from: new Address(config('mail.from.address', 'noreply@timeteccloud.com'), 'TimeTec HR CRM'),
            to: $recipients,
            bcc: $bccAddresses,
            subject: "{$this->ticketId} | {$this->handover->reseller_company_name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reseller-handover-status-update',
        );
    }

    private function getRecipients(): array
    {
        $reseller = \App\Models\ResellerV2::where('reseller_id', $this->handover->reseller_id)->first();

        if ($reseller && $reseller->email) {
            return [$reseller->email];
        }

        return ['faiz@timeteccloud.com'];
    }

    private function getBccAddresses(): array
    {
        return ['faiz@timeteccloud.com'];
    }

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

    public function attachments(): array
    {
        return [];
    }
}
