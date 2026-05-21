<!-- filepath: /var/www/html/timeteccrm/resources/views/filament/pages/training-setting.blade.php -->
<x-filament-panels::page>
    <div class="training-setting-container">
        {{-- Header Controls --}}
        <div class="header-controls">
            <div class="trainer-info">
                <h2 class="trainer-title">Trainer 1</h2>
                @php
                    $currentSession = \App\Models\TrainingSession::where('year', $selectedYear)
                        ->where('trainer_profile', 'TRAINER_1')
                        ->first();
                    $categoryLabel = match($currentSession->training_category ?? null) {
                        'HRDF_WEBINAR' => 'HRDF + Webinar',
                        'HRDF' => 'HRDF Only',
                        'WEBINAR' => 'Webinar Only',
                        default => '-',
                    };
                    $moduleLabel = match($currentSession->training_module ?? null) {
                        'OPERATIONAL' => 'Operational Module',
                        'STRATEGIC' => 'Strategic Module',
                        default => '-',
                    };
                @endphp
                <span class="trainer-subtitle">Training Category: {{ $categoryLabel }}</span>
                <span class="trainer-subtitle">Training Module: {{ $moduleLabel }}</span>
            </div>
            <div class="year-selection">
                <label for="year" class="year-label">Select Year:</label>
                <select wire:model.live="selectedYear" class="year-dropdown">
                    @for($year = 2025; $year <= 2027; $year++)
                        <option value="{{ $year }}">{{ $year }}</option>
                    @endfor
                </select>
            </div>

            <div class="action-buttons">
                @if(!$showCalendar)
                <button
                    wire:click="generateSchedule"
                    class="btn btn-generate"
                    @if(!$this->hasHolidaysForYear) disabled @endif
                    @if(!$this->hasHolidaysForYear) title="No public holidays found for {{ $selectedYear }}. Add holidays first." @endif
                    @if(!$this->hasHolidaysForYear) style="opacity: 0.5; cursor: not-allowed;" @endif
                >
                    <span class="btn-icon">📅</span>
                    Generate Schedule
                </button>
                @endif
                <button
                    onclick="if(confirm('This will permanently delete ALL training sessions, bookings, and attendees. This action cannot be undone.\n\nAre you sure?')) { @this.call('resetTrainingDb') }"
                    class="btn"
                    style="background: #dc2626; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 0.875rem;"
                >
                    🗑️ Reset DB
                </button>
            </div>
        </div>

        {{-- Monthly Calendar View --}}
        @if($showCalendar)
            {{-- Sticky Month Navigation Bar --}}
            <div class="month-nav-bar" x-data="{
                scrollToMonth(monthId) {
                    const el = document.getElementById(monthId);
                    if (el) {
                        const navBar = document.querySelector('.month-nav-bar');
                        const offset = 56 + (navBar ? navBar.offsetHeight : 0) + 12;
                        const top = el.getBoundingClientRect().top + window.scrollY - offset;
                        window.scrollTo({ top, behavior: 'smooth' });
                    }
                }
            }">
                @php
                    $now = Carbon\Carbon::now();
                    $currentMonthKey = $now->format('M');
                @endphp
                @foreach($this->monthlyCalendar as $monthKey => $monthData)
                    <button
                        class="month-nav-btn {{ $monthKey === $currentMonthKey && $selectedYear == $now->year ? 'active' : '' }}"
                        @click="scrollToMonth('month-{{ $monthKey }}')"
                        wire:click="$set('collapsedMonths.{{ $monthKey }}', false)"
                    >
                        {{ $monthKey }}
                    </button>
                @endforeach
            </div>

            <div class="months-list">
                @foreach($this->monthlyCalendar as $monthKey => $monthData)
                    <div class="month-section" id="month-{{ $monthKey }}">
                        {{-- Tier 1: Month Header (expand/collapse) --}}
                        <div class="month-header" wire:click="toggleMonth('{{ $monthKey }}')">
                            <div class="month-title-section">
                                <h3 class="month-title">{{ $monthData['name'] }}</h3>
                                <span class="month-count">({{ count($monthData['sessions']) }} weeks)</span>
                            </div>
                            <div class="month-toggle">
                                <i class="toggle-icon {{ ($collapsedMonths[$monthKey] ?? false) ? 'collapsed' : 'expanded' }}">▼</i>
                            </div>
                        </div>

                        @if(!($collapsedMonths[$monthKey] ?? false))
                            <div class="month-content">
                                @if(count($monthData['sessions']) > 0)
                                    @foreach($monthData['sessions'] as $index => $week)
                                        @php $weekKey = $monthKey . '-' . $week['week_number']; @endphp
                                        <div class="week-section status-{{ $week['status'] }}">
                                            {{-- Tier 2: Week Header (expand/collapse) --}}
                                            <div class="week-header" wire:click="toggleWeek('{{ $weekKey }}')">
                                                <div class="week-left">
                                                    <div class="week-info-line">
                                                        <span class="week-number">W{{ $week['week_number'] }}</span>
                                                        <span class="week-dates">
                                                            @if($week['session'])
                                                                {{ Carbon\Carbon::parse($week['session']->day1_date)->format('M j') }} -
                                                                {{ Carbon\Carbon::parse($week['session']->day3_date)->format('M j') }}
                                                            @else
                                                                {{ Carbon\Carbon::parse($week['dates']['tuesday'])->format('M j') }} -
                                                                {{ Carbon\Carbon::parse($week['dates']['thursday'])->format('M j') }}
                                                            @endif
                                                        </span>
                                                    </div>
                                                    @if($week['session'])
                                                        <span class="session-badge status-{{ strtolower($week['session']->status) }}">{{ $week['session']->status }}</span>
                                                    @endif
                                                </div>
                                                <div class="week-right">
                                                    @if($week['status'] === 'missing')
                                                        <button wire:click.stop="showDateSelectionForNewSession({{ $week['week_number'] }}, {{ json_encode($week['dates']) }})"
                                                                class="action-btn btn-create">
                                                            + Create
                                                        </button>
                                                    @elseif($week['status'] === 'needs_meeting' && $week['can_create_meeting'])
                                                        <button wire:click.stop="showMeetingConfirmation({{ $week['session']->id }})"
                                                                class="action-btn btn-meeting">
                                                            Meetings
                                                        </button>
                                                    @endif
                                                    @if($week['session'] && $week['session']->status === 'SCHEDULED' && $week['status'] !== 'past')
                                                        <button wire:click.stop="openCancelSessionModal({{ $week['session']->id }})"
                                                                class="action-btn btn-cancel-session">
                                                            Cancel
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>

                                            {{-- Tier 3: Day Details --}}
                                            @if(!($collapsedWeeks[$weekKey] ?? false) && $week['session'])
                                                <div class="week-content">
                                                    <div class="training-days">
                                                        @php
                                                            $sessionDates = [
                                                                1 => ['date' => $week['session']->day1_date, 'label' => 'Day 1'],
                                                                2 => ['date' => $week['session']->day2_date, 'label' => 'Day 2'],
                                                                3 => ['date' => $week['session']->day3_date, 'label' => 'Day 3']
                                                            ];
                                                        @endphp
                                                        @foreach($sessionDates as $dayNum => $dayData)
                                                            <div class="day-schedule">
                                                                <div class="day-info">
                                                                    <span class="day-label">{{ $dayData['label'] }}</span>
                                                                    <span class="day-date">{{ Carbon\Carbon::parse($dayData['date'])->format('M j') }} ({{ Carbon\Carbon::parse($dayData['date'])->format('l') }})</span>
                                                                </div>
                                                                @if(Carbon\Carbon::parse($dayData['date'])->isPast())
                                                                    <span class="no-meeting-btn">Passed</span>
                                                                @elseif($week['session']->{"day{$dayNum}_meeting_link"})
                                                                    <a href="{{ $week['session']->{"day{$dayNum}_meeting_link"} }}"
                                                                        target="_blank" class="meeting-btn">
                                                                        Join
                                                                    </a>
                                                                @else
                                                                    <span class="no-meeting-btn">Pending</span>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                @else
                                    <div class="empty-month">
                                        <p>No sessions</p>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Date Selection Modal --}}
        @if($showDateModal)
            <div class="modal-overlay">
                <div class="modal-container">
                    <div class="modal-header">
                        <h3>{{ $isCreatingNewSession ? 'Create New Training Session - Trainer 1' : 'Select Training Dates - Trainer 1' }}</h3>
                        <button wire:click="closeDateModal" class="modal-close">✕</button>
                    </div>

                    <div class="modal-body">
                        <p class="modal-instruction">
                            @if($isCreatingNewSession)
                                Select exactly 3 dates from Monday to Friday for this new training session:
                            @else
                                Select 1-3 dates from Monday to Friday for this training session:
                            @endif
                            <br><small class="holiday-notice">🏖️ Public holidays are disabled and cannot be selected.</small>
                        </p>

                        <div class="date-selection-grid">
                            @foreach($weekDates as $dateInfo)
                                <label class="date-option {{ in_array($dateInfo['date'], $selectedDates) ? 'selected' : '' }} {{ $dateInfo['is_holiday'] ? 'holiday-disabled' : '' }}">
                                    <input type="checkbox"
                                           wire:click="toggleDate('{{ $dateInfo['date'] }}')"
                                           class="date-input"
                                           {{ in_array($dateInfo['date'], $selectedDates) ? 'checked' : '' }}
                                           {{ $dateInfo['is_holiday'] ? 'disabled' : '' }}>
                                    <div class="date-content">
                                        <span class="day-name">{{ $dateInfo['day'] }}</span>
                                        <span class="date-formatted">{{ $dateInfo['formatted'] }}</span>
                                        @if($dateInfo['is_holiday'])
                                            <span class="holiday-label">🏖️ {{ $dateInfo['holiday_name'] }}</span>
                                        @endif
                                    </div>
                                </label>
                            @endforeach
                        </div>

                        @if(count($selectedDates) > 0)
                            <div class="selected-summary">
                                <strong>Selected dates:</strong> {{ count($selectedDates) }}/{{ $isCreatingNewSession ? '3 (required)' : '3' }}
                                @if($isCreatingNewSession && count($selectedDates) !== 3)
                                    <div class="selection-note">Please select exactly 3 dates for new training session.</div>
                                @endif
                            </div>
                        @endif
                    </div>

                    <div class="modal-footer">
                        <button wire:click="closeDateModal" class="btn btn-secondary">Cancel</button>
                        <button wire:click="createMeetingsWithSelectedDates" class="btn btn-primary">
                            {{ $isCreatingNewSession ? 'Create Session' : 'Continue' }}
                        </button>
                    </div>
                </div>
            </div>
        @endif

        {{-- Generate Schedule Modal - Category and Module Selection --}}
        @if($showGenerateModal)
            <div class="modal-overlay">
                <div class="modal-container">
                    <div class="modal-header">
                        <h3>Generate {{ $selectedYear }} Schedule - Trainer 1</h3>
                        <button wire:click="closeGenerateModal" class="modal-close">✕</button>
                    </div>

                    <div class="modal-body">
                        <p class="modal-instruction">
                            Select the training category and module for the entire year's schedule.
                            All sessions will use the same category and module.
                        </p>

                        <div class="form-group">
                            <label class="form-label">Choose Training Category</label>
                            <div class="radio-group">
                                @foreach($trainingCategories as $key => $label)
                                    <label class="radio-option">
                                        <input type="radio" wire:model.live="selectedCategory" value="{{ $key }}" class="radio-input">
                                        <span class="radio-text">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Choose Training Module</label>
                            <div class="radio-group">
                                @foreach($trainingModules as $key => $label)
                                    <label class="radio-option">
                                        <input type="radio" wire:model.live="selectedModule" value="{{ $key }}" class="radio-input">
                                        <span class="radio-text">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button wire:click="closeGenerateModal" class="btn btn-secondary">Cancel</button>
                        <button wire:click="confirmGenerateSchedule" class="btn btn-generate" wire:loading.attr="disabled" wire:target="confirmGenerateSchedule">
                            <span wire:loading.remove wire:target="confirmGenerateSchedule">📅 Generate Schedule</span>
                            <span wire:loading wire:target="confirmGenerateSchedule">
                                Generating...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        @endif

        {{-- Cancel Session Confirmation Modal --}}
        @if($showCancelSessionModal)
            <div class="modal-overlay">
                <div class="modal-container" style="max-width: 450px;">
                    <div class="modal-header" style="background: linear-gradient(135deg, #dc2626, #ef4444);">
                        <h3>Cancel Training Session</h3>
                        <button wire:click="closeCancelSessionModal" class="modal-close">✕</button>
                    </div>

                    <div class="modal-body">
                        @if($cancelSession)
                            <p style="margin-bottom: 12px;">Are you sure you want to cancel this session?</p>
                            <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 12px; margin-bottom: 16px;">
                                <strong>{{ $cancelSession->session_number }}</strong><br>
                                <span style="color: #6b7280; font-size: 13px;">
                                    {{ Carbon\Carbon::parse($cancelSession->day1_date)->format('M j, Y') }} -
                                    {{ Carbon\Carbon::parse($cancelSession->day3_date)->format('M j, Y') }}
                                </span>
                            </div>
                            <p style="color: #dc2626; font-size: 13px;">This action will mark the session as CANCELLED.</p>
                        @endif
                    </div>

                    <div class="modal-footer">
                        <button wire:click="closeCancelSessionModal" class="btn btn-secondary">No, Keep It</button>
                        <button wire:click="confirmCancelSession" class="btn btn-danger">Yes, Cancel Session</button>
                    </div>
                </div>
            </div>
        @endif

        {{-- Meeting Creation Confirmation Modal --}}
        @if($showMeetingConfirmModal)
            <div class="modal-overlay">
                <div class="modal-container" style="max-width: 450px;">
                    <div class="modal-header" style="background: linear-gradient(135deg, #f59e0b, #fbbf24);">
                        <h3 style="color: #92400e;">Create Teams Meetings</h3>
                        <button wire:click="closeMeetingConfirmModal" class="modal-close" style="color: #92400e;">✕</button>
                    </div>

                    <div class="modal-body">
                        @if($meetingSession)
                            <p style="margin-bottom: 12px;">Are you sure you want to create Microsoft Teams meetings for this session?</p>
                            <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 12px; margin-bottom: 16px;">
                                <strong>{{ $meetingSession->session_number }}</strong><br>
                                <span style="color: #6b7280; font-size: 13px;">
                                    {{ Carbon\Carbon::parse($meetingSession->day1_date)->format('M j, Y') }} -
                                    {{ Carbon\Carbon::parse($meetingSession->day3_date)->format('M j, Y') }}
                                </span>
                            </div>
                            <p style="color: #92400e; font-size: 13px;">This will generate 3 Teams meeting links (Day 1, Day 2, Day 3).</p>
                        @endif
                    </div>

                    <div class="modal-footer">
                        <button wire:click="closeMeetingConfirmModal" class="btn btn-secondary">Cancel</button>
                        <button wire:click="confirmGenerateTeamsMeetings" class="btn btn-meeting" wire:loading.attr="disabled" wire:target="confirmGenerateTeamsMeetings">
                            <span wire:loading.remove wire:target="confirmGenerateTeamsMeetings">📞 Yes, Create Meetings</span>
                            <span wire:loading wire:target="confirmGenerateTeamsMeetings">⏳ Creating...</span>
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <style>
        .training-setting-container {
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Header Controls */
        .header-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 18px 24px;
            margin-bottom: 20px;
            box-shadow: 0 6px 24px rgba(0, 0, 0, 0.1);
        }

        .trainer-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .trainer-title {
            font-size: 20px;
            font-weight: 700;
            margin: 0;
        }

        .trainer-subtitle {
            font-size: 12px;
            opacity: 0.9;
        }

        .year-selection {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .year-label {
            font-weight: 600;
            font-size: 14px;
        }

        .year-dropdown {
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            background: white;
            background-image: none !important;
            color: #333;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
        }

        .year-dropdown:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.3);
        }

        .action-buttons {
            display: flex;
            gap: 15px;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 12px;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .btn-icon {
            font-size: 16px;
        }

        .btn-generate {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
        }

        .btn-generate:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(40, 167, 69, 0.3);
        }

        .btn-danger {
            background: linear-gradient(45deg, #dc3545, #e74c3c);
            color: white;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(220, 53, 69, 0.3);
        }

        .btn-create {
            background: linear-gradient(45deg, #dc3545, #e74c3c);
            color: white;
            font-size: 12px;
            padding: 8px 12px;
        }

        .btn-meeting {
            background: linear-gradient(45deg, #ffc107, #ffb300);
            color: #333;
            font-size: 12px;
            padding: 8px 12px;
        }

        /* Legend */
        .legend {
            background: white;
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 20px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.1);
        }

        .legend h3 {
            margin: 0 0 12px 0;
            color: #333;
            font-size: 16px;
        }

        .legend-items {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .status-indicator {
            width: 20px;
            height: 20px;
            border-radius: 4px;
        }

        .status-missing {
            background: linear-gradient(45deg, #dc3545, #e74c3c);
        }

        .status-needs-meeting {
            background: linear-gradient(45deg, #ffc107, #ffb300);
        }

        .status-ready {
            background: linear-gradient(45deg, #28a745, #20c997);
        }

        .status-past {
            background: linear-gradient(45deg, #6c757d, #5a6268);
        }

        /* Month Navigation Bar */
        .month-nav-bar {
            position: sticky;
            top: 56px;
            z-index: 1;
            display: flex;
            gap: 4px;
            padding: 10px 12px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            margin-bottom: 12px;
        }

        .month-nav-btn {
            flex: 1;
            padding: 8px 4px;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            background: #f3f4f6;
            color: #4b5563;
            transition: all 0.2s ease;
        }

        .month-nav-btn:hover {
            background: #e0e7ff;
            color: #4338ca;
        }

        .month-nav-btn.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.4);
        }

        /* Monthly Calendar */
        .months-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .month-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
            overflow: hidden;
        }

        .month-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 14px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            user-select: none;
            transition: all 0.2s ease;
        }

        .month-header:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
        }

        .month-title-section {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .month-title {
            font-size: 18px;
            font-weight: 700;
            margin: 0;
        }

        .month-count {
            background: rgba(255, 255, 255, 0.2);
            padding: 4px 10px;
            border-radius: 16px;
            font-size: 12px;
            font-weight: 600;
        }

        .month-toggle {
            display: flex;
            align-items: center;
        }

        .toggle-icon {
            font-size: 18px;
            transition: transform 0.3s ease;
        }

        .toggle-icon.expanded {
            transform: rotate(0deg);
        }

        .toggle-icon.collapsed {
            transform: rotate(-90deg);
        }

        .month-content {
            padding: 10px;
            display: flex;
            gap: 8px;
        }

        .month-content > .week-section {
            flex: 1 1 0;
            min-width: 0;
        }

        .month-content > .empty-month {
            width: 100%;
        }

        /* Week Section */
        .week-section {
            border-radius: 8px;
            border: 2px solid;
            overflow: hidden;
            position: relative;
        }

        .week-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            width: 4px;
        }

        .week-section.status-missing {
            background: #fee2e2;
            border-color: #ef4444;
        }

        .week-section.status-missing::before {
            background: #dc2626;
        }

        .week-section.status-needs_meeting {
            background: #fef3c7;
            border-color: #f59e0b;
        }

        .week-section.status-needs_meeting::before {
            background: #d97706;
        }

        .week-section.status-ready {
            background: #d1fae5;
            border-color: #10b981;
        }

        .week-section.status-ready::before {
            background: #059669;
        }

        .week-section.status-past {
            background: #e5e7eb;
            border-color: #9ca3af;
        }

        .week-section.status-past::before {
            background: #6b7280;
        }

        .week-header {
            padding: 10px 12px 10px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            user-select: none;
        }

        .week-left {
            display: flex;
            flex-direction: column;
            gap: 4px;
            min-width: 0;
        }

        .week-info-line {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .week-number {
            font-size: 14px;
            font-weight: 700;
            color: #1f2937;
            white-space: nowrap;
        }

        .week-dates {
            font-size: 12px;
            color: #6b7280;
            font-weight: 500;
            white-space: nowrap;
        }

        .week-right {
            display: flex;
            align-items: center;
            gap: 4px;
            flex-shrink: 0;
        }

        .action-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .action-btn:hover {
            transform: translateY(-1px);
        }

        .btn-create {
            background: linear-gradient(45deg, #dc2626, #ef4444);
            color: white;
        }

        .btn-meeting {
            background: linear-gradient(45deg, #f59e0b, #fbbf24);
            color: #92400e;
        }

        .btn-cancel-session {
            background: linear-gradient(45deg, #dc2626, #ef4444);
            color: white;
        }

        .session-badge {
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            align-self: flex-start;
        }

        .session-badge.status-draft {
            background: #dbeafe;
            color: #1e40af;
        }

        .session-badge.status-scheduled {
            background: #00ff58;
            color: #166534;
        }

        .session-badge.status-cancelled {
            background: #fee2e2;
            color: #991b1b;
        }

        /* Week Content - Day Details */
        .week-content {
            padding: 0 14px 12px 18px;
            border-top: 1px solid rgba(0, 0, 0, 0.06);
        }

        .session-info {
            padding: 8px 0 6px;
        }

        .session-name {
            font-size: 13px;
            font-weight: 700;
            color: #1f2937;
        }

        .training-days {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .day-schedule {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(255, 255, 255, 0.7);
            padding: 8px 12px;
            border-radius: 6px;
        }

        .day-info {
            display: flex;
            flex-direction: column;
        }

        .day-label {
            font-size: 12px;
            font-weight: 600;
            color: #374151;
        }

        .day-date {
            font-size: 11px;
            color: #6b7280;
        }

        .meeting-btn {
            background: #10b981;
            color: white;
            padding: 4px 10px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 11px;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .meeting-btn:hover {
            background: #059669;
        }

        .no-meeting-btn {
            background: #f3f4f6;
            color: #6b7280;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 500;
        }

        .empty-month {
            text-align: center;
            padding: 20px;
            color: #9ca3af;
        }

        .empty-month p {
            font-size: 13px;
            margin: 0;
        }

        /* Modal */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .modal-container {
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow: auto;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 25px;
            border-bottom: 1px solid #eee;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #999;
            transition: color 0.3s ease;
        }

        .modal-close:hover {
            color: #333;
        }

        .modal-body {
            padding: 20px 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 12px;
            font-size: 15px;
        }

        .radio-group {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .radio-option {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .radio-option:hover {
            border-color: #007bff;
            background: #f8f9fa;
        }

        .radio-input {
            margin: 0;
        }

        .radio-input:checked + .radio-text {
            font-weight: 600;
            color: #007bff;
        }

        .radio-option:has(.radio-input:checked) {
            border-color: #007bff;
            background: #e3f2fd;
        }

        .radio-text {
            flex: 1;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            padding: 15px 25px;
            border-top: 1px solid #eee;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn-primary {
            background: linear-gradient(45deg, #007bff, #0056b3);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0, 123, 255, 0.3);
        }

        .btn-primary:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .loading-spinner {
            animation: spin 1s linear infinite;
            margin-right: 6px;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .modal-instruction {
            margin-bottom: 15px;
            color: #666;
            font-size: 13px;
            line-height: 1.4;
        }

        .holiday-notice {
            color: #dc2626;
            font-weight: 500;
            display: block;
            margin-top: 6px;
            font-size: 12px;
        }

        .date-selection-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 8px;
            margin-bottom: 15px;
        }

        .date-option {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }

        .date-option:hover {
            border-color: #007bff;
            background: #f8f9fa;
        }

        .date-option.selected {
            border-color: #007bff;
            background: #e3f2fd;
        }

        .date-option.holiday-disabled {
            background: #fff5f5;
            border-color: #fca5a5;
            opacity: 0.7;
            cursor: not-allowed;
        }

        .date-option.holiday-disabled:hover {
            border-color: #fca5a5;
            background: #fff5f5;
        }

        .date-input {
            margin: 0;
            width: 16px;
            height: 16px;
        }

        .date-content {
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .day-name {
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        .date-formatted {
            color: #666;
            font-size: 12px;
        }

        .holiday-label {
            color: #dc2626;
            font-size: 11px;
            font-weight: 500;
            margin-top: 2px;
            font-style: italic;
        }

        .selected-summary {
            padding: 10px;
            background: #e3f2fd;
            border-radius: 6px;
            color: #1976d2;
            font-size: 13px;
            text-align: center;
        }

        .selection-note {
            margin-top: 6px;
            font-size: 11px;
            color: #d32f2f;
            font-style: italic;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .month-nav-bar {
                flex-wrap: wrap;
                gap: 3px;
            }

            .month-nav-btn {
                flex: 1 1 calc(25% - 3px);
                font-size: 11px;
                padding: 6px 2px;
            }

            .training-setting-container {
                padding: 15px;
            }

            .header-controls {
                flex-direction: column;
                gap: 20px;
                text-align: center;
                padding: 20px;
            }

            .month-content {
                flex-wrap: wrap;
            }

            .month-content > .week-section {
                flex: 1 1 100%;
            }

            .modal-container {
                width: 95%;
                margin: 20px;
            }

            .modal-header,
            .modal-body,
            .modal-footer {
                padding: 15px;
            }

            .date-selection-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</x-filament-panels::page>
