<?php

namespace App\Mail;

use App\Models\ResellerHandoverFf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Support\Facades\URL;

class ResellerHandoverFfStatusUpdate extends Mailable
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
    public $proceedLabel;
    public $selfBilledEInvoiceUrl;

    public function __construct(ResellerHandoverFf $handover)
    {
        $this->handover = $handover;
        $this->status = $handover->status;
        $this->statusLabel = $this->getStatusLabel($handover->status);
        $this->ticketId = $handover->ff_id;

        $this->category = match ($handover->category) {
            'renewal_subscription' => 'Renewal Subscription',
            'addon_headcount' => 'AddOn Headcount',
            default => 'Bill as End User',
        };

        if ($this->status === 'pending_quotation_confirmation') {
            $this->invoiceUrl = $handover->invoice_url;
            $this->proceedUrl = URL::signedRoute('reseller.ff-handover.make-payment', ['handover' => $handover->id]);
            $this->cancelUrl = URL::signedRoute('reseller.ff-handover.cancel', ['handover' => $handover->id]);
            $this->proceedLabel = 'Make Payment';
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
    }

    public static function shouldSend(string $status): bool
    {
        $skipStatuses = [
            'new',
        ];

        return !in_array($status, $skipStatuses);
    }

    public function envelope(): Envelope
    {
        $recipients = $this->getRecipients();
        $bccAddresses = $this->getBccAddresses();

        \Illuminate\Support\Facades\Log::info('Reseller Handover FF Status Email Sent', [
            'handover_id' => $this->handover->id,
            'ff_id' => $this->ticketId,
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
            'completed' => 'Completed',
        ];

        return $labels[$status] ?? ucwords(str_replace('_', ' ', $status));
    }

    public function attachments(): array
    {
        return [];
    }
}
