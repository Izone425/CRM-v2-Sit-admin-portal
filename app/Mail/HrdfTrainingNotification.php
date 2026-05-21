<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\TrainingBooking;
use App\Models\TrainingSession;
use App\Models\Lead;
use Carbon\Carbon;

class HrdfTrainingNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $booking;
    public $session;
    public $lead;
    public $attendees;

    public function __construct(TrainingBooking $booking, $attendees)
    {
        $this->booking = $booking;
        $this->session = $booking->trainingSession;
        $this->lead = $booking->lead;
        $this->attendees = $attendees;
    }

    public function envelope(): Envelope
    {
        $companyName = $this->lead->companyDetail->company_name ?? 'Customer';

        return new Envelope(
            from: 'zilih.ng@timeteccloud.com',
            subject: "TIMETEC HR | ONLINE HRDF TRAINING | {$companyName}",
            bcc: ['zilih.ng@timeteccloud.com']
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.hrdf-training-notification',
            with: [
                'booking' => $this->booking,
                'session' => $this->session,
                'lead' => $this->lead,
                'attendees' => $this->attendees,
                'companyName' => $this->lead->companyDetail->company_name ?? 'Customer',
                'day1Date' => Carbon::parse($this->session->day1_date)->format('l | d F Y'),
                'day2Date' => Carbon::parse($this->session->day2_date)->format('l | d F Y'),
                'day3Date' => Carbon::parse($this->session->day3_date)->format('l | d F Y'),
                'day1Module' => $this->session->day1_module ?? 'TimeTec Attendance',
                'day2Module' => $this->session->day2_module ?? 'TimeTec Leave & TimeTec Claim',
                'day3Module' => $this->session->day3_module ?? 'TimeTec Payroll',
                'day1DeckLink' => $this->session->day1_deck_link,
                'day2DeckLink' => $this->session->day2_deck_link,
                'day3DeckLink' => $this->session->day3_deck_link,
                'day1MeetingId' => $this->session->day1_meeting_id,
                'day1Password' => $this->session->day1_meeting_password,
                'day2MeetingId' => $this->session->day2_meeting_id,
                'day2Password' => $this->session->day2_meeting_password,
                'day3MeetingId' => $this->session->day3_meeting_id,
                'day3Password' => $this->session->day3_meeting_password,
            ]
        );
    }
}
