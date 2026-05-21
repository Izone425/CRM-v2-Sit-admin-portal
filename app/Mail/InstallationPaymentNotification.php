<?php

namespace App\Mail;

use App\Models\ResellerInstallationPayment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InstallationPaymentNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ResellerInstallationPayment $payment,
        public string $resellerCompanyName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'INSTALLATION PAYMENT | ' . $this->payment->formatted_id . ' | ' . strtoupper($this->resellerCompanyName),
        );
    }

    public function content(): Content
    {
        $quotationFiles = [];
        $quotationPaths = $this->payment->quotation_path ? json_decode($this->payment->quotation_path, true) : [];
        foreach ($quotationPaths as $path) {
            $quotationFiles[] = [
                'name' => basename($path),
                'url' => asset('storage/' . $path),
            ];
        }

        $invoiceFiles = [];
        $invoicePaths = $this->payment->invoice_path ? json_decode($this->payment->invoice_path, true) : [];
        foreach ($invoicePaths as $path) {
            $invoiceFiles[] = [
                'name' => basename($path),
                'url' => asset('storage/' . $path),
            ];
        }

        return new Content(
            view: 'emails.installation-payment-notification',
            with: [
                'paymentId' => $this->payment->formatted_id,
                'resellerName' => $this->payment->reseller_name,
                'resellerCompanyName' => $this->resellerCompanyName,
                'customerName' => $this->payment->customer_name,
                'installationDate' => $this->payment->installation_date->format('d M Y'),
                'installationAddress' => $this->payment->installation_address,
                'quotationFiles' => $quotationFiles,
                'invoiceFiles' => $invoiceFiles,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
