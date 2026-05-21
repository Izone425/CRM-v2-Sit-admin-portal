<x-filament-panels::page>
    <style>
        .scv2-combo-btn {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #D1D5DB;
            border-radius: 8px;
            font-size: 13px;
            color: #111827;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 6px;
            cursor: pointer;
            text-align: left;
        }
        .scv2-combo-btn .placeholder { color: #9CA3AF; }
        .scv2-combo-pop {
            position: absolute;
            top: calc(100% + 4px);
            left: 0;
            right: 0;
            background: #fff;
            border: 1px solid #E5E7EB;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.10);
            z-index: 60;
            overflow: hidden;
        }
        .scv2-combo-search {
            width: 100%;
            padding: 8px 10px;
            border: 0;
            border-bottom: 1px solid #F3F4F6;
            font-size: 13px;
            background: #F9FAFB;
            color: #111827;
            outline: none;
        }
        .scv2-combo-list { max-height: 240px; overflow-y: auto; }
        .scv2-combo-item {
            display: block;
            width: 100%;
            text-align: left;
            padding: 10px 14px;
            border: 0;
            background: #fff;
            font-size: 13px;
            color: #111827;
            cursor: pointer;
            border-bottom: 1px solid #F3F4F6;
        }
        .scv2-combo-item:hover { background: #F3F4F6; }
        .scv2-combo-item.clear { color: #9CA3AF; }
        .scv2-combo-item.selected { background: #EEF2FF; color: #4F46E5; font-weight: 600; }

        .scv2-wrap {
            border: 1px solid #E5E7EB;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04);
            background: #fff;
        }
        .scv2-header {
            display: grid;
            grid-template-columns: 1fr repeat(5, 0.8fr);
            gap: 1px;
            background: #E5E7EB;
        }
        .scv2-body {
            display: grid;
            grid-template-columns: 1fr repeat(5, 0.8fr);
            gap: 1px;
            background: #E5E7EB;
        }
        .scv2-cell {
            background: #fff;
            padding: 10px;
            min-height: 80px;
            transition: background 0.15s;
        }
        .scv2-cell.is-header {
            background: linear-gradient(180deg, #FAFBFC 0%, #F3F4F6 100%);
            font-weight: 700; color: #111827; text-align: center; padding: 14px 10px;
        }
        .scv2-cell.is-header.is-today {
            background: linear-gradient(180deg, #EEF2FF 0%, #E0E7FF 100%);
            box-shadow: inset 0 -3px 0 #4F46E5;
        }
        .scv2-cell.is-today { background: #F5F7FF; }
        .scv2-row:hover .scv2-cell { background: #FAFAFB; }
        .scv2-row:hover .scv2-cell.is-today { background: #EEF2FF; }
        .scv2-row:hover .scv2-name-cell { background: #F3F4F6; }
        .scv2-name-cell {
            background: #F9FAFB; padding: 12px 14px; font-weight: 600; color: #111827;
            display: flex; align-items: center; gap: 10px;
        }
        .scv2-avatar {
            width: 32px; height: 32px; border-radius: 50%; flex-shrink: 0;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 12px; font-weight: 700; letter-spacing: 0.02em;
        }
        .scv2-leave-full { background: #FEE2E2; color: #991B1B; padding: 10px; border-radius: 6px; text-align: center; font-weight: 700; font-size: 12px; }
        .scv2-leave-half { background: #FEE2E2; color: #991B1B; padding: 6px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; }
        .scv2-holiday { background: #9CA3AF; color: #fff; padding: 10px; border-radius: 6px; text-align: center; font-weight: 700; font-size: 12px; }
        .scv2-task-card { background: #FEF9C3; border-left: 4px solid #CA8A04; color: #92400E; padding: 6px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; margin-bottom: 4px; }
        .scv2-task-card.is-red { background: #FEE2E2; border-left-color: #DC2626; color: #991B1B; }
        .scv2-available {
            min-height: 40px;
            cursor: pointer;
            background: #DCFCE7;
            border-left: 4px solid #16A34A;
            border-radius: 4px;
            padding: 8px 10px;
            color: #166534;
            font-size: 12px;
            font-weight: 700;
        }
        .scv2-available:hover { background: #BBF7D0; }
        .scv2-group-divider {
            grid-column: 1 / -1;
            background: linear-gradient(90deg, #4F46E5 0%, #6366F1 100%);
            color: #fff;
            padding: 10px 16px;
            font-weight: 700;
            font-size: 13px;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
    </style>

    @if ($this->groups->isEmpty())
        <div style="background: #fff; border: 1px dashed #E5E7EB; border-radius: 12px; padding: 48px 24px; text-align: center; color: #6B7280;">
            <div style="font-size: 16px; font-weight: 600; color: #111827; margin-bottom: 8px;">No groups yet</div>
            <div style="font-size: 13px;">Click <strong>New Group</strong> to create your first group and add members.</div>
        </div>
    @else
        {{-- Group tabs --}}
        <div style="display: flex; flex-wrap: wrap; gap: 8px; padding: 12px; background: #F9FAFB; border-radius: 12px; border: 1px solid #E5E7EB;">
            @php $allActive = $selectedGroupId === 0; @endphp
            @php $allCount = $this->groups->flatMap->users->unique('id')->count(); @endphp
            <button type="button" wire:click="selectGroup(0)"
                    style="padding: 8px 14px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer;
                           background: {{ $allActive ? '#4F46E5' : '#fff' }};
                           color: {{ $allActive ? '#fff' : '#374151' }};
                           border: 1px solid {{ $allActive ? '#4F46E5' : '#E5E7EB' }};">
                All Groups
                <span style="margin-left: 6px; opacity: 0.8; font-weight: 500;">({{ $allCount }})</span>
            </button>
            @foreach ($this->groups as $group)
                @php $isActive = $selectedGroupId === $group->id; @endphp
                <button type="button" wire:click="selectGroup({{ $group->id }})"
                        style="padding: 8px 14px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer;
                               background: {{ $isActive ? '#4F46E5' : '#fff' }};
                               color: {{ $isActive ? '#fff' : '#374151' }};
                               border: 1px solid {{ $isActive ? '#4F46E5' : '#E5E7EB' }};">
                    {{ $group->name }}
                    <span style="margin-left: 6px; opacity: 0.8; font-weight: 500;">(#{{ $group->sort_order }})</span>
                </button>
            @endforeach
        </div>

        @if ($selectedGroupId !== null && $this->selectedUsers->isNotEmpty())
            {{-- Edit Mode selection toolbar --}}
            @if ($editMode)
                @php
                    $jobCount = count($selectedAppointmentIds);
                    $slotCount = count($selectedEmptySlots);
                    $hasSelection = $jobCount + $slotCount > 0;
                @endphp
                <div style="margin-top: 12px; padding: 10px 16px; background: #FEF3C7; border: 1px solid #FCD34D; border-radius: 12px; display: flex; align-items: center; gap: 12px;">
                    <div style="font-size: 13px; font-weight: 700; color: #92400E;">Edit Mode</div>
                    <div style="font-size: 12px; color: #78350F; font-weight: 600;">
                        {{ $jobCount }} job(s){{ $slotCount > 0 ? ', ' . $slotCount . ' empty slot(s)' : '' }} selected
                    </div>
                    <div style="flex: 1;"></div>
                    @if ($hasSelection)
                        <button type="button" wire:click="openBulkTypeModal"
                                style="padding: 6px 12px; background: #2563EB; color: #fff; border: 0; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer;">
                            @if ($jobCount > 0 && $slotCount > 0)
                                Apply Type
                            @elseif ($slotCount > 0)
                                Create Jobs
                            @else
                                Change Type
                            @endif
                        </button>
                        @if ($jobCount > 0)
                            <button type="button" wire:click="bulkDeleteSelected"
                                    wire:confirm="Delete {{ $jobCount }} selected job(s)?"
                                    style="padding: 6px 12px; background: #DC2626; color: #fff; border: 0; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer;">
                                Delete Selected
                            </button>
                        @endif
                        <button type="button" wire:click="clearSelection"
                                style="padding: 6px 12px; background: #fff; color: #6B7280; border: 1px solid #D1D5DB; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer;">
                            Clear
                        </button>
                    @endif
                </div>
            @endif

            {{-- Week navigation --}}
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; background: #fff; border: 1px solid #E5E7EB; border-radius: 12px;">
                <button wire:click="prevWeek"
                        style="padding: 8px 14px; background: #F3F4F6; border: 1px solid #E5E7EB; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer;">← Prev Week</button>
                <div style="font-size: 14px; font-weight: 700; color: #111827;">
                    Week of {{ \Carbon\Carbon::parse($weekStart)->format('d M Y') }}
                </div>
                <button wire:click="nextWeek"
                        style="padding: 8px 14px; background: #F3F4F6; border: 1px solid #E5E7EB; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer;">Next Week →</button>
            </div>

            {{-- Calendar grid (day-by-day) --}}
            <div class="scv2-wrap">
                <div class="scv2-header">
                    <div class="scv2-cell is-header" style="text-align: left;">Member</div>
                    @foreach ($this->weekDays as $day)
                        <div class="scv2-cell is-header {{ $day['is_today'] ? 'is-today' : '' }}">
                            <div style="font-size: 11px; color: #6B7280; letter-spacing: 0.06em; font-weight: 700;">{{ $day['day_label'] }}</div>
                            <div style="font-size: 18px; color: #111827; margin-top: 2px;">{{ $day['day_num'] }}</div>
                            @if ($day['is_today'])
                                <div style="font-size: 9px; color: #4F46E5; margin-top: 2px; font-weight: 700; letter-spacing: 0.08em;">TODAY</div>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="scv2-body">
                    @php $lastGroupId = null; @endphp
                    @foreach ($this->gridData as $row)
                        @if ($selectedGroupId === 0 && $row['user']->group_id !== $lastGroupId)
                            <div class="scv2-group-divider">{{ $row['user']->group_name }}</div>
                            @php $lastGroupId = $row['user']->group_id; @endphp
                        @endif
                        @php
                            [$avatarBg, $avatarFg] = \App\Filament\Pages\SupportCalendarV2::avatarColor($row['user']->name);
                            $initials = collect(explode(' ', trim($row['user']->name)))
                                ->filter()->take(2)
                                ->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))
                                ->implode('');
                        @endphp
                        <div class="scv2-row" style="display: contents;">
                            <div class="scv2-cell scv2-name-cell">
                                <span class="scv2-avatar" style="background: {{ $avatarBg }}; color: {{ $avatarFg }};">{{ $initials }}</span>
                                <span>{{ $row['user']->name }}</span>
                            </div>
                            @foreach ($this->weekDays as $day)
                                @php $cell = $row['days'][$day['date']] ?? null; @endphp
                                <div class="scv2-cell {{ $day['is_today'] ? 'is-today' : '' }}">
                                    @if ($cell['is_holiday'])
                                        <div class="scv2-holiday">
                                            Public Holiday
                                            <div style="font-size: 10px; font-weight: 500; opacity: 0.9; margin-top: 2px;">{{ $cell['holiday']->name ?? '' }}</div>
                                        </div>
                                    @elseif ($cell['is_full_leave'])
                                        @php $firstLeave = $cell['leaves']->first(); @endphp
                                        <div class="scv2-leave-full">
                                            On Leave
                                            <div style="font-size: 10px; font-weight: 500; opacity: 0.9; margin-top: 2px;">{{ $firstLeave->leave_type ?? '' }}</div>
                                        </div>
                                    @else
                                        @foreach ($cell['leaves'] as $l)
                                            <div class="scv2-leave-half">
                                                {{ strtoupper($l->session) }} Leave — {{ $l->leave_type }}
                                            </div>
                                        @endforeach

                                        @foreach ($cell['appointments'] as $a)
                                            @php $isSelected = in_array($a->id, $selectedAppointmentIds); @endphp
                                            <div class="scv2-task-card"
                                                 style="position: relative; {{ $editMode ? 'cursor: pointer; padding-right: 28px;' : '' }} {{ $isSelected ? 'outline: 2px solid #2563EB; outline-offset: 2px;' : '' }}"
                                                 @if ($editMode)
                                                    wire:click="toggleAppointmentSelection({{ $a->id }})"
                                                 @elseif ($this->canEdit())
                                                    title="Right-click to delete"
                                                    oncontextmenu="event.preventDefault(); Livewire.find('{{ $this->getId() }}').call('openDeleteModal', {{ $a->id }}); return false;"
                                                 @endif>
                                                {{ $a->type }}
                                                @if ($editMode)
                                                    <span style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); display: inline-flex; align-items: center; justify-content: center; width: 16px; height: 16px; border: 2px solid {{ $isSelected ? '#2563EB' : '#9CA3AF' }}; background: {{ $isSelected ? '#2563EB' : '#fff' }}; border-radius: 3px;">
                                                        @if ($isSelected)
                                                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="4"><polyline points="20 6 9 17 4 12"/></svg>
                                                        @endif
                                                    </span>
                                                @endif
                                            </div>
                                        @endforeach

                                        @if ($cell['appointments']->isEmpty())
                                            @php $leaveBlocks = $cell['leaves']->isNotEmpty(); @endphp
                                            @if ($this->canEdit() && ! $leaveBlocks)
                                                @php
                                                    $slotKey = $row['user']->id . ':' . $day['date'];
                                                    $isSlotSelected = in_array($slotKey, $selectedEmptySlots);
                                                @endphp
                                                <div class="scv2-available"
                                                     style="position: relative; {{ $editMode ? 'padding-right: 28px;' : '' }} {{ $isSlotSelected ? 'outline: 2px solid #2563EB; outline-offset: 2px; background: #DBEAFE;' : '' }}"
                                                     @if ($editMode)
                                                        wire:click="toggleEmptySlot({{ $row['user']->id }}, '{{ $day['date'] }}')"
                                                     @else
                                                        wire:click="openAssignModal({{ $row['user']->id }}, '{{ $day['date'] }}')"
                                                        oncontextmenu="event.preventDefault(); Livewire.find('{{ $this->getId() }}').call('openAssignModal', {{ $row['user']->id }}, '{{ $day['date'] }}'); return false;"
                                                     @endif>
                                                    Available
                                                    @if ($editMode)
                                                        <span style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); display: inline-flex; align-items: center; justify-content: center; width: 16px; height: 16px; border: 2px solid {{ $isSlotSelected ? '#2563EB' : '#9CA3AF' }}; background: {{ $isSlotSelected ? '#2563EB' : '#fff' }}; border-radius: 3px;">
                                                            @if ($isSlotSelected)
                                                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="4"><polyline points="20 6 9 17 4 12"/></svg>
                                                            @endif
                                                        </span>
                                                    @endif
                                                </div>
                                            @else
                                                <div class="scv2-available" style="cursor: not-allowed; opacity: 0.6;">
                                                    @if ($cell['available_half'] === 'am')
                                                        AM Available
                                                    @elseif ($cell['available_half'] === 'pm')
                                                        PM Available
                                                    @else
                                                        Available
                                                    @endif
                                                </div>
                                            @endif
                                        @endif
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @endif

    {{-- Assign Job Modal --}}
    @if ($showAssignModal)
        <div style="position: fixed; inset: 0; background: rgba(17, 24, 39, 0.5); z-index: 1000; display: flex; align-items: center; justify-content: center; padding: 20px;"
             wire:click.self="closeAssignModal">
            <div style="background: #fff; border-radius: 14px; width: 100%; max-width: 440px; display: flex; flex-direction: column; box-shadow: 0 25px 50px rgba(0,0,0,0.25);">
                <div style="padding: 20px 24px; border-bottom: 1px solid #F3F4F6; display: flex; align-items: center; justify-content: space-between;">
                    <h3 style="font-size: 18px; font-weight: 700; color: #111827; margin: 0;">Assign Job</h3>
                    <button type="button" wire:click="closeAssignModal"
                            style="background: transparent; border: 0; color: #9CA3AF; cursor: pointer; padding: 4px;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <form wire:submit.prevent="submitAssignment">
                    <div style="padding: 24px; display: flex; flex-direction: column; gap: 16px;">
                        <div style="background: #F9FAFB; padding: 12px; border-radius: 8px; font-size: 13px; color: #374151;">
                            <div><strong>Member:</strong> {{ $assignForm['user_name'] }}</div>
                            <div><strong>Date:</strong> {{ $assignForm['date'] }}</div>
                        </div>

                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 6px;">Job Type <span style="color: #DC2626;">*</span></label>
                            <div x-data="{ open: false, search: '' }" @click.away="open = false; search = ''" style="position: relative;">
                                <button type="button" class="scv2-combo-btn" @click="open = !open">
                                    @php $typeLabel = \App\Filament\Pages\SupportCalendarV2::JOB_TYPES[$assignForm['type'] ?? ''] ?? null; @endphp
                                    @if ($typeLabel)
                                        <span>{{ $typeLabel }}</span>
                                    @else
                                        <span class="placeholder">-- Select Session Type --</span>
                                    @endif
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                                </button>
                                <div x-show="open" x-cloak x-transition.opacity class="scv2-combo-pop">
                                    <input type="text" x-model="search" @click.stop placeholder="Search type..." class="scv2-combo-search">
                                    <div class="scv2-combo-list">
                                        @foreach (\App\Filament\Pages\SupportCalendarV2::JOB_TYPES as $val => $label)
                                            @php $haystack = strtolower(str_replace(["'", '"'], '', $label)); @endphp
                                            <button type="button"
                                                x-show="search === '' || '{{ e($haystack) }}'.includes(search.toLowerCase())"
                                                class="scv2-combo-item {{ ($assignForm['type'] ?? '') === $val ? 'selected' : '' }}"
                                                @click="$wire.set('assignForm.type', '{{ $val }}'); open = false; search = ''">
                                                {{ $label }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div style="padding: 16px 24px; border-top: 1px solid #F3F4F6; display: flex; justify-content: flex-end; gap: 10px;">
                        <button type="button" wire:click="closeAssignModal"
                                style="padding: 10px 20px; background: #fff; color: #111827; border: 1px solid #E5E7EB; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer;">Cancel</button>
                        <button type="submit"
                                style="padding: 10px 24px; background: #4F46E5; color: #fff; border: none; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer;">Assign</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Delete Confirmation Modal --}}
    @if ($showDeleteModal)
        <div style="position: fixed; inset: 0; background: rgba(17, 24, 39, 0.5); z-index: 1000; display: flex; align-items: center; justify-content: center; padding: 20px;"
             wire:click.self="closeDeleteModal">
            <div style="background: #fff; border-radius: 14px; width: 100%; max-width: 420px; display: flex; flex-direction: column; box-shadow: 0 25px 50px rgba(0,0,0,0.25);">
                <div style="padding: 20px 24px; border-bottom: 1px solid #F3F4F6; display: flex; align-items: center; justify-content: space-between;">
                    <h3 style="font-size: 18px; font-weight: 700; color: #B91C1C; margin: 0;">Delete Task?</h3>
                    <button type="button" wire:click="closeDeleteModal"
                            style="background: transparent; border: 0; color: #9CA3AF; cursor: pointer; padding: 4px;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <div style="padding: 24px; display: flex; flex-direction: column; gap: 12px;">
                    <div style="font-size: 14px; color: #374151;">This action cannot be undone. The following task will be removed:</div>
                    <div style="background: #FEF2F2; border: 1px solid #FECACA; padding: 12px 14px; border-radius: 10px; font-size: 13px; color: #991B1B;">
                        <div><strong>Task:</strong> {{ $deleteTarget['type'] }}</div>
                        <div><strong>Member:</strong> {{ $deleteTarget['user_name'] }}</div>
                        <div><strong>Date:</strong> {{ $deleteTarget['date'] }}</div>
                    </div>
                </div>
                <div style="padding: 16px 24px; border-top: 1px solid #F3F4F6; display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" wire:click="closeDeleteModal"
                            style="padding: 10px 20px; background: #fff; color: #111827; border: 1px solid #E5E7EB; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer;">Cancel</button>
                    <button type="button" wire:click="confirmDeleteAppointment"
                            style="padding: 10px 24px; background: #DC2626; color: #fff; border: none; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer;">Delete</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Bulk Change Type Modal --}}
    @if ($showBulkTypeModal)
        <div style="position: fixed; inset: 0; background: rgba(17, 24, 39, 0.5); z-index: 1000; display: flex; align-items: center; justify-content: center; padding: 20px;"
             wire:click.self="closeBulkTypeModal">
            <div style="width: 100%; max-width: 440px; background: #fff; border-radius: 14px; box-shadow: 0 20px 50px rgba(0,0,0,0.25);">
                @php
                    $jCount = count($selectedAppointmentIds);
                    $sCount = count($selectedEmptySlots);
                @endphp
                <div style="padding: 20px 24px; border-bottom: 1px solid #F3F4F6;">
                    <div style="font-size: 16px; font-weight: 700; color: #111827;">
                        @if ($sCount > 0 && $jCount > 0)
                            Apply Job Type
                        @elseif ($sCount > 0)
                            Create Jobs
                        @else
                            Change Job Type
                        @endif
                    </div>
                    <div style="font-size: 12px; color: #6B7280; margin-top: 4px;">
                        @if ($jCount > 0)
                            {{ $jCount }} existing job(s) will be updated.
                        @endif
                        @if ($sCount > 0)
                            {{ $sCount }} new job(s) will be created.
                        @endif
                    </div>
                </div>
                <div style="padding: 20px 24px;">
                    <label style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 6px;">New Type</label>
                    <div x-data="{ open: false, search: '' }" @click.away="open = false; search = ''" style="position: relative;">
                        <button type="button" class="scv2-combo-btn" @click="open = !open">
                            @php $bulkLabel = \App\Filament\Pages\SupportCalendarV2::JOB_TYPES[$bulkNewType ?? ''] ?? null; @endphp
                            @if ($bulkLabel)
                                <span>{{ $bulkLabel }}</span>
                            @else
                                <span class="placeholder">Select type...</span>
                            @endif
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                        </button>
                        <div x-show="open" x-cloak x-transition.opacity class="scv2-combo-pop">
                            <input type="text" x-model="search" @click.stop placeholder="Search type..." class="scv2-combo-search">
                            <div class="scv2-combo-list">
                                @foreach (\App\Filament\Pages\SupportCalendarV2::JOB_TYPES as $k => $label)
                                    @php $haystack = strtolower(str_replace(["'", '"'], '', $label)); @endphp
                                    <button type="button"
                                        x-show="search === '' || '{{ e($haystack) }}'.includes(search.toLowerCase())"
                                        class="scv2-combo-item {{ ($bulkNewType ?? '') === $k ? 'selected' : '' }}"
                                        @click="$wire.set('bulkNewType', '{{ $k }}'); open = false; search = ''">
                                        {{ $label }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                <div style="padding: 16px 24px; border-top: 1px solid #F3F4F6; display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" wire:click="closeBulkTypeModal"
                            style="padding: 10px 20px; background: #fff; color: #111827; border: 1px solid #E5E7EB; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer;">Cancel</button>
                    <button type="button" wire:click="applyBulkType"
                            style="padding: 10px 24px; background: #2563EB; color: #fff; border: none; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer;">Apply</button>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
