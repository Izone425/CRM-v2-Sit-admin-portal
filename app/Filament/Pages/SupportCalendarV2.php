<?php

namespace App\Filament\Pages;

use App\Models\PublicHoliday;
use App\Models\SupportAppointment;
use App\Models\SupportGroup;
use App\Models\User;
use App\Models\UserLeave;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class SupportCalendarV2 extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Support Calendar V2';
    protected static ?string $navigationGroup = 'Support Information';
    protected static ?string $title = 'Support - Daily Task';
    protected static ?int $navigationSort = 11;

    protected static string $view = 'filament.pages.support-calendar-v2';

    public ?int $selectedGroupId = null;
    public ?string $weekStart = null;

    public bool $showAssignModal = false;
    public array $assignForm = [
        'user_id' => null,
        'user_name' => '',
        'date' => '',
        'type' => '',
    ];

    // Edit Mode — tick existing appointments for bulk change/delete,
    // or tick empty "Available" cells for bulk create.
    public bool $editMode = false;
    public array $selectedAppointmentIds = [];
    public array $selectedEmptySlots = []; // "userId:date" entries
    public bool $showBulkTypeModal = false;
    public ?string $bulkNewType = null;

    public const JOB_TYPES = [
        'DATA MIGRATION SESSION' => 'Data Migration Session',
        'MOCKUP TRAINING' => 'Mockup Training',
        'SYSTEM SETTING SESSION' => 'System Setting Session',
        'WEEKLY FOLLOW UP SESSION' => 'Weekly Follow Up Session',
        'QC TASK' => 'QC Task',
        'QC MEETING' => 'QC Meeting',
        'BACKUP IMPLEMENTER' => 'Backup Implementer',
        'BACKUP SUPPORT' => 'Backup Support',
        'HRDF CERTIFIED COURSE' => 'HRDF Certified Course',
        'ONLINE TRAINING SESSION' => 'Online Training Session',
        'TRAINER TASK' => 'Trainer Task',
    ];

    // bg, border-left, text
    public const JOB_TYPE_COLORS = [
        'DATA MIGRATION SESSION'   => ['#EDE9FE', '#7C3AED', '#5B21B6'],
        'MOCKUP TRAINING'          => ['#FEF3C7', '#D97706', '#92400E'],
        'SYSTEM SETTING SESSION'   => ['#DBEAFE', '#2563EB', '#1E40AF'],
        'WEEKLY FOLLOW UP SESSION' => ['#CCFBF1', '#0D9488', '#115E59'],
        'QC TASK'                  => ['#FCE7F3', '#DB2777', '#9D174D'],
        'QC MEETING'               => ['#FFE4E6', '#E11D48', '#9F1239'],
        'BACKUP IMPLEMENTER'       => ['#E0E7FF', '#4F46E5', '#3730A3'],
        'BACKUP SUPPORT'           => ['#D1FAE5', '#059669', '#065F46'],
        'HRDF CERTIFIED COURSE'    => ['#FFEDD5', '#EA580C', '#9A3412'],
        'ONLINE TRAINING SESSION'  => ['#CFFAFE', '#0891B2', '#155E75'],
        'TRAINER TASK'             => ['#ECFCCB', '#65A30D', '#3F6212'],
    ];

    public static function jobTypeColors(string $type): array
    {
        return self::JOB_TYPE_COLORS[strtoupper($type)] ?? ['#F3F4F6', '#6B7280', '#374151'];
    }

    public static function avatarColor(string $name): array
    {
        $palette = [
            ['#DBEAFE', '#1D4ED8'],
            ['#FEF3C7', '#B45309'],
            ['#DCFCE7', '#15803D'],
            ['#FCE7F3', '#BE185D'],
            ['#EDE9FE', '#6D28D9'],
            ['#FFE4E6', '#BE123C'],
            ['#CFFAFE', '#0E7490'],
            ['#FFEDD5', '#C2410C'],
        ];
        $idx = abs(crc32($name)) % count($palette);
        return $palette[$idx];
    }

    public function mount(): void
    {
        // 0 = "All Groups" pseudo-group; default to it when any groups exist
        $this->selectedGroupId = SupportGroup::exists() ? 0 : null;
        $this->weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY)->toDateString();
    }

    public function getGroupsProperty()
    {
        return SupportGroup::with('users:id,name')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function getSelectedGroupProperty(): ?SupportGroup
    {
        if (! $this->selectedGroupId) return null;
        return $this->groups->firstWhere('id', $this->selectedGroupId);
    }

    public function getSelectedUsersProperty()
    {
        if ($this->selectedGroupId === 0) {
            // Preserve group order; within a group, sort by name; drop duplicates across groups
            $seen = [];
            $ordered = collect();
            foreach ($this->groups as $g) {
                foreach ($g->users->sortBy('name') as $u) {
                    if (isset($seen[$u->id])) continue;
                    $seen[$u->id] = true;
                    $u->setAttribute('group_name', $g->name);
                    $u->setAttribute('group_id', $g->id);
                    $ordered->push($u);
                }
            }
            return $ordered;
        }

        $group = $this->selectedGroup;
        if (! $group) return collect();
        return $group->users->sortBy('name')->each(function ($u) use ($group) {
            $u->setAttribute('group_name', $group->name);
            $u->setAttribute('group_id', $group->id);
        })->values();
    }

    public function getSelectedDisplayNameProperty(): string
    {
        if ($this->selectedGroupId === 0) return 'All Groups';
        return $this->selectedGroup?->name ?? '';
    }

    public function selectGroup(int $groupId): void
    {
        $this->selectedGroupId = $groupId;
    }

    public function prevWeek(): void
    {
        $this->weekStart = Carbon::parse($this->weekStart)->subWeek()->toDateString();
    }

    public function nextWeek(): void
    {
        $this->weekStart = Carbon::parse($this->weekStart)->addWeek()->toDateString();
    }

    public function getWeekDaysProperty(): array
    {
        $days = [];
        $start = Carbon::parse($this->weekStart);
        for ($i = 0; $i < 5; $i++) {
            $d = $start->copy()->addDays($i);
            $days[] = [
                'date' => $d->toDateString(),
                'day_label' => strtoupper($d->format('D')),
                'day_num' => $d->format('j M'),
                'is_today' => $d->isToday(),
            ];
        }
        return $days;
    }

    public function getHolidaysProperty()
    {
        $dates = collect($this->weekDays)->pluck('date')->all();
        return PublicHoliday::whereIn('date', $dates)->get()->keyBy(fn ($h) => Carbon::parse($h->date)->toDateString());
    }

    public function getGridDataProperty(): array
    {
        $users = $this->selectedUsers;
        if ($users->isEmpty()) return [];

        $userIds = $users->pluck('id')->all();
        $weekDates = collect($this->weekDays)->pluck('date')->all();
        $weekStart = $weekDates[0];
        $weekEnd = end($weekDates);

        $leaves = UserLeave::whereIn('user_ID', $userIds)
            ->whereBetween('date', [$weekStart, $weekEnd])
            ->get()
            ->groupBy(fn ($l) => $l->user_ID . '|' . Carbon::parse($l->date)->toDateString());

        $appointments = SupportAppointment::whereIn('user_id', $userIds)
            ->whereBetween('date', [$weekStart, $weekEnd])
            ->where('status', '!=', 'Cancelled')
            ->get()
            ->groupBy(fn ($a) => $a->user_id . '|' . Carbon::parse($a->date)->toDateString());

        $rows = [];
        foreach ($users as $user) {
            $row = ['user' => $user, 'days' => []];
            foreach ($this->weekDays as $day) {
                $date = $day['date'];
                $leaveKey = $user->id . '|' . $date;
                $apptKey = $user->id . '|' . $date;

                $dayLeaves = $leaves->get($leaveKey, collect());
                $dayAppts = $appointments->get($apptKey, collect())->values();

                $hasAm = $dayLeaves->contains(fn ($l) => strtolower($l->session ?? '') === 'am');
                $hasPm = $dayLeaves->contains(fn ($l) => strtolower($l->session ?? '') === 'pm');
                $hasFull = $dayLeaves->contains(fn ($l) => strtolower($l->session ?? '') === 'full');
                $isFullLeave = $hasFull || ($hasAm && $hasPm);

                $availableHalf = null;
                if (! $isFullLeave) {
                    if ($hasAm) $availableHalf = 'pm';
                    elseif ($hasPm) $availableHalf = 'am';
                }

                $row['days'][$date] = [
                    'leaves' => $dayLeaves,
                    'is_full_leave' => $isFullLeave,
                    'available_half' => $availableHalf,
                    'appointments' => $dayAppts,
                    'is_holiday' => isset($this->holidays[$date]),
                    'holiday' => $this->holidays[$date] ?? null,
                ];
            }
            $rows[] = $row;
        }

        return $rows;
    }

    public function openAssignModal(int $userId, string $date): void
    {
        if (! $this->canEdit()) return;

        $userName = User::where('id', $userId)->value('name') ?? '';

        $this->assignForm = [
            'user_id' => $userId,
            'user_name' => $userName,
            'date' => $date,
            'type' => '',
        ];
        $this->showAssignModal = true;
    }

    public function canEdit(): bool
    {
        $user = auth()->user();
        return (int) ($user?->role_id ?? 0) === 3 || (int) ($user?->id ?? 0) === 41;
    }

    public function closeAssignModal(): void
    {
        $this->showAssignModal = false;
    }

    // ----- Edit Mode (bulk select + change type + delete) -----

    public function toggleEditMode(): void
    {
        if (! $this->canEdit()) return;
        $this->editMode = ! $this->editMode;
        if (! $this->editMode) {
            $this->selectedAppointmentIds = [];
            $this->selectedEmptySlots = [];
        }
    }

    public function toggleAppointmentSelection(int $id): void
    {
        if (! $this->canEdit() || ! $this->editMode) return;
        if (in_array($id, $this->selectedAppointmentIds, true)) {
            $this->selectedAppointmentIds = array_values(array_filter($this->selectedAppointmentIds, fn ($i) => $i !== $id));
        } else {
            $this->selectedAppointmentIds[] = $id;
        }
    }

    public function toggleEmptySlot(int $userId, string $date): void
    {
        if (! $this->canEdit() || ! $this->editMode) return;
        $key = $userId . ':' . $date;
        if (in_array($key, $this->selectedEmptySlots, true)) {
            $this->selectedEmptySlots = array_values(array_filter($this->selectedEmptySlots, fn ($k) => $k !== $key));
        } else {
            $this->selectedEmptySlots[] = $key;
        }
    }

    public function clearSelection(): void
    {
        $this->selectedAppointmentIds = [];
        $this->selectedEmptySlots = [];
    }

    public function openBulkTypeModal(): void
    {
        if (! $this->canEdit() || (empty($this->selectedAppointmentIds) && empty($this->selectedEmptySlots))) return;
        $this->bulkNewType = '';
        $this->showBulkTypeModal = true;
    }

    public function closeBulkTypeModal(): void
    {
        $this->showBulkTypeModal = false;
        $this->bulkNewType = null;
    }

    public function applyBulkType(): void
    {
        if (! $this->canEdit() || empty($this->bulkNewType)) return;

        $updated = 0;
        $created = 0;

        if (! empty($this->selectedAppointmentIds)) {
            $updated = SupportAppointment::whereIn('id', $this->selectedAppointmentIds)->update([
                'type' => $this->bulkNewType,
                'causer_id' => auth()->id(),
            ]);
        }

        foreach ($this->selectedEmptySlots as $key) {
            [$userId, $date] = explode(':', $key, 2);
            SupportAppointment::create([
                'support_group_id' => $this->selectedGroupId ?: null,
                'user_id' => (int) $userId,
                'date' => $date,
                'type' => $this->bulkNewType,
                'status' => 'New',
                'causer_id' => auth()->id(),
            ]);
            $created++;
        }

        $parts = [];
        if ($updated > 0) $parts[] = "updated {$updated}";
        if ($created > 0) $parts[] = "created {$created}";
        Notification::make()->title('Bulk apply done')->body(ucfirst(implode(', ', $parts) ?: 'nothing changed'))->success()->send();

        $this->closeBulkTypeModal();
        $this->selectedAppointmentIds = [];
        $this->selectedEmptySlots = [];
    }

    public function bulkDeleteSelected(): void
    {
        if (! $this->canEdit() || empty($this->selectedAppointmentIds)) return;

        $count = SupportAppointment::whereIn('id', $this->selectedAppointmentIds)->delete();

        Notification::make()->title("Deleted {$count} job(s)")->success()->send();
        $this->selectedAppointmentIds = [];
    }

    public bool $showDeleteModal = false;
    public array $deleteTarget = [
        'id' => null,
        'type' => '',
        'user_name' => '',
        'date' => '',
    ];

    public function openDeleteModal(int $id): void
    {
        if (! $this->canEdit()) return;

        $appt = SupportAppointment::with('user')->find($id);
        if (! $appt) return;

        $this->deleteTarget = [
            'id' => $appt->id,
            'type' => $appt->type,
            'user_name' => $appt->user?->name ?? '',
            'date' => Carbon::parse($appt->date)->toDateString(),
        ];
        $this->showDeleteModal = true;
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
    }

    public function confirmDeleteAppointment(): void
    {
        if (! $this->canEdit()) return;

        $id = $this->deleteTarget['id'] ?? null;
        if (! $id) return;

        $appt = SupportAppointment::find($id);
        if (! $appt) {
            $this->showDeleteModal = false;
            return;
        }

        $type = $appt->type;
        $appt->delete();

        Notification::make()
            ->title('Task deleted')
            ->body("{$type} removed.")
            ->success()
            ->send();

        $this->showDeleteModal = false;
    }

    public function submitAssignment(): void
    {
        if (! $this->canEdit()) return;

        $data = $this->assignForm;

        if (empty($data['type'])) {
            Notification::make()->title('Job type is required')->danger()->send();
            return;
        }

        SupportAppointment::create([
            'support_group_id' => $this->selectedGroupId ?: null,
            'user_id' => $data['user_id'],
            'date' => $data['date'],
            'type' => $data['type'],
            'status' => 'New',
            'causer_id' => auth()->id(),
        ]);

        Notification::make()
            ->title('Job assigned')
            ->body("{$data['type']} assigned to {$data['user_name']} on {$data['date']}")
            ->success()
            ->send();

        $this->showAssignModal = false;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createGroup')
                ->label('New Group')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->visible(fn () => $this->canEdit())
                ->form([
                    TextInput::make('name')
                        ->label('Group Name')
                        ->required()
                        ->maxLength(100),
                    TextInput::make('sort_order')
                        ->label('Sort Order')
                        ->numeric()
                        ->default(0)
                        ->helperText('Lower numbers appear first.'),
                    Select::make('user_ids')
                        ->label('Members')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->options(fn () => User::orderBy('name')->pluck('name', 'id'))
                        ->required(),
                ])
                ->action(function (array $data) {
                    $group = SupportGroup::create([
                        'name' => $data['name'],
                        'sort_order' => (int) ($data['sort_order'] ?? 0),
                        'created_by' => auth()->id(),
                    ]);
                    $group->users()->sync($data['user_ids']);
                    $this->selectedGroupId = $group->id;

                    Notification::make()->title("Group \"{$group->name}\" created")->success()->send();
                }),

            Action::make('editGroup')
                ->label('Edit Group')
                ->icon('heroicon-o-pencil-square')
                ->color('warning')
                ->visible(fn () => $this->canEdit() && $this->selectedGroup !== null)
                ->fillForm(fn () => [
                    'name' => $this->selectedGroup?->name,
                    'sort_order' => $this->selectedGroup?->sort_order ?? 0,
                    'user_ids' => $this->selectedGroup?->users->pluck('id')->toArray() ?? [],
                ])
                ->form([
                    TextInput::make('name')
                        ->label('Group Name')
                        ->required()
                        ->maxLength(100),
                    TextInput::make('sort_order')
                        ->label('Sort Order')
                        ->numeric()
                        ->default(0)
                        ->helperText('Lower numbers appear first.'),
                    Select::make('user_ids')
                        ->label('Members')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->options(fn () => User::orderBy('name')->pluck('name', 'id'))
                        ->required(),
                ])
                ->action(function (array $data) {
                    $group = $this->selectedGroup;
                    if (! $group) return;

                    $group->update([
                        'name' => $data['name'],
                        'sort_order' => (int) ($data['sort_order'] ?? 0),
                    ]);
                    $group->users()->sync($data['user_ids']);

                    Notification::make()->title("Group updated")->success()->send();
                }),

            Action::make('toggleEditMode')
                ->label(fn () => $this->editMode ? 'Exit Edit Mode' : 'Edit Mode')
                ->icon(fn () => $this->editMode ? 'heroicon-o-check' : 'heroicon-o-pencil-square')
                ->color(fn () => $this->editMode ? 'warning' : 'primary')
                ->visible(fn () => $this->canEdit())
                ->action(fn () => $this->toggleEditMode()),

            Action::make('deleteGroup')
                ->label('Delete Group')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn () => $this->canEdit() && $this->selectedGroup !== null)
                ->action(function () {
                    $group = $this->selectedGroup;
                    if (! $group) return;

                    $name = $group->name;
                    $group->delete();
                    $this->selectedGroupId = SupportGroup::orderBy('name')->value('id');

                    Notification::make()->title("Group \"{$name}\" deleted")->success()->send();
                }),
        ];
    }
}
