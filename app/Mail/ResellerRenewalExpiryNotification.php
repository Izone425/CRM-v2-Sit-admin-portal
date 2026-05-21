<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ResellerRenewalExpiryNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $resellerName;
    public $companies;

    public function __construct(string $resellerName, array $companies)
    {
        $this->resellerName = $resellerName;
        $this->companies = $companies;
    }

    public function build()
    {
        return $this->subject('TIMETEC RENEWAL NOTIFICATION | ' . strtoupper($this->resellerName))
            ->view('emails.reseller-renewal-expiry');
    }
}
