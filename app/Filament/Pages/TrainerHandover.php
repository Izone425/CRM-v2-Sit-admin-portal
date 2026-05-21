<?php

namespace App\Filament\Pages;

use App\Models\TrainingSession;
use App\Models\TrainingBooking;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class TrainerHandover extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Trainer Handover';
    protected static ?string $title = '';
    protected static string $view = 'filament.pages.trainer-handover';
    protected static ?int $navigationSort = 63;

    // Step 1: Choose Trainer
    public string $selectedTrainer = '';

    // Step 2: Choose Year
    public int $selectedYear;
    public bool $showSessions = false;

    // Expanded states
    public array $expandedSessions = [];
    public array $expandedBookings = [];

    // Available options
    public array $trainers = [
        'TRAINER_1' => 'Trainer 1',
        'TRAINER_2' => 'Trainer 2'
    ];

    public array $years = [];

    public array $trainingCategoryLabels = [
        'HRDF_WEBINAR' => '(01) Online HRDF Training + Online Webinar Training',
        'HRDF' => '(02) Online HRDF Training Only',
        'WEBINAR' => '(03) Online Webinar Training Only'
    ];

    public function mount()
    {
        $currentYear = Carbon::now()->year;
        $this->years = [$currentYear - 1, $currentYear, $currentYear + 1, $currentYear + 2];
        $this->selectedYear = $currentYear;
        $this->selectedTrainer = 'TRAINER_1';
        $this->showSessions = true;
    }

    public function updatedSelectedTrainer()
    {
        $this->loadTrainingSessions();
    }

    public function updatedSelectedYear()
    {
        $this->loadTrainingSessions();
    }

    private function loadTrainingSessions()
    {
        if ($this->selectedTrainer && $this->selectedYear) {
            $this->showSessions = true;
        }
    }

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
                // Past: grey, Current week: green, Future: yellow/orange
                if ($sessionDate->lt($startOfWeek)) {
                    $status = 'past';
                } elseif ($sessionDate->between($startOfWeek, $endOfWeek)) {
                    $status = 'current_week';
                } else {
                    $status = 'future';
                }

                // Get booking counts
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

                // Calculate slot limits
                // HRDF_WEBINAR: 50 HRDF + 100 Webinar, HRDF only: 50, Webinar only: 100
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

    public function toggleSession($sessionId)
    {
        if (in_array($sessionId, $this->expandedSessions)) {
            $this->expandedSessions = array_filter($this->expandedSessions, fn($id) => $id != $sessionId);
        } else {
            $this->expandedSessions[] = $sessionId;
        }
    }

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

    public function getSessionBookings($sessionId)
    {
        return TrainingBooking::where('training_session_id', $sessionId)
            ->with(['lead.companyDetail', 'attendees'])
            ->where('status', '!=', 'CANCELLED')
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('training_type');
    }

    public function getTrainingCategoryLabel($category)
    {
        return $this->trainingCategoryLabels[$category] ?? $category;
    }

    public function bookingHasAttendanceData($sessionId, $day, $bookingId)
    {
        $session = TrainingSession::find($sessionId);
        if (!$session) return false;

        $attendanceReport = $session->{"day{$day}_attendance_report"} ?? [];
        $attendeesData = $attendanceReport['attendees'] ?? $attendanceReport;
        if (empty($attendeesData)) return false;

        // Get booking attendee emails
        $booking = TrainingBooking::with('attendees')->find($bookingId);
        if (!$booking) return false;

        $bookingEmails = $booking->attendees->pluck('email')->filter()->map(fn($e) => strtolower($e))->toArray();
        if (empty($bookingEmails)) return false;

        // Check if any attendance record matches booking emails (excluding organizers)
        foreach ($attendeesData as $record) {
            $role = strtolower($record['role'] ?? '');
            if (in_array($role, ['organizer', 'coorganizer', 'co-organizer'])) continue;

            $email = strtolower($record['email'] ?? '');
            if (!empty($email) && in_array($email, $bookingEmails)) {
                return true;
            }
        }

        return false;
    }

    public function downloadAttendancePdf($sessionId, $day, $bookingId = null)
    {
        $session = TrainingSession::find($sessionId);

        if (!$session) {
            return;
        }

        // Get attendance report for the specific day
        $attendanceReport = $session->{"day{$day}_attendance_report"} ?? [];
        $dayDate = $session->{"day{$day}_date"};
        $dayModule = $session->{"day{$day}_module"} ?? 'OPERATIONAL MODULES';
        $rawMeetingId = $session->{"day{$day}_meeting_id"} ?? '';
        $meetingId = trim(chunk_split(str_replace(' ', '', $rawMeetingId), 3, ' '));

        // Get booking and company info if provided
        $clientCompany = '';
        $bookingEmails = [];
        $maxParticipants = 0;
        if ($bookingId) {
            $booking = TrainingBooking::with(['lead.companyDetail', 'attendees'])->find($bookingId);
            if ($booking) {
                $clientCompany = $booking->lead->companyDetail->company_name ?? $booking->company_name ?? '';
                $bookingEmails = $booking->attendees->pluck('email')->filter()->map(fn($e) => strtolower($e))->toArray();
                $maxParticipants = $booking->attendees->count();
            }
        }

        // Format title based on training_module
        $trainingModule = $session->training_module ?? 'OPERATIONAL';
        $moduleLabel = strtoupper($trainingModule) === 'OPERATIONAL' ? 'OPERATIONAL MODULES' : 'STRATEGIC MODULES';

        // Calculate totals from attendance report
        // The structure has 'attendees' array with intervals
        $attendeesData = $attendanceReport['attendees'] ?? $attendanceReport;

        // Group attendees by email to consolidate duplicates
        // Exclude Organizer and Coorganizer roles
        $groupedAttendees = [];
        foreach ($attendeesData as $record) {
            // Skip organizers and co-organizers
            $role = strtolower($record['role'] ?? '');
            if (in_array($role, ['organizer', 'coorganizer', 'co-organizer'])) {
                continue;
            }

            $email = $record['email'] ?? '';
            $name = $record['name'] ?? $record['displayName'] ?? '-';

            // Filter by booking attendees if bookingId provided
            if (!empty($bookingEmails) && (empty($email) || !in_array(strtolower($email), $bookingEmails))) {
                continue;
            }

            // Use email as key, or name if email is empty
            $key = !empty($email) ? $email : $name . '_' . uniqid();

            if (!isset($groupedAttendees[$key])) {
                $groupedAttendees[$key] = [
                    'name' => $name,
                    'email' => $email,
                    'intervals' => [],
                    'total_minutes' => 0,
                ];
            }

            // Accumulate total minutes
            $minutes = $record['total_attendance_minutes'] ?? ($record['total_attendance_seconds'] ?? 0) / 60;
            $groupedAttendees[$key]['total_minutes'] += $minutes;

            // Merge all intervals
            if (!empty($record['intervals'])) {
                $groupedAttendees[$key]['intervals'] = array_merge(
                    $groupedAttendees[$key]['intervals'],
                    $record['intervals']
                );
            }
        }

        $participantCount = count($groupedAttendees);
        $totalMinutes = 0;
        $attendees = [];

        foreach ($groupedAttendees as $record) {
            $minutes = $record['total_minutes'];
            $totalMinutes += $minutes;

            // Get the earliest join time and latest leave time from all intervals
            $earliestJoin = null;
            $latestLeave = null;

            if (!empty($record['intervals'])) {
                foreach ($record['intervals'] as $interval) {
                    $joinTime = $interval['join_time'] ?? null;
                    $leaveTime = $interval['leave_time'] ?? null;

                    if ($joinTime && (!$earliestJoin || $joinTime < $earliestJoin)) {
                        $earliestJoin = $joinTime;
                    }
                    if ($leaveTime && (!$latestLeave || $leaveTime > $latestLeave)) {
                        $latestLeave = $leaveTime;
                    }
                }
            }

            // Format join/leave times in Malaysia timezone (UTC+8)
            $formattedJoin = $earliestJoin ? Carbon::parse($earliestJoin)->setTimezone('Asia/Kuala_Lumpur')->format('m/d/y, g:i:s A') : '-';
            $formattedLeave = $latestLeave ? Carbon::parse($latestLeave)->setTimezone('Asia/Kuala_Lumpur')->format('m/d/y, g:i:s A') : '-';

            $attendees[] = [
                'meeting_id' => $meetingId,
                'name' => strtoupper($record['name']),
                'join_time' => $formattedJoin,
                'leave_time' => $formattedLeave,
                'minutes' => round($minutes),
            ];
        }

        // Trainer name based on profile
        $trainerName = match($session->trainer_profile) {
            'TRAINER_1' => 'Mohd Hanif Bin Razali',
            'TRAINER_2' => 'Ahmad Firdaus',
            default => 'Trainer'
        };

        $data = [
            'title' => "TIMETEC HR - {$moduleLabel}",
            'date' => $dayDate ? Carbon::parse($dayDate)->format('d-M-y') : '-',
            'duration' => 480, // 8 hours default
            'participantMinutes' => round($totalMinutes),
            'startTime' => '9:00 AM',
            'participantCount' => $participantCount,
            'maxParticipants' => $maxParticipants,
            'dayLabel' => "Day {$day}",
            'meetingId' => $meetingId,
            'attendees' => $attendees,
            'trainerName' => $trainerName,
            'clientCompany' => $clientCompany,
            'signatureDate' => $dayDate ? Carbon::parse($dayDate)->format('d') . ' ' . strtoupper(Carbon::parse($dayDate)->format('F')) . ' ' . Carbon::parse($dayDate)->format('Y') : '-',
        ];

        $pdf = Pdf::setOptions([
            'isPhpEnabled' => true,
            'isRemoteEnabled' => true
        ])->loadView('pdf.training-attendance', $data);

        $filename = "Attendance_List_Day{$day}_" . ($dayDate ? Carbon::parse($dayDate)->format('Ymd') : 'unknown') . ".pdf";

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename);
    }
}
