<?php

namespace App\Filament\Pages;

use App\Models\OvertimeSchedule;
use App\Models\OvertimeScheduleDefault;
use App\Models\User;
use App\Models\UserLeave;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Log;

class SupportCalendarWeekday extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Overtime Weekday';
    protected static ?string $navigationGroup = 'Support Information';
    protected static ?string $title = 'Support - Overtime Weekday / Group Chat Reseller';
    protected static ?int $navigationSort = 12;

    protected static string $view = 'filament.pages.support-calendar-weekday';

    public ?string $weekStart = null;
    public bool $editMode = false;
    public $users = [];
    public array $days = [];

    public function mount(): void
    {
        $requested = request()->query('week');
        $this->weekStart = $requested
            ? Carbon::parse($requested)->startOfWeek(Carbon::MONDAY)->toDateString()
            : Carbon::now()->startOfWeek(Carbon::MONDAY)->toDateString();

        $this->users = User::whereIn('role_id', [4, 5, 6, 7, 8])
            ->orWhere('id', 43)
            ->orderBy('name')
            ->get();

        $this->loadWeek();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('prev_week')
                ->label('← Prev Week')
                ->color('gray')
                ->action(fn () => $this->shiftWeek(-1)),

            Action::make('this_week')
                ->label('This Week')
                ->color('info')
                ->action(function () {
                    $this->weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY)->toDateString();
                    $this->loadWeek();
                }),

            Action::make('next_week')
                ->label('Next Week →')
                ->color('gray')
                ->action(fn () => $this->shiftWeek(1)),

            Action::make('toggle_edit')
                ->label(fn () => $this->editMode ? 'Exit Edit Mode' : 'Edit')
                ->color(fn () => $this->editMode ? 'danger' : 'primary')
                ->visible(fn () => $this->canEdit())
                ->action(fn () => $this->editMode = ! $this->editMode),

            Action::make('copy_defaults')
                ->label('Copy to This Week')
                ->icon('heroicon-o-document-duplicate')
                ->color('warning')
                ->visible(fn () => $this->canEdit())
                ->modalHeading(fn () => 'Copy to Week of ' . $this->week_range_label)
                ->modalDescription('Review or edit the default setting below, then confirm to copy it into this week. Existing assignments in this week will be overwritten.')
                ->modalWidth('3xl')
                ->modalSubmitActionLabel('Save & Copy')
                ->fillForm(fn () => $this->getDefaultSettingData())
                ->form(fn () => $this->getCopyDefaultsForm())
                ->action(fn (array $data) => $this->saveDefaultsAndCopy($data)),
        ];
    }

    protected function getCopyDefaultsForm(): array
    {
        return [
            Section::make('Default Setting')
                ->description('Review or edit the default staff assignments before copying.')
                ->schema($this->getDefaultSettingForm())
                ->collapsible()
                ->collapsed(),
        ];
    }

    public function saveDefaultsAndCopy(array $data): void
    {
        if (! $this->canEdit()) {
            Notification::make()->title('You do not have permission to edit')->warning()->send();
            return;
        }

        $this->saveDefaultSetting($data);
        $this->copyDefaultsToViewedWeek();
    }

    protected function getDefaultSettingForm(): array
    {
        $userOptions = collect($this->users)->pluck('name', 'id')->toArray();

        $days = [
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
        ];

        $buildDaySelects = function (string $type) use ($days, $userOptions) {
            $selects = [];
            foreach ($days as $num => $label) {
                $selects[] = Select::make('day_' . $num . '_' . $type)
                    ->label($label)
                    ->options($userOptions)
                    ->searchable()
                    ->placeholder('— Unassigned —');
            }
            return $selects;
        };

        return [
            Fieldset::make('Main')
                ->schema($buildDaySelects('main'))
                ->columns(1),
        ];
    }

    protected function getDefaultSettingData(): array
    {
        $data = [];
        foreach (OvertimeScheduleDefault::all() as $row) {
            $data['day_' . $row->day_of_week . '_' . $row->type] = $row->user_id;
        }
        return $data;
    }

    public function saveDefaultSetting(array $data): void
    {
        if (! $this->canEdit()) {
            Notification::make()->title('You do not have permission to edit')->warning()->send();
            return;
        }

        try {
            for ($day = 1; $day <= 5; $day++) {
                $userId = $data['day_' . $day . '_main'] ?? null;
                OvertimeScheduleDefault::updateOrCreate(
                    ['day_of_week' => $day, 'type' => 'main'],
                    ['user_id' => $userId ?: null]
                );
            }
        } catch (\Exception $e) {
            Log::error('OT Weekday default setting save failed: ' . $e->getMessage());
            Notification::make()->title('Error: ' . $e->getMessage())->danger()->send();
        }
    }

    public function copyDefaultsToViewedWeek(): void
    {
        if (! $this->canEdit()) {
            Notification::make()->title('You do not have permission to edit')->warning()->send();
            return;
        }

        try {
            $defaults = OvertimeScheduleDefault::where('type', 'main')
                ->whereNotNull('user_id')
                ->get();

            if ($defaults->isEmpty()) {
                Notification::make()
                    ->title('No defaults configured')
                    ->body('Set up the default staff under "Default Setting" first.')
                    ->warning()
                    ->send();
                return;
            }

            $weekStart = Carbon::parse($this->weekStart);
            $copied = 0;

            foreach ($defaults as $default) {
                $targetDate = $weekStart->copy()->addDays($default->day_of_week - 1)->toDateString();
                $schedule = OvertimeSchedule::firstOrNew(['date' => $targetDate, 'type' => $default->type]);
                $schedule->user_id = $default->user_id;
                $schedule->status = $schedule->status ?? 'scheduled';
                $schedule->save();
                $copied++;
            }

            Notification::make()
                ->title('Defaults copied into this week')
                ->body("{$copied} assignment(s) applied.")
                ->success()
                ->send();
        } catch (\Exception $e) {
            Log::error('OT Weekday copy-defaults failed: ' . $e->getMessage());
            Notification::make()->title('Error: ' . $e->getMessage())->danger()->send();
        }

        $this->loadWeek();
    }

    public function canEdit(): bool
    {
        $user = auth()->user();
        return (int) ($user?->role_id ?? 0) === 3 || (int) ($user?->id ?? 0) === 41;
    }

    public function shiftWeek(int $delta): void
    {
        $this->weekStart = Carbon::parse($this->weekStart)->addWeeks($delta)->toDateString();
        $this->loadWeek();
    }

    public function loadWeek(): void
    {
        $start = Carbon::parse($this->weekStart);
        $end = $start->copy()->addDays(4); // Mon..Fri

        $schedules = OvertimeSchedule::whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->with('user:id,name')
            ->get()
            ->groupBy(fn ($s) => $s->date->format('Y-m-d'));

        // Look up leaves for all scheduled users in this week range.
        $scheduledUserIds = $schedules->flatten(1)->pluck('user_id')->filter()->unique()->values()->all();
        $leaves = collect();
        if (!empty($scheduledUserIds)) {
            $leaves = UserLeave::whereIn('user_ID', $scheduledUserIds)
                ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                ->whereIn('status', ['Approved', 'Pending'])
                ->get()
                ->groupBy(fn ($l) => $l->user_ID . '|' . Carbon::parse($l->date)->toDateString());
        }

        $buildSlot = function ($schedule, string $key) use ($leaves) {
            $onLeave = false;
            $leaveType = null;
            $leaveSession = null;
            if ($schedule && $schedule->user_id) {
                $userLeaves = $leaves->get($schedule->user_id . '|' . $key, collect());
                if ($userLeaves->isNotEmpty()) {
                    $onLeave = true;
                    $leaveType = $userLeaves->first()->leave_type ?? 'Leave';
                    $sessions = $userLeaves->pluck('session')->map(fn ($s) => strtolower((string) $s))->unique()->values()->all();
                    if (in_array('full', $sessions, true) || (in_array('am', $sessions, true) && in_array('pm', $sessions, true))) {
                        $leaveSession = 'full';
                    } elseif (in_array('am', $sessions, true)) {
                        $leaveSession = 'am';
                    } elseif (in_array('pm', $sessions, true)) {
                        $leaveSession = 'pm';
                    }
                }
            }

            return [
                'user_id' => $schedule?->user_id,
                'user_name' => $schedule?->user?->name ?? 'Unassigned',
                'status' => $schedule?->status,
                'record_id' => $schedule?->id,
                'css_class' => $schedule ? ($schedule->status === 'completed' ? 'completed' : 'assigned') : 'unassigned',
                'on_leave' => $onLeave,
                'leave_type' => $leaveType,
                'leave_session' => $leaveSession,
            ];
        };

        $days = [];
        for ($i = 0; $i < 5; $i++) {
            $d = $start->copy()->addDays($i);
            $key = $d->format('Y-m-d');
            $daySchedules = $schedules->get($key, collect());

            $main = $daySchedules->firstWhere('type', 'main') ?? $daySchedules->firstWhere('type', null);
            $backup = $daySchedules->firstWhere('type', 'backup');

            $days[] = [
                'date' => $key,
                'day_label' => strtoupper($d->format('l')),
                'day_num' => $d->format('j M'),
                'is_today' => $d->isToday(),
                'main' => $buildSlot($main, $key),
                'backup' => $buildSlot($backup, $key),
            ];
        }

        $this->days = $days;
    }

    public function assignStaff(string $date, $userId, string $type = 'main'): void
    {
        if (! $this->canEdit() || ! $this->editMode) {
            Notification::make()->title('Edit mode is disabled')->warning()->send();
            return;
        }

        $type = in_array($type, ['main', 'backup'], true) ? $type : 'main';

        try {
            if (empty($userId)) {
                $existing = OvertimeSchedule::where('date', $date)->where('type', $type)->first();
                if ($existing) {
                    $existing->delete();
                    Notification::make()->title('Staff unassigned')->success()->send();
                }
            } else {
                $schedule = OvertimeSchedule::firstOrNew(['date' => $date, 'type' => $type]);
                $schedule->user_id = $userId;
                $schedule->status = $schedule->status ?? 'scheduled';
                $schedule->save();
                Notification::make()->title('Staff assigned')->success()->send();
            }
        } catch (\Exception $e) {
            Log::error('OT Weekday assign failed: ' . $e->getMessage());
            Notification::make()->title('Error: ' . $e->getMessage())->danger()->send();
        }

        $this->loadWeek();
    }

    public function getWeekRangeLabelProperty(): string
    {
        $start = Carbon::parse($this->weekStart);
        $end = $start->copy()->addDays(4);
        return $start->format('d M Y') . ' – ' . $end->format('d M Y');
    }
}
