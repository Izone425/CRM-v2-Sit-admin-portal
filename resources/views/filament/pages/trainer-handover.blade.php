<x-filament-panels::page>
    <div class="trainer-handover-container">
        {{-- Header Section --}}
        <div class="header-section">
            <div class="header-card">
                <div class="header-row">
                    <div class="header-item">
                        <span class="header-label">Trainer</span>
                        <div class="trainer-select">
                            @foreach($trainers as $key => $label)
                                <label class="trainer-option {{ $selectedTrainer === $key ? 'selected' : '' }}">
                                    <input type="radio" wire:model.live="selectedTrainer" value="{{ $key }}" class="hidden-input">
                                    <span class="option-text">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="header-item">
                        <span class="header-label">Year</span>
                        <div class="year-select">
                            @foreach($years as $year)
                                <label class="year-option {{ $selectedYear === $year ? 'selected' : '' }}">
                                    <input type="radio" wire:model.live="selectedYear" value="{{ $year }}" class="hidden-input">
                                    <span class="option-text">{{ $year }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sessions List --}}
        @if($showSessions)
            <div class="sessions-section">
                @foreach($this->trainingSessions as $sessionData)
                    @php $session = $sessionData['session']; @endphp
                    <div class="session-card status-{{ $sessionData['status'] }} {{ $sessionData['is_expanded'] ? 'expanded' : '' }}">
                        {{-- Session Header Row --}}
                        <div class="session-header" wire:click="toggleSession({{ $session->id }})">
                            <div class="session-main">
                                <div class="session-badge {{ $sessionData['status'] }}">
                                    {{ $session->session_number }}
                                </div>
                                <div class="session-info">
                                    <span class="training-category-badge">{{ $this->getTrainingCategoryLabel($sessionData['training_category']) }}</span>
                                    @if($sessionData['status'] === 'current_week')
                                        <span class="session-week-badge current">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                            This Week
                                        </span>
                                    @elseif($sessionData['status'] === 'past')
                                        <span class="session-week-badge past">Completed</span>
                                    @endif
                                </div>
                            </div>
                            <div class="session-dates">
                                <div class="date-row">
                                    <span class="day-label">Day 1</span>
                                    <span class="day-date">{{ \Carbon\Carbon::parse($session->day1_date)->format('j F Y') }}</span>
                                    <span class="day-name">{{ \Carbon\Carbon::parse($session->day1_date)->format('l') }}</span>
                                </div>
                                <div class="date-row">
                                    <span class="day-label">Day 2</span>
                                    <span class="day-date">{{ \Carbon\Carbon::parse($session->day2_date)->format('j F Y') }}</span>
                                    <span class="day-name">{{ \Carbon\Carbon::parse($session->day2_date)->format('l') }}</span>
                                </div>
                                <div class="date-row">
                                    <span class="day-label">Day 3</span>
                                    <span class="day-date">{{ \Carbon\Carbon::parse($session->day3_date)->format('j F Y') }}</span>
                                    <span class="day-name">{{ \Carbon\Carbon::parse($session->day3_date)->format('l') }}</span>
                                </div>
                            </div>
                            <div class="session-stats">
                                @if(in_array($sessionData['training_category'], ['HRDF', 'HRDF_WEBINAR']))
                                    <div class="stat-item hrdf">
                                        <span class="stat-label">HRDF Slot:</span>
                                        <span class="stat-value">{{ $sessionData['hrdf_count'] }}/{{ $sessionData['hrdf_limit'] }}</span>
                                    </div>
                                @endif
                                @if(in_array($sessionData['training_category'], ['WEBINAR', 'HRDF_WEBINAR']))
                                    <div class="stat-item webinar">
                                        <span class="stat-label">Webinar Slot:</span>
                                        <span class="stat-value">{{ $sessionData['webinar_count'] }}/{{ $sessionData['webinar_limit'] }}</span>
                                    </div>
                                @endif
                            </div>
                            <div class="session-actions">
                                <button class="expand-btn {{ $sessionData['is_expanded'] ? 'expanded' : '' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                </button>
                            </div>
                        </div>

                        {{-- Expanded Content --}}
                        @if($sessionData['is_expanded'])
                            <div class="session-content">
                                @php $bookings = $this->getSessionBookings($session->id); @endphp

                                {{-- HRDF Bookings --}}
                                @if(in_array($sessionData['training_category'], ['HRDF', 'HRDF_WEBINAR']))
                                    <div class="booking-section">
                                        <div class="section-header hrdf">
                                            <h4>Online HRDF Training Handover ID</h4>
                                        </div>

                                        @if($bookings->has('HRDF') && $bookings['HRDF']->count() > 0)
                                            <div class="booking-table hrdf-table">
                                                <div class="table-header hrdf-header">
                                                    <div class="th">Handover ID</div>
                                                    <div class="th">Submitted By</div>
                                                    <div class="th">Submitted Date & Time</div>
                                                    <div class="th">Company Name</div>
                                                    <div class="th">Participant Count</div>
                                                    <div class="th">HRDF Status</div>
                                                    <div class="th">Training Status</div>
                                                    <div class="th"></div>
                                                </div>

                                                @foreach($bookings['HRDF'] as $booking)
                                                    <div class="booking-item {{ $this->isBookingExpanded($booking->id) ? 'expanded' : '' }}">
                                                        <div class="table-row hrdf-row" wire:click="toggleBooking({{ $booking->id }})">
                                                            <div class="td handover-id">{{ $booking->handover_id }}</div>
                                                            <div class="td">{{ $booking->submitted_by }}</div>
                                                            <div class="td">{{ $booking->submitted_at ? $booking->submitted_at->format('j M Y h:i A') : '-' }}</div>
                                                            <div class="td company">{{ $booking->company_name }}</div>
                                                            <div class="td count">Count: {{ max($booking->attendees->count(), $booking->expected_attendees ?? 0) }}</div>
                                                            <div class="td">
                                                                <span class="status-badge status-{{ strtolower($booking->hrdfClaim()?->claim_status ?? 'pending') }}">{{ $booking->hrdfClaim()?->claim_status ?? '-' }}</span>
                                                            </div>
                                                            <div class="td">
                                                                {{ strtoupper($booking->status ?? 'PENDING') }}
                                                            </div>
                                                            <div class="td">
                                                                <button class="expand-row-btn {{ $this->isBookingExpanded($booking->id) ? 'expanded' : '' }}">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                                                </button>
                                                            </div>
                                                        </div>

                                                        {{-- Attendance PDF buttons per day --}}
                                                        <div class="attendance-pdf-actions">
                                                            @for($day = 1; $day <= 3; $day++)
                                                                @if($this->bookingHasAttendanceData($session->id, $day, $booking->id))
                                                                    <button class="attendance-pdf-btn" wire:click.stop="downloadAttendancePdf({{ $session->id }}, {{ $day }}, {{ $booking->id }})" title="Download Day {{ $day }} Attendance List">
                                                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><polyline points="9 15 12 18 15 15"/></svg>
                                                                        Day {{ $day }}
                                                                    </button>
                                                                @endif
                                                            @endfor
                                                        </div>

                                                        {{-- Attendee Details --}}
                                                        @if($this->isBookingExpanded($booking->id))
                                                            <div class="attendee-section">
                                                                <table class="attendee-data-table">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>No.</th>
                                                                            <th>Training Type</th>
                                                                            <th>Name</th>
                                                                            <th>Email Address</th>
                                                                            <th>HP Number</th>
                                                                            <th>HRDF Grant ID</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        @forelse($booking->attendees as $index => $attendee)
                                                                            <tr>
                                                                                <td>{{ $index + 1 }}</td>
                                                                                <td>{{ $booking->training_category === 'NEW_TRAINING' ? 'NEW' : 'RE-TRAINING' }}</td>
                                                                                <td>{{ $attendee->name }}</td>
                                                                                <td>{{ $attendee->email }}</td>
                                                                                <td>{{ $attendee->phone ?? '-' }}</td>
                                                                                <td>{{ $booking->hrdf_grant_id ?? '-' }}</td>
                                                                            </tr>
                                                                        @empty
                                                                            <tr>
                                                                                <td colspan="6" class="no-data">No attendees registered yet</td>
                                                                            </tr>
                                                                        @endforelse
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="no-bookings">No HRDF training bookings for this session</div>
                                        @endif
                                    </div>
                                @endif

                                {{-- Webinar Bookings --}}
                                @if(in_array($sessionData['training_category'], ['WEBINAR', 'HRDF_WEBINAR']))
                                    <div class="booking-section">
                                        <div class="section-header webinar">
                                            <h4>Online Webinar Training Handover ID</h4>
                                        </div>

                                        @if($bookings->has('WEBINAR') && $bookings['WEBINAR']->count() > 0)
                                            <div class="booking-table">
                                                <div class="table-header">
                                                    <div class="th">Handover ID</div>
                                                    <div class="th">Submitted By</div>
                                                    <div class="th">Submitted Date & Time</div>
                                                    <div class="th">Company Name</div>
                                                    <div class="th">Participant Count</div>
                                                    <div class="th">Training Status</div>
                                                    <div class="th"></div>
                                                </div>

                                                @foreach($bookings['WEBINAR'] as $booking)
                                                    <div class="booking-item {{ $this->isBookingExpanded($booking->id) ? 'expanded' : '' }}">
                                                        <div class="table-row" wire:click="toggleBooking({{ $booking->id }})">
                                                            <div class="td handover-id">{{ $booking->handover_id }}</div>
                                                            <div class="td">{{ $booking->submitted_by }}</div>
                                                            <div class="td">{{ $booking->submitted_at ? $booking->submitted_at->format('j M Y h:i A') : '-' }}</div>
                                                            <div class="td company">{{ $booking->company_name }}</div>
                                                            <div class="td count">Count: {{ max($booking->attendees->count(), $booking->expected_attendees ?? 0) }}</div>
                                                            <div class="td">
                                                                {{ strtoupper($booking->status ?? 'PENDING') }}</span>
                                                            </div>
                                                            <div class="td">
                                                                <button class="expand-row-btn {{ $this->isBookingExpanded($booking->id) ? 'expanded' : '' }}">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                                                </button>
                                                            </div>
                                                        </div>

                                                        {{-- Attendance PDF buttons per day --}}
                                                        <div class="attendance-pdf-actions">
                                                            @for($day = 1; $day <= 3; $day++)
                                                                @if($this->bookingHasAttendanceData($session->id, $day, $booking->id))
                                                                    <button class="attendance-pdf-btn" wire:click.stop="downloadAttendancePdf({{ $session->id }}, {{ $day }}, {{ $booking->id }})" title="Download Day {{ $day }} Attendance List">
                                                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><polyline points="9 15 12 18 15 15"/></svg>
                                                                        Day {{ $day }}
                                                                    </button>
                                                                @endif
                                                            @endfor
                                                        </div>

                                                        {{-- Attendee Details --}}
                                                        @if($this->isBookingExpanded($booking->id))
                                                            <div class="attendee-section">
                                                                <table class="attendee-data-table">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>No.</th>
                                                                            <th>Training Type</th>
                                                                            <th>Name</th>
                                                                            <th>Email Address</th>
                                                                            <th>HP Number</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        @forelse($booking->attendees as $index => $attendee)
                                                                            <tr>
                                                                                <td>{{ $index + 1 }}</td>
                                                                                <td>{{ $booking->training_category === 'NEW_TRAINING' ? 'NEW' : 'RE-TRAINING' }}</td>
                                                                                <td>{{ $attendee->name }}</td>
                                                                                <td>{{ $attendee->email }}</td>
                                                                                <td>{{ $attendee->phone ?? '-' }}</td>
                                                                            </tr>
                                                                        @empty
                                                                            <tr>
                                                                                <td colspan="5" class="no-data">No attendees registered yet</td>
                                                                            </tr>
                                                                        @endforelse
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="no-bookings">No Webinar training bookings for this session</div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach

                @if($this->trainingSessions->count() === 0)
                    <div class="no-sessions">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                        <p>No training sessions found for {{ $selectedYear }}</p>
                    </div>
                @endif
            </div>
        @endif
    </div>

    <style>
        .trainer-handover-container {
            padding: 24px;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8fafc;
            min-height: 100vh;
        }

        .hidden-input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        /* Header Section */
        .header-section {
            margin-bottom: 24px;
        }

        .header-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
        }

        .header-row {
            display: flex;
            gap: 40px;
            align-items: flex-start;
        }

        .header-item {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .header-label {
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .trainer-select,
        .year-select {
            display: flex;
            gap: 8px;
        }

        .trainer-option,
        .year-option {
            display: inline-flex;
            padding: 10px 20px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            background: white;
        }

        .trainer-option:hover,
        .year-option:hover {
            border-color: #6366f1;
            background: #f5f3ff;
        }

        .trainer-option.selected,
        .year-option.selected {
            border-color: #6366f1;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
        }

        .option-text {
            font-size: 14px;
            font-weight: 600;
        }

        /* Sessions Section */
        .sessions-section {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .session-card {
            border-radius: 14px;
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid #e2e8f0;
        }

        .session-card.status-past {
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            border-left: 4px solid #9ca3af;
            opacity: 0.7;
        }

        .session-card.status-current_week {
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
            border-left: 4px solid #10b981;
            box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2);
        }

        .session-card.status-future {
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
            border-left: 4px solid #f59e0b;
        }

        .session-card.expanded {
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
        }

        .session-header {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 20px;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .session-header:hover {
            background: rgba(255,255,255,0.5);
        }

        .session-main {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .session-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 100px;
            padding: 12px 20px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 14px;
        }

        .session-badge.past {
            background: linear-gradient(135deg, #9ca3af, #6b7280);
            color: white;
        }

        .session-badge.current_week {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            box-shadow: 0 0 12px rgba(16, 185, 129, 0.4);
        }

        .session-badge.future {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }

        .session-info {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .training-category-badge {
            display: inline-flex;
            padding: 6px 12px;
            background: #ccfbf1;
            color: #0d9488;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            width: fit-content;
        }

        .session-week-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            width: fit-content;
        }

        .session-week-badge.current {
            background: #d1fae5;
            color: #047857;
            animation: pulse-green 2s infinite;
        }

        .session-week-badge.past {
            background: #e5e7eb;
            color: #6b7280;
        }

        @keyframes pulse-green {
            0%, 100% {
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4);
            }
            50% {
                box-shadow: 0 0 0 4px rgba(16, 185, 129, 0);
            }
        }

        .session-dates {
            display: flex;
            flex-direction: column;
            gap: 6px;
            flex: 1;
        }

        .date-row {
            display: flex;
            gap: 12px;
            font-size: 13px;
        }

        .day-label {
            font-weight: 700;
            color: #64748b;
            min-width: 50px;
        }

        .day-date {
            color: #1e293b;
            font-weight: 500;
            min-width: 120px;
        }

        .day-name {
            color: #64748b;
        }

        .attendance-pdf-actions {
            display: flex;
            gap: 8px;
            padding: 8px 16px;
            background: #f8fafc;
            border-top: 1px dashed #e2e8f0;
        }

        .attendance-pdf-actions:empty {
            display: none;
        }

        .attendance-pdf-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .attendance-pdf-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4);
        }

        .session-stats {
            display: flex;
            gap: 12px;
        }

        .stat-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 12px 20px;
            background: white;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            min-width: 100px;
        }

        .stat-item.hrdf {
            border-color: #fde68a;
            background: #fefce8;
        }

        .stat-item.webinar {
            border-color: #a7f3d0;
            background: #ecfdf5;
        }

        .stat-label {
            font-size: 11px;
            color: #64748b;
            font-weight: 500;
        }

        .stat-value {
            font-size: 18px;
            font-weight: 700;
            color: #1e293b;
        }

        .session-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .expand-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            background: #f1f5f9;
            border: none;
            border-radius: 8px;
            color: #64748b;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .expand-btn:hover {
            background: #e2e8f0;
        }

        .expand-btn.expanded svg {
            transform: rotate(180deg);
        }

        .expand-btn svg {
            transition: transform 0.2s ease;
        }

        /* Session Content - TIER 2 Container */
        .session-content {
            border-top: 3px solid #6366f1;
            padding: 24px;
            background: linear-gradient(180deg, #eef2ff 0%, #f8fafc 100%);
            margin: 0;
        }

        .booking-section {
            margin-bottom: 24px;
            margin-left: 20px;
            position: relative;
        }

        .booking-section::before {
            content: '';
            position: absolute;
            left: -20px;
            top: 0;
            bottom: 0;
            width: 3px;
            background: linear-gradient(180deg, #6366f1, #8b5cf6);
            border-radius: 3px;
        }

        .booking-section:last-child {
            margin-bottom: 0;
        }

        .section-header {
            padding: 14px 20px;
            border-radius: 10px 10px 0 0;
            margin-bottom: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .section-header::before {
            font-size: 9px;
            font-weight: 800;
            padding: 3px 8px;
            background: rgba(255,255,255,0.3);
            border-radius: 4px;
            letter-spacing: 0.5px;
        }

        .section-header.hrdf {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        }

        .section-header.webinar {
            background: linear-gradient(135deg, #10b981, #059669);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .section-header h4 {
            margin: 0;
            font-size: 14px;
            font-weight: 700;
            color: white;
        }

        /* HRDF Booking Cards - TIER 2 */
        .booking-cards {
            display: flex;
            flex-direction: column;
            gap: 12px;
            background: white;
            border: 2px solid #e2e8f0;
            border-top: none;
            border-radius: 0 0 10px 10px;
            padding: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        .booking-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
            transition: all 0.2s ease;
        }

        .booking-card:hover {
            border-color: #cbd5e1;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .booking-card.expanded {
            background: linear-gradient(180deg, #fffbeb 0%, #fef3c7 100%);
            border-color: #f59e0b;
            box-shadow: 0 4px 16px rgba(245, 158, 11, 0.2);
        }

        .booking-card-header {
            display: grid;
            grid-template-columns: 1.5fr 1.5fr 1.2fr 50px;
            gap: 16px;
            padding: 16px;
            cursor: pointer;
            align-items: center;
            transition: background 0.2s ease;
        }

        .booking-card-header:hover {
            background: rgba(0,0,0,0.02);
        }

        .booking-main-info {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .handover-badge {
            display: inline-flex;
            font-size: 14px;
            font-weight: 700;
            color: #6366f1;
            background: #eef2ff;
            padding: 4px 10px;
            border-radius: 6px;
            width: fit-content;
        }

        .company-name {
            font-size: 14px;
            font-weight: 600;
            color: #1e293b;
        }

        .booking-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
        }

        .meta-item {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .meta-label {
            font-size: 10px;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .meta-value {
            font-size: 13px;
            font-weight: 500;
            color: #334155;
        }

        .meta-value.count-badge {
            display: inline-flex;
            font-weight: 700;
            color: #6366f1;
            background: #eef2ff;
            padding: 2px 8px;
            border-radius: 4px;
            width: fit-content;
        }

        .booking-statuses {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .status-item {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .status-label {
            font-size: 10px;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .expand-card-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            background: #e2e8f0;
            border: none;
            border-radius: 8px;
            color: #64748b;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .expand-card-btn:hover {
            background: #cbd5e1;
            color: #475569;
        }

        .expand-card-btn.expanded {
            background: #f59e0b;
            color: white;
        }

        .expand-card-btn.expanded svg {
            transform: rotate(180deg);
        }

        .expand-card-btn svg {
            transition: transform 0.2s ease;
        }

        /* Booking Table - TIER 2 */
        .booking-table {
            background: white;
            border: 2px solid #e2e8f0;
            border-top: none;
            border-radius: 0 0 10px 10px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        .table-header {
            display: grid;
            grid-template-columns: 1fr 1fr 1.3fr 1.5fr 1fr 1fr 50px;
            gap: 12px;
            padding: 12px 16px;
            background: #f1f5f9;
            border-bottom: 1px solid #e2e8f0;
        }

        /* HRDF Table - 8 columns */
        .hrdf-table .table-header.hrdf-header,
        .hrdf-table .table-row.hrdf-row {
            grid-template-columns: 1fr 1fr 1.2fr 1.4fr 1fr 0.9fr 0.9fr 50px;
        }

        .th {
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .booking-item {
            border-bottom: 2px solid #e2e8f0;
            transition: all 0.2s ease;
        }

        .booking-item:last-child {
            border-bottom: none;
        }

        .booking-item.expanded {
            background: linear-gradient(180deg, #f0f9ff 0%, #e0f2fe 100%);
            border-left: 4px solid #0ea5e9;
            margin-left: -4px;
            padding-left: 4px;
        }

        .table-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1.3fr 1.5fr 1fr 1fr 50px;
            gap: 12px;
            padding: 14px 16px;
            cursor: pointer;
            align-items: center;
            transition: background 0.2s ease;
        }

        .table-row:hover {
            background: #f8fafc;
        }

        .td {
            font-size: 13px;
            color: #334155;
        }

        .td.handover-id {
            font-weight: 700;
            color: #6366f1;
        }

        .td.lead-id {
            font-weight: 600;
            color: #0ea5e9;
        }

        .td.company {
            font-weight: 600;
            color: #1e293b;
        }

        .td.count {
            font-weight: 700;
            color: #6366f1;
        }

        /* Status Badges */
        .status-badge {
            display: inline-flex;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .status-badge.status-booking {
            background: #fef3c7;
            color: #92400e;
        }

        .status-badge.status-apply {
            background: #e0f2fe;
            color: #0369a1;
        }

        .status-badge.status-approved {
            background: #dcfce7;
            color: #166534;
        }

        .training-status {
            display: inline-flex;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .training-status.pending {
            background: #fef3c7;
            color: #92400e;
        }

        .training-status.completed {
            background: #dcfce7;
            color: #166534;
        }

        .training-status.cancel {
            background: #fee2e2;
            color: #dc2626;
        }

        .expand-row-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            background: #e2e8f0;
            border: none;
            border-radius: 6px;
            color: #64748b;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .expand-row-btn:hover {
            background: #cbd5e1;
        }

        .expand-row-btn.expanded svg {
            transform: rotate(180deg);
        }

        .expand-row-btn svg {
            transition: transform 0.2s ease;
        }

        /* Attendee Table */
        .attendee-table {
            margin: 0 16px 16px 16px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
        }

        .attendee-header {
            display: grid;
            grid-template-columns: 1fr 1fr 1.5fr 2fr 1.2fr 1.2fr;
            gap: 12px;
            padding: 10px 14px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }

        .ath {
            font-size: 10px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .attendee-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1.5fr 2fr 1.2fr 1.2fr;
            gap: 12px;
            padding: 12px 14px;
            border-bottom: 1px solid #f1f5f9;
            align-items: center;
        }

        .attendee-row:last-child {
            border-bottom: none;
        }

        .atd {
            font-size: 13px;
            color: #334155;
        }

        .download-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .download-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        /* Attendee Data Table - TIER 3 */
        .attendee-section {
            margin: 0 16px 16px 40px;
            padding: 16px;
            background: linear-gradient(135deg, #fdf4ff 0%, #faf5ff 100%);
            border-radius: 10px;
            border: 2px solid #e9d5ff;
            border-left: 4px solid #a855f7;
            position: relative;
        }

        .attendee-section::before {
            position: absolute;
            top: -10px;
            left: 16px;
            font-size: 9px;
            font-weight: 800;
            padding: 3px 10px;
            background: linear-gradient(135deg, #a855f7, #9333ea);
            color: white;
            border-radius: 4px;
            letter-spacing: 0.5px;
        }

        .attendee-data-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border: 1px solid #e9d5ff;
            border-radius: 8px;
            overflow: hidden;
            margin-top: 8px;
        }

        .attendee-data-table thead {
            background: linear-gradient(135deg, #f3e8ff 0%, #ede9fe 100%);
        }

        .attendee-data-table th {
            padding: 12px 14px;
            text-align: left;
            font-size: 11px;
            font-weight: 700;
            color: #7c3aed;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border-bottom: 2px solid #e9d5ff;
        }

        .attendee-data-table td {
            padding: 12px 14px;
            font-size: 13px;
            color: #334155;
            border-bottom: 1px solid #f3e8ff;
        }

        .attendee-data-table tbody tr:last-child td {
            border-bottom: none;
        }

        .attendee-data-table tbody tr:hover {
            background: #faf5ff;
        }

        .attendee-data-table td.no-data {
            text-align: center;
            color: #a855f7;
            font-style: italic;
            padding: 24px;
        }

        .pdf-btn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .pdf-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
        }

        /* Empty States */
        .no-bookings,
        .no-attendees {
            padding: 24px;
            text-align: center;
            color: #94a3b8;
            font-size: 13px;
            background: white;
            border-radius: 0 0 8px 8px;
        }

        .no-sessions {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 16px;
            padding: 60px 24px;
            color: #94a3b8;
            text-align: center;
        }

        .no-sessions p {
            margin: 0;
            font-size: 14px;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .table-header,
            .table-row {
                grid-template-columns: 1fr 1.2fr 1.2fr 1fr 50px;
            }

            .table-header .th:nth-child(2),
            .table-header .th:nth-child(3),
            .table-row .td:nth-child(2),
            .table-row .td:nth-child(3),
            .table-row .td:nth-child(7) {
                display: none;
            }
        }

        @media (max-width: 768px) {
            .header-row {
                flex-direction: column;
                gap: 20px;
            }

            .session-row {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .session-slots {
                flex-direction: row;
            }

            .table-header,
            .table-row {
                grid-template-columns: 1fr 1.2fr 1fr 50px;
            }
        }
        }
    </style>
</x-filament-panels::page>
