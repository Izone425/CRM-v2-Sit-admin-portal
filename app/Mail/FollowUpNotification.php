<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FollowUpNotification extends Mailable
{
    public $content;
    public $viewName; // This holds the Blade template to use
    public $subjectOverride;

    public function __construct($content, $viewName, $subjectOverride = null)
    {
        $this->content = $content;
        $this->viewName = $viewName; // Set the view name dynamically
        $this->subjectOverride = $subjectOverride;
    }

    public function build()
    {
        $subject = $this->subjectOverride
            ?: "Human Resource Management System | " . $this->content['lead']['companyName'];

        return $this->from($this->content['leadOwnerEmail'], $this->content['leadOwnerName'])
                    ->view($this->viewName) // Use the selected template dynamically
                    ->subject($subject)
                    ->with([
                        'lead' => $this->content['lead'],
                        'leadOwnerName' => $this->content['leadOwnerName'],
                    ]);
    }
}
