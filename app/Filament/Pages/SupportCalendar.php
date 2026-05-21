<?php

namespace App\Filament\Pages;

use App\Models\OvertimeSchedule;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class SupportCalendar extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationLabel = 'Overtime Schedule';
    protected static ?string $navigationGroup = 'Support Information';
    protected static ?string $title = 'Support - Overtime Weekend ';
    protected static ?int $navigationSort = 10;

    protected static string $view = 'filament.pages.support-calendar';

    public $selectedYear = '';
    public $months = [];
    public $users = [];
    public $editMode = false;
    public $viewMode = 'calendar'; // 'calendar' or 'table'
    public $staffSummary = [];
    public $selectedUserIds = [];

    public function mount()
    {
        $this->selectedYear = request()->query('year', Carbon::now()->year);
        $this->loadCalendarData();
        $this->users = User::whereIn('role_id', [4, 5, 6, 7, 8])
            ->orWhere('id', 43)
            ->orderBy('name')
            ->get();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('filter_name')
                ->label(fn() => !empty($this->selectedUserIds)
                    ? 'Filter (' . count($this->selectedUserIds) . ')'
                    : 'Filter by Name')
                ->icon('heroicon-o-funnel')
                ->color(fn() => !empty($this->selectedUserIds) ? 'success' : 'gray')
                ->form([
                    Select::make('user_ids')
                        ->label('Select Staff')
                        ->multiple()
                        ->options(fn() => User::whereIn('role_id', [4, 5, 6, 7, 8])
                            ->orWhere('id', 43)
                            ->orderBy('name')
                            ->pluck('name', 'id'))
                        ->default(fn() => $this->selectedUserIds)
                        ->searchable()
                        ->preload(),
                ])
                ->action(function (array $data) {
                    $this->selectedUserIds = $data['user_ids'] ?? [];
                }),
            Action::make('view_by_name')
                ->label(fn() => $this->viewMode === 'table' ? 'Calendar View' : 'View by Name')
                ->icon(fn() => $this->viewMode === 'table' ? 'heroicon-o-calendar' : 'heroicon-o-table-cells')
                ->color(fn() => $this->viewMode === 'table' ? 'success' : 'gray')
                ->action(function () {
                    if ($this->viewMode === 'calendar') {
                        $this->buildStaffSummary();
                        $this->viewMode = 'table';
                    } else {
                        $this->viewMode = 'calendar';
                    }
                }),
            Action::make('year_2025')
                ->label('2025')
                ->url(fn() => route('filament.admin.pages.support-calendar', ['year' => 2025]))
                ->color('success'),
            Action::make('year_2026')
                ->label('2026')
                ->url(fn() => route('filament.admin.pages.support-calendar', ['year' => 2026]))
                ->color('info'),
            Action::make('year_2027')
                ->label('2027')
                ->url(fn() => route('filament.admin.pages.support-calendar', ['year' => 2027]))
                ->color('warning'),
            Action::make('toggle_edit')
                ->label(fn() => $this->editMode ? 'Exit Edit Mode' : 'Edit')
                ->action(fn() => $this->toggleEditMode())
                ->visible(fn() => auth()->check() && auth()->user()->role_id === 3)
                ->color(fn() => $this->editMode ? 'danger' : 'primary'),
        ];
    }

    public function toggleEditMode()
    {
        $this->editMode = !$this->editMode;
    }

    public function loadCalendarData()
    {
        $year = $this->selectedYear;
        $startDate = Carbon::createFromDate($year, 1, 1);
        $endDate = Carbon::createFromDate($year, 12, 31);

        // Initialize months structure
        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthName = Carbon::create($year, $m, 1)->format('F');
            $months[$monthName] = [
                'name' => $monthName,
                'weeks' => [],
            ];
        }

        // Get all weekends in the selected year
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            // If it's a Saturday, add it to weekends
            if ($currentDate->dayOfWeek === Carbon::SATURDAY) {
                $saturday = $currentDate->format('Y-m-d');
                $sunday = $currentDate->copy()->addDay()->format('Y-m-d');
                $month = $currentDate->format('F');
                $weekNumber = ceil($currentDate->format('j') / 7);

                if (!isset($months[$month]['weeks']["W{$weekNumber}"])) {
                    $months[$month]['weeks']["W{$weekNumber}"] = [
                        'week_number' => "W{$weekNumber}",
                        'dates' => $currentDate->format('j') . ' & ' . $currentDate->copy()->addDay()->format('j'),
                        'date_start' => $saturday,
                        'date_end' => $sunday,
                        'user_id' => null,
                        'user_name' => 'Unassigned',
                        'status' => null,
                        'record_id' => null,
                        'css_class' => 'unassigned',
                    ];
                }
            }

            $currentDate->addDay();
        }

        // Load assigned staff for each weekend
        $overtimeSchedules = OvertimeSchedule::whereYear('date', $year)->get();

        // Debug the fetched records
        Log::info("Loaded overtime schedules for {$year}", [
            'count' => $overtimeSchedules->count(),
            'records' => $overtimeSchedules->map(function($item) {
                return [
                    'id' => $item->id,
                    'date' => $item->date->format('Y-m-d'),
                    'user_id' => $item->user_id
                ];
            })->toArray()
        ]);

        foreach ($months as $monthName => &$month) {
            foreach ($month['weeks'] as $weekNumber => &$week) {
                // Convert date format to ensure proper comparison
                $dateToFind = $week['date_start'];

                // Find the schedule for this weekend
                $schedule = null;
                foreach ($overtimeSchedules as $s) {
                    if ($s->date->format('Y-m-d') === $dateToFind) {
                        $schedule = $s;
                        break;
                    }
                }

                if ($schedule) {
                    $week['user_id'] = $schedule->user_id;

                    // Get user name from database
                    $user = User::find($schedule->user_id);
                    $week['user_name'] = $user ? $user->name : 'Unknown User';

                    $week['status'] = $schedule->status;
                    $week['record_id'] = $schedule->id;

                    // Define colors using CSS class names
                    if ($schedule->status === 'completed') {
                        $week['css_class'] = 'staff-color-6'; // Green for completed
                    } else {
                        // Assign consistent color based on user ID
                        $colorIndex = $schedule->user_id % 7;
                        $week['css_class'] = "staff-color-{$colorIndex}";
                    }
                }
            }
        }

        $this->months = $months;
    }

    public function buildStaffSummary()
    {
        $year = $this->selectedYear;
        $schedules = OvertimeSchedule::whereYear('date', $year)
            ->with('user')
            ->orderBy('date')
            ->get();

        $grouped = [];
        foreach ($schedules as $schedule) {
            $userId = $schedule->user_id;
            $userName = $schedule->user ? $schedule->user->name : 'Unknown User';

            if (!isset($grouped[$userId])) {
                $grouped[$userId] = [
                    'name' => $userName,
                    'weekends' => [],
                ];
            }

            $sat = Carbon::parse($schedule->date);
            $sun = $sat->copy()->addDay();
            $grouped[$userId]['weekends'][] = $sat->format('j') . '&' . $sun->format('j') . ' ' . $sat->format('F');
        }

        // Sort alphabetically by name
        usort($grouped, fn($a, $b) => strcasecmp($a['name'], $b['name']));

        $this->staffSummary = $grouped;
    }

    public function assignStaff($weekendDate, $userId)
    {
        if (!$this->editMode) {
            Notification::make()
                ->title('Edit mode is disabled')
                ->warning()
                ->send();
            return;
        }

        try {
            // Log the incoming data
            Log::info('Assigning staff', [
                'weekendDate' => $weekendDate,
                'userId' => $userId,
                'editMode' => $this->editMode
            ]);

            // If userId is empty, treat it as unassignment
            if (empty($userId)) {
                // Check if there's an existing schedule to delete
                $existingSchedule = OvertimeSchedule::where('date', $weekendDate)->first();
                if ($existingSchedule) {
                    Log::info("Deleting schedule", ['id' => $existingSchedule->id]);
                    $existingSchedule->delete();

                    Notification::make()
                        ->title('Staff unassigned successfully')
                        ->success()
                        ->send();
                }
            } else {
                // Check if a record already exists for this date
                $schedule = OvertimeSchedule::where('date', $weekendDate)->first();

                $isNew = !$schedule;
                if (!$schedule) {
                    // Create new record
                    $schedule = new OvertimeSchedule();
                    $schedule->date = $weekendDate;
                    Log::info("Creating new schedule for date", ['date' => $weekendDate]);
                } else {
                    Log::info("Updating existing schedule", ['id' => $schedule->id]);
                }

                $schedule->user_id = $userId;
                $schedule->status = $schedule->status ?? 'scheduled';

                // Save and log result
                $saved = $schedule->save();
                Log::info('Save result', [
                    'success' => $saved,
                    'record_id' => $schedule->id,
                    'is_new' => $isNew,
                    'data' => $schedule->toArray()
                ]);

                if ($saved) {
                    Notification::make()
                        ->title('Staff assigned successfully')
                        ->success()
                        ->send();
                } else {
                    Notification::make()
                        ->title('Failed to save assignment')
                        ->danger()
                        ->send();
                }
            }
        } catch (\Exception $e) {
            Log::error("Error assigning staff: " . $e->getMessage());
            Log::error($e->getTraceAsString());

            Notification::make()
                ->title('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }

        // Reload calendar data
        $this->loadCalendarData();
    }
}
