<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ResellerReminderListAnnouncement extends Mailable
{
    use Queueable, SerializesModels;

    public ?string $resellerCompany;

    public function __construct(?string $resellerCompany = null)
    {
        $this->resellerCompany = $resellerCompany;
    }

    public function build()
    {
        return $this->subject('TimeTec CRM | Reseller Portal | New Memo')
            ->view('emails.reseller-reminder-list-announcement');
    }
}
