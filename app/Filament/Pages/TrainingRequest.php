<?php

namespace App\Filament\Pages;

use App\Models\TrainingSession;
use App\Models\TrainingBooking;
use App\Models\TrainingAttendee;
use App\Models\Lead;
use App\Models\SoftwareHandover;
use App\Models\HrdfClaim;
use App\Mail\WebinarTrainingNotification;
use App\Mail\HrdfTrainingNotification;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TrainingRequest extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Training Request';
    protected static ?string $title = '';
    protected static string $view = 'filament.pages.training-request';
    protected static ?int $navigationSort = 62;
    protected static bool $shouldRegisterNavigation = false;

    // Step 1: Choose Trainer (can be set from URL parameter)
    public string $selectedTrainer = '';

    // Step 2: Choose Year
    public int $selectedYear;
    public bool $showSessions = false;

    // Step 3: Choose Training Session
    public ?int $selectedSessionId = null;
    public array $expandedSessions = [];
    public array $expandedBookings = [];
    public string $selectedSessionCategory = '';

    // Step 5: Add Training Request Modal
    public bool $showRequestModal = false;
    public string $selectedTrainingType = '';

    // Form Data for Training Request
    public string $companySearchTerm = '';
    public ?int $selectedLeadId = null;
    public string $trainingCategory = '';
    public array $attendees = [];
    public string $hrdfStatus = 'BOOKING';
    public int $hrdfParticipantCount = 1;

    // HRDF Grant ID Search
    public string $hrdfGrantSearchTerm = '';
    public ?int $selectedHrdfClaimId = null;

    // Apply Modal - For adding attendees to existing booking
    public bool $showApplyModal = false;
    public ?int $applyBookingId = null;
    public $applyBooking = null;
    public array $applyAttendees = [];

    // Approve Modal - For confirming approval of training request
    public bool $showApproveModal = false;
    public ?int $approveBookingId = null;
    public $approveBooking = null;

    // Cancel Modal - For confirming cancellation of training request
    public bool $showCancelModal = false;
    public ?int $cancelBookingId = null;
    public $cancelBooking = null;
    public string $cancelReason = '';

    // Available options
    public array $trainers = [
        'TRAINER_1' => 'Trainer 1',
        'TRAINER_2' => 'Trainer 2'
    ];

    public array $years = [];

    public array $trainingTypes = [
        'HRDF' => 'Online HRDF Training',
        'WEBINAR' => 'Online Webinar Training'
    ];

    public array $trainingCategories = [
        'NEW_TRAINING' => 'New Training',
        'RE_TRAINING' => 'Re-Training'
    ];

    public array $hrdfStatuses = [
        'BOOKING' => 'Booking',
        'APPLY' => 'Apply'
    ];

    public function mount()
    {
        $currentYear = Carbon::now()->year;
        $this->years = [$currentYear, $currentYear + 1, $currentYear + 2];
        $this->selectedYear = $currentYear;

        // Get trainer from URL parameter
        $trainer = request()->query('trainer', '');
        if (in_array($trainer, ['TRAINER_1', 'TRAINER_2'])) {
            $this->selectedTrainer = $trainer;
            $this->showSessions = true;
        } else {
            $this->selectedTrainer = 'TRAINER_1';
            $this->showSessions = true;
        }
    }

    // When trainer changes, reload sessions
    public function updatedSelectedTrainer()
    {
        $this->showSessions = true;
    }

    // When year changes, reload sessions
    public function updatedSelectedYear()
    {
        $this->showSessions = true;
    }

    // When company search term changes, reset selected lead to allow new search
    public function updatedCompanySearchTerm()
    {
        $this->selectedLeadId = null;
    }

    // Get training sessions with color coding
    #[Computed]
    public function trainingSessions()
    {
        if (!$this->showSessions) {
            return collect();
        }

        return TrainingSession::where('year', $this->selectedYear)
            ->where('trainer_profile', $this->selectedTrainer)
            ->orderBy('day1_date')
            ->get()
            ->map(function ($session) {
                $now = Carbon::now();
                $sessionDate = Carbon::parse($session->day1_date);

                // Get start and end of current week (Monday to Sunday)
                $startOfWeek = $now->copy()->startOfWeek(Carbon::MONDAY);
                $endOfWeek = $now->copy()->endOfWeek(Carbon::SUNDAY);

                // Color coding based on date
                if ($sessionDate->lt($startOfWeek)) {
                    $status = 'past';
                } elseif ($sessionDate->between($startOfWeek, $endOfWeek)) {
                    $status = 'current_week';
                } else {
                    $status = 'future';
                }

                // Get booking counts (use MAX of active_attendees_count or expected_attendees)
                $hrdfCount = TrainingBooking::where('training_session_id', $session->id)
                    ->where('training_type', 'HRDF')
                    ->where('status', '!=', 'CANCELLED')
                    ->withCount(['activeAttendees'])
                    ->get()
                    ->sum(fn($booking) => max($booking->active_attendees_count, $booking->expected_attendees ?? 0));

                $webinarCount = TrainingBooking::where('training_session_id', $session->id)
                    ->where('training_type', 'WEBINAR')
                    ->where('status', '!=', 'CANCELLED')
                    ->withCount(['activeAttendees'])
                    ->get()
                    ->sum(fn($booking) => max($booking->active_attendees_count, $booking->expected_attendees ?? 0));

                $hrdfLimit = 50;
                $webinarLimit = 100;

                return [
                    'session' => $session,
                    'status' => $status,
                    'hrdf_count' => $hrdfCount,
                    'webinar_count' => $webinarCount,
                    'hrdf_limit' => $hrdfLimit,
                    'webinar_limit' => $webinarLimit,
                    'training_category' => $session->training_category,
                    'is_expanded' => in_array($session->id, $this->expandedSessions)
                ];
            });
    }

    // Toggle session expansion
    public function toggleSession($sessionId)
    {
        if (in_array($sessionId, $this->expandedSessions)) {
            $this->expandedSessions = array_filter($this->expandedSessions, fn($id) => $id != $sessionId);
        } else {
            $this->expandedSessions[] = $sessionId;
        }
    }

    // Toggle booking expansion to show attendees
    public function toggleBooking($bookingId)
    {
        if (in_array($bookingId, $this->expandedBookings)) {
            $this->expandedBookings = array_filter($this->expandedBookings, fn($id) => $id != $bookingId);
        } else {
            $this->expandedBookings[] = $bookingId;
        }
    }

    public function isBookingExpanded($bookingId)
    {
        return in_array($bookingId, $this->expandedBookings);
    }

    // Get bookings for expanded session
    public function getSessionBookings($sessionId)
    {
        return TrainingBooking::where('training_session_id', $sessionId)
            ->with(['lead.companyDetail'])
            ->where('status', '!=', 'CANCELLED')
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('training_type');
    }

    // Show Add Training Request Modal
    public function showAddRequestModal($sessionId)
    {
        $session = TrainingSession::find($sessionId);

        if (Carbon::parse($session->day1_date)->lt(Carbon::now())) {
            Notification::make()
                ->title('Session Not Available')
                ->body('Cannot create training request for past sessions.')
                ->warning()
                ->send();
            return;
        }

        if (!$this->hasCompleteMeetingLinks($session)) {
            Notification::make()
                ->title('No Meeting Link Available')
                ->body('Cannot create training request. Meeting links are required for all training days.')
                ->warning()
                ->send();
            return;
        }

        $this->selectedSessionId = $sessionId;
        $this->selectedSessionCategory = $session->training_category;
        $this->resetRequestForm();
        $this->showRequestModal = true;
    }

    private function resetRequestForm()
    {
        $this->selectedTrainingType = '';
        $this->companySearchTerm = '';
        $this->selectedLeadId = null;
        $this->trainingCategory = '';
        $this->attendees = [['name' => '', 'email' => '', 'phone' => '']];
        $this->hrdfStatus = 'BOOKING';
        $this->hrdfParticipantCount = 1;
        $this->hrdfGrantSearchTerm = '';
        $this->selectedHrdfClaimId = null;
    }

    // Select Training Type
    public function selectTrainingType($type)
    {
        $this->selectedTrainingType = $type;

        if ($type === 'WEBINAR' && $this->selectedLeadId) {
            $this->populateWebinarAttendees($this->selectedLeadId);
        } elseif ($type === 'HRDF') {
            $this->attendees = [['name' => '', 'email' => '', 'phone' => '']];
        }
    }

    // Populate webinar attendees from software handovers
    private function populateWebinarAttendees($leadId)
    {
        $softwareHandovers = SoftwareHandover::where('lead_id', $leadId)
            ->whereNotNull('implementation_pics')
            ->where('implementation_pics', '!=', '')
            ->get();

        $attendees = [];
        $uniqueEmails = [];

        foreach ($softwareHandovers as $handover) {
            $implementerPics = $handover->implementation_pics;

            if (is_string($implementerPics)) {
                $implementerPics = json_decode($implementerPics, true);
            }

            if (is_array($implementerPics)) {
                foreach ($implementerPics as $pic) {
                    $email = $pic['pic_email_impl'] ?? '';

                    if (!empty($email) && !in_array($email, $uniqueEmails)) {
                        $uniqueEmails[] = $email;

                        $attendees[] = [
                            'name' => $pic['pic_name_impl'] ?? '',
                            'email' => $email,
                            'phone' => $pic['pic_phone_impl'] ?? ''
                        ];
                    }
                }
            }
        }

        if (empty($attendees)) {
            $attendees = [['name' => '', 'email' => '', 'phone' => '']];
        }

        $this->attendees = $attendees;
    }

    // Search for companies/leads
    public function searchCompanies()
    {
        if (empty($this->companySearchTerm)) {
            return collect();
        }

        return Lead::with('companyDetail')
            ->where(function ($query) {
                $query->where('lead_code', 'like', '%' . $this->companySearchTerm . '%')
                      ->orWhereHas('companyDetail', function ($q) {
                          $q->where('company_name', 'like', '%' . $this->companySearchTerm . '%');
                      });
            })
            ->limit(10)
            ->get();
    }

    // Search for HRDF claims
    public function searchHrdfClaims()
    {
        if (empty($this->hrdfGrantSearchTerm)) {
            return collect();
        }

        return HrdfClaim::where(function ($query) {
                $query->where('hrdf_grant_id', 'like', '%' . $this->hrdfGrantSearchTerm . '%')
                      ->orWhere('company_name', 'like', '%' . $this->hrdfGrantSearchTerm . '%');
            })
            ->whereNotNull('hrdf_grant_id')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    public function selectHrdfClaim($claimId)
    {
        $this->selectedHrdfClaimId = $claimId;
        $claim = HrdfClaim::find($claimId);

        if ($claim) {
            $this->hrdfGrantSearchTerm = $claim->hrdf_grant_id . ' - ' . $claim->company_name;
        }
    }

    public function clearHrdfClaim()
    {
        $this->selectedHrdfClaimId = null;
        $this->hrdfGrantSearchTerm = '';
    }

    public function selectLead($leadId)
    {
        $this->selectedLeadId = $leadId;
        $lead = Lead::with('companyDetail')->find($leadId);

        if ($lead) {
            $this->companySearchTerm = $lead->companyDetail->company_name ?? '';

            if ($this->selectedTrainingType === 'WEBINAR') {
                $this->populateWebinarAttendees($leadId);
            } else {
                $this->prefillAttendeeFromSoftwareHandover($leadId);
            }
        }
    }

    private function prefillAttendeeFromSoftwareHandover($leadId)
    {
        $softwareHandover = SoftwareHandover::where('lead_id', $leadId)
            ->whereIn('status', ['Approved', 'New'])
            ->orderBy('created_at', 'desc')
            ->first();

        if ($softwareHandover && $softwareHandover->implementation_pics) {
            $implementationPics = $softwareHandover->implementation_pics;

            if (is_string($implementationPics)) {
                $implementationPics = json_decode($implementationPics, true);
            }

            if (is_array($implementationPics) && !empty($implementationPics)) {
                $firstPic = $implementationPics[0] ?? null;

                if ($firstPic) {
                    $this->attendees = [[
                        'name' => strtoupper($firstPic['pic_name_impl'] ?? ''),
                        'email' => $firstPic['pic_email_impl'] ?? '',
                        'phone' => $firstPic['pic_phone_impl'] ?? ''
                    ]];
                    return;
                }
            }
        }

        $this->attendees = [['name' => '', 'email' => '', 'phone' => '']];
    }

    // Submit Training Request
    public function submitRequest()
    {
        $rules = [
            'selectedTrainingType' => 'required',
            'selectedLeadId' => 'required',
            'trainingCategory' => 'required',
        ];

        $attendeesRequired = !($this->hrdfStatus === 'BOOKING');

        if ($attendeesRequired) {
            $rules['attendees.*.name'] = 'required|string|max:255';
            $rules['attendees.*.email'] = 'required|email|max:255';
        } else {
            $maxParticipants = $this->selectedTrainingType === 'HRDF' ? 50 : 100;
            $rules['hrdfParticipantCount'] = "required|integer|min:1|max:{$maxParticipants}";
        }

        $this->validate($rules);

        $session = TrainingSession::find($this->selectedSessionId);
        $lead = Lead::find($this->selectedLeadId);

        $currentCount = TrainingBooking::where('training_session_id', $this->selectedSessionId)
            ->where('training_type', $this->selectedTrainingType)
            ->where('status', '!=', 'CANCELLED')
            ->withCount(['activeAttendees'])
            ->get()
            ->sum(fn($booking) => max($booking->active_attendees_count, $booking->expected_attendees ?? 0));

        $slotLimit = $this->selectedTrainingType === 'HRDF' ? 50 : 100;

        $attendeeCount = count(array_filter($this->attendees, fn($attendee) => !empty($attendee['name']) && !empty($attendee['email'])));
        $isBookingStatus = $this->hrdfStatus === 'BOOKING';

        if ($isBookingStatus && $attendeeCount === 0) {
            $attendeeCount = $this->hrdfParticipantCount;
        }

        if ($currentCount + $attendeeCount > $slotLimit) {
            $availableSlots = $slotLimit - $currentCount;
            $this->addError('slotLimit', "Slot limit exceeded. Cannot add {$attendeeCount} participants. Available slots: {$availableSlots}");
            return;
        }

        $runningNumber = $this->generateRunningNumber($this->selectedTrainingType);

        $bookingData = [
            'handover_id' => $runningNumber,
            'training_session_id' => $this->selectedSessionId,
            'lead_id' => $this->selectedLeadId,
            'training_type' => $this->selectedTrainingType,
            'training_category' => $this->trainingCategory,
            'status' => $this->hrdfStatus,
            'submitted_by' => auth()->user()->name,
            'submitted_at' => now(),
            'expected_attendees' => $isBookingStatus ? $this->hrdfParticipantCount : $attendeeCount
        ];

        if ($this->selectedTrainingType === 'HRDF' && $this->selectedHrdfClaimId) {
            $bookingData['hrdf_claim_id'] = $this->selectedHrdfClaimId;
        }

        $booking = TrainingBooking::create($bookingData);

        $createdAttendees = [];
        foreach ($this->attendees as $attendee) {
            if (!empty($attendee['name']) && !empty($attendee['email'])) {
                TrainingAttendee::create([
                    'training_booking_id' => $booking->id,
                    'name' => $attendee['name'],
                    'email' => $attendee['email'],
                    'phone' => $attendee['phone'] ?? '',
                    'attendance_status' => 'REGISTERED',
                    'registered_at' => now()
                ]);

                $createdAttendees[] = $attendee;
            }
        }

        Notification::make()
            ->title('Training Request Created')
            ->body("Training request {$runningNumber} has been created with " . $attendeeCount . " attendees. Email will be sent upon approval.")
            ->success()
            ->send();

        $this->closeRequestModal();
    }

    public function addAttendee()
    {
        $this->attendees[] = ['name' => '', 'email' => '', 'phone' => ''];
    }

    public function removeAttendee($index)
    {
        if (count($this->attendees) > 1) {
            unset($this->attendees[$index]);
            $this->attendees = array_values($this->attendees);
        }
    }

    private function generateRunningNumber($type)
    {
        $year = substr($this->selectedYear, -2);
        $prefix = $type === 'HRDF' ? 'TH_' : 'TW_';

        $lastNumber = TrainingBooking::where('handover_id', 'like', $prefix . $year . '%')
            ->orderBy('handover_id', 'desc')
            ->value('handover_id');

        if ($lastNumber) {
            $lastSequence = (int)substr($lastNumber, -4);
            $nextSequence = $lastSequence + 1;
        } else {
            $nextSequence = 1;
        }

        return $prefix . $year . str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
    }

    public function closeRequestModal()
    {
        $this->showRequestModal = false;
        $this->resetRequestForm();
    }

    public function hasCompleteMeetingLinks($session)
    {
        return !empty($session->day1_meeting_link) &&
               !empty($session->day2_meeting_link) &&
               !empty($session->day3_meeting_link);
    }

    // Cancel Modal Methods
    public function openCancelModal($bookingId)
    {
        $this->cancelBookingId = $bookingId;
        $this->cancelBooking = TrainingBooking::with(['lead.companyDetail'])->find($bookingId);
        $this->showCancelModal = true;
    }

    public function closeCancelModal()
    {
        $this->showCancelModal = false;
        $this->cancelBookingId = null;
        $this->cancelBooking = null;
        $this->cancelReason = '';
    }

    public function confirmCancel()
    {
        if (empty(trim($this->cancelReason))) {
            Notification::make()
                ->title('Cancel Reason Required')
                ->body('Please provide a reason for cancellation.')
                ->danger()
                ->send();
            return;
        }

        $booking = TrainingBooking::find($this->cancelBookingId);

        if ($booking && ($booking->submitted_by === auth()->user()->name || auth()->user()->role_id == 3)) {
            $booking->update([
                'status' => 'CANCELLED',
                'cancel_reason' => strtoupper(trim($this->cancelReason))
            ]);

            Notification::make()
                ->title('Training Request Cancelled')
                ->body("Training request {$booking->handover_id} has been cancelled.")
                ->success()
                ->send();
        }

        $this->closeCancelModal();
    }

    // Approve Modal Methods
    public function openApproveModal($bookingId)
    {
        $this->approveBookingId = $bookingId;
        $this->approveBooking = TrainingBooking::with(['lead.companyDetail'])->find($bookingId);
        $this->showApproveModal = true;
    }

    public function closeApproveModal()
    {
        $this->showApproveModal = false;
        $this->approveBookingId = null;
        $this->approveBooking = null;
    }

    public function confirmApprove()
    {
        if (auth()->user()->role_id != 3) {
            Notification::make()
                ->title('Unauthorized')
                ->body('You do not have permission to approve training requests.')
                ->danger()
                ->send();
            $this->closeApproveModal();
            return;
        }

        $booking = TrainingBooking::find($this->approveBookingId);

        if ($booking && $booking->status === 'APPLY') {
            $booking->update([
                'status' => 'APPROVED',
            ]);

            $attendees = $booking->attendees;
            $attendeeList = $attendees->map(fn($a) => ['name' => $a->name, 'email' => $a->email, 'phone' => $a->phone])->toArray();

            if ($attendees->count() > 0) {
                try {
                    if ($booking->training_type === 'HRDF') {
                        foreach ($attendees as $attendee) {
                            Mail::to($attendee->email)
                                ->send(new HrdfTrainingNotification($booking, $attendeeList));
                        }
                    } elseif ($booking->training_type === 'WEBINAR') {
                        foreach ($attendees as $attendee) {
                            Mail::to($attendee->email)
                                ->send(new WebinarTrainingNotification($booking, $attendeeList));
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to send training email on approval: ' . $e->getMessage());
                }
            }

            Notification::make()
                ->title('Training Request Approved')
                ->body("Training request {$booking->handover_id} has been approved. Email notifications have been sent to all attendees.")
                ->success()
                ->send();
        }

        $this->closeApproveModal();
    }

    // Apply Modal Methods
    public function openApplyModal($bookingId)
    {
        $this->applyBookingId = $bookingId;
        $this->applyBooking = TrainingBooking::with(['lead.companyDetail', 'attendees'])->find($bookingId);

        $existingAttendees = $this->applyBooking->attendees ?? collect();
        $expectedCount = $this->applyBooking->expected_attendees ?? 1;

        $this->applyAttendees = [];

        foreach ($existingAttendees as $attendee) {
            $this->applyAttendees[] = [
                'name' => $attendee->name,
                'email' => $attendee->email,
                'phone' => $attendee->phone ?? ''
            ];
        }

        $remainingSlots = $expectedCount - count($this->applyAttendees);
        for ($i = 0; $i < $remainingSlots; $i++) {
            $this->applyAttendees[] = ['name' => '', 'email' => '', 'phone' => ''];
        }

        if (empty($this->applyAttendees)) {
            $this->applyAttendees = [['name' => '', 'email' => '', 'phone' => '']];
        }

        $this->showApplyModal = true;
    }

    public function closeApplyModal()
    {
        $this->showApplyModal = false;
        $this->applyBookingId = null;
        $this->applyBooking = null;
        $this->applyAttendees = [];
        $this->resetErrorBag();
    }

    public function addApplyAttendee()
    {
        $expectedCount = $this->applyBooking->expected_attendees ?? 999;
        if (count($this->applyAttendees) >= $expectedCount) {
            return;
        }

        $this->applyAttendees[] = ['name' => '', 'email' => '', 'phone' => ''];
    }

    public function removeApplyAttendee($index)
    {
        if (count($this->applyAttendees) > 1) {
            unset($this->applyAttendees[$index]);
            $this->applyAttendees = array_values($this->applyAttendees);
        }
    }

    public function submitApply()
    {
        $this->validate([
            'applyAttendees.*.name' => 'required|string|max:255',
            'applyAttendees.*.email' => 'required|email|max:255',
        ]);

        $booking = TrainingBooking::find($this->applyBookingId);

        if (!$booking) {
            Notification::make()
                ->title('Error')
                ->body('Booking not found.')
                ->danger()
                ->send();
            return;
        }

        TrainingAttendee::where('training_booking_id', $booking->id)->delete();

        $createdAttendees = [];
        foreach ($this->applyAttendees as $attendee) {
            if (!empty($attendee['name']) && !empty($attendee['email'])) {
                TrainingAttendee::create([
                    'training_booking_id' => $booking->id,
                    'name' => $attendee['name'],
                    'email' => $attendee['email'],
                    'phone' => $attendee['phone'] ?? '',
                    'attendance_status' => 'REGISTERED',
                    'registered_at' => now()
                ]);

                $createdAttendees[] = $attendee;
            }
        }

        $booking->update([
            'status' => 'APPLY'
        ]);

        Notification::make()
            ->title('Apply Submitted')
            ->body("Attendee details have been added to {$booking->handover_id}. Status changed to APPLY. Email will be sent upon approval.")
            ->success()
            ->send();

        $this->closeApplyModal();
    }

    public static function getSlug(): string
    {
        return 'training-request';
    }
}
