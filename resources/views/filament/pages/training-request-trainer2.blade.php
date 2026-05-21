<x-filament-panels::page>
    <div class="training-request-container">
        {{-- Filter Section: Year and Date --}}
        <div class="selection-header">
            <div class="selection-card filter-card">
                <div class="selection-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
                </div>
                <div class="selection-content">
                    <span class="selection-step">Filter</span>
                    <h3 class="selection-title">Session Filter</h3>
                    <div class="filter-row">
                        <div class="filter-group">
                            <label class="filter-group-label">Year</label>
                            <select wire:model.live="selectedYear" class="year-dropdown">
                                @foreach($years as $year)
                                    <option value="{{ $year }}">{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="filter-group">
                            <label class="filter-group-label">Date</label>
                            <div class="date-filter-wrapper" wire:ignore
                                 x-data="{
                                     picker: null,
                                     hasSelection: false,
                                     init() {
                                         const self = this;
                                         this.hasSelection = @js($filterSessionDate ? true : false);
                                         this.picker = flatpickr(this.$refs.datepicker, {
                                             dateFormat: 'Y-m-d',
                                             altInput: true,
                                             altFormat: 'd M Y (D)',
                                             allowInput: false,
                                             defaultDate: @js($filterSessionDate),
                                             locale: { firstDayOfWeek: 1 },
                                             disable: [
                                                 function(date) {
                                                     return (date.getDay() === 0 || date.getDay() === 6);
                                                 }
                                             ],
                                             onChange: (selectedDates, dateStr) => {
                                                 self.hasSelection = !!dateStr;
                                                 $wire.set('filterSessionDate', dateStr || null);
                                             }
                                         });
                                     },
                                     clearFilter() {
                                         this.picker.clear();
                                         this.hasSelection = false;
                                         $wire.set('filterSessionDate', null);
                                     }
                                 }"
                                 x-init="init()">
                                <input type="text" x-ref="datepicker" class="date-filter-input" placeholder="All Dates">
                                <button type="button" class="clear-date-btn" x-show="hasSelection" x-on:click="clearFilter()" title="Clear filter">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- STEP 2: Training Sessions Display --}}
        @if($showSessions)
            <div class="sessions-container">
                <div class="sessions-header">
                    <div class="sessions-header-content">
                        <h2 class="sessions-title">Training Sessions</h2>
                        <p class="sessions-subtitle">Step 2: Select a session to view details or add training requests</p>
                    </div>
                    <div class="legend-compact">
                        <div class="legend-item">
                            <span class="legend-dot past"></span>
                            <span>Past</span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-dot available"></span>
                            <span>Available</span>
                        </div>
                    </div>
                </div>

                <div class="sessions-grid">
                    @foreach($this->trainingSessions as $sessionData)
                        @php $session = $sessionData['session']; @endphp
                        <div class="session-card status-{{ $sessionData['status'] }} {{ $sessionData['is_expanded'] ? 'expanded' : '' }}">
                            <div class="session-header" wire:click="toggleSession({{ $session->id }})">
                                <div class="session-main">
                                    <div class="session-badge {{ $sessionData['status'] }}">
                                        {{ $session->session_number }}
                                    </div>
                                    <div class="session-info">
                                        <div class="session-dates-inline">
                                            <div class="date-chip">
                                                <span class="date-label">D1</span>
                                                <span class="date-value">{{ \Carbon\Carbon::parse($session->day1_date)->format('d M') }}</span>
                                            </div>
                                            <div class="date-chip">
                                                <span class="date-label">D2</span>
                                                <span class="date-value">{{ \Carbon\Carbon::parse($session->day2_date)->format('d M') }}</span>
                                            </div>
                                            <div class="date-chip">
                                                <span class="date-label">D3</span>
                                                <span class="date-value">{{ \Carbon\Carbon::parse($session->day3_date)->format('d M') }}</span>
                                            </div>
                                        </div>
                                        <div class="session-category-badge">
                                            {{ $sessionData['training_category'] }}
                                        </div>
                                        @if($sessionData['status'] === 'current_week')
                                            <div class="session-week-badge current">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                                This Week
                                            </div>
                                        @elseif($sessionData['status'] === 'past')
                                            <div class="session-week-badge past">
                                                Completed
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="session-stats">
                                    @if($sessionData['training_category'] === 'HRDF_WEBINAR')
                                        <div class="stat-item combined">
                                            <span class="stat-value">{{ $sessionData['hrdf_count'] + $sessionData['webinar_count'] }}/150</span>
                                            <span class="stat-label">Combined</span>
                                        </div>
                                        <div class="stat-item hrdf">
                                            <span class="stat-value">{{ $sessionData['hrdf_count'] }}/{{ $sessionData['hrdf_limit'] }}</span>
                                            <span class="stat-label">HRDF</span>
                                        </div>
                                        <div class="stat-item webinar">
                                            <span class="stat-value">{{ $sessionData['webinar_count'] }}/{{ $sessionData['webinar_limit'] }}</span>
                                            <span class="stat-label">Webinar</span>
                                        </div>
                                    @elseif($sessionData['training_category'] === 'HRDF')
                                        <div class="stat-item hrdf">
                                            <span class="stat-value">{{ $sessionData['hrdf_count'] }}/{{ $sessionData['hrdf_limit'] }}</span>
                                            <span class="stat-label">HRDF Slot</span>
                                        </div>
                                    @elseif($sessionData['training_category'] === 'WEBINAR')
                                        <div class="stat-item webinar">
                                            <span class="stat-value">{{ $sessionData['webinar_count'] }}/{{ $sessionData['webinar_limit'] }}</span>
                                            <span class="stat-label">Webinar Slot</span>
                                        </div>
                                    @endif
                                </div>

                                <div class="session-actions">
                                    @if(in_array($sessionData['status'], ['current_week', 'future']) && $session->status === 'SCHEDULED')
                                        <button wire:click.stop="showAddRequestModal({{ $session->id }})" class="btn-add-request">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                                            Add Request
                                        </button>
                                    @elseif(in_array($sessionData['status'], ['current_week', 'future']) && $session->status !== 'SCHEDULED')
                                        <div class="warning-badge">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                                            Missing Links
                                        </div>
                                    @endif

                                    <button class="expand-btn {{ $sessionData['is_expanded'] ? 'expanded' : '' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                    </button>
                                </div>
                            </div>

                            {{-- STEP 3: Expanded Session Details --}}
                            @if($sessionData['is_expanded'])
                                <div class="session-details">
                                    @php $bookings = $this->getSessionBookings($session->id); @endphp

                                    {{-- Show HRDF bookings only for HRDF or HRDF_WEBINAR sessions --}}
                                    @if(in_array($sessionData['training_category'], ['HRDF', 'HRDF_WEBINAR']) && $bookings->has('HRDF'))
                                        <div class="booking-section">
                                            <div class="booking-section-header">
                                                <div class="section-icon hrdf">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                                                </div>
                                                <h5 class="section-title">HRDF Training Requests</h5>
                                                <span class="section-count">{{ $bookings['HRDF']->count() }} booking(s)</span>
                                            </div>
                                            <div class="booking-list">
                                                @foreach($bookings['HRDF'] as $booking)
                                                    <div class="booking-card {{ $this->isBookingExpanded($booking->id) ? 'expanded' : '' }}">
                                                        <div class="booking-row" wire:click="toggleBooking({{ $booking->id }})">
                                                            <div class="booking-cell id-cell">
                                                                <span class="cell-label">Handover ID</span>
                                                                <span class="cell-value handover-id">{{ $booking->handover_id }}</span>
                                                            </div>
                                                            <div class="booking-cell">
                                                                <span class="cell-label">Company</span>
                                                                <span class="cell-value company">{{ $booking->company_name }}</span>
                                                            </div>
                                                            {{-- <div class="booking-cell">
                                                                <span class="cell-label">HRDF Grant ID</span>
                                                                @php
                                                                    $hrdfClaim = $booking->hrdfClaim();
                                                                @endphp
                                                                @if($hrdfClaim && $hrdfClaim->hrdf_grant_id)
                                                                    <span class="cell-value hrdf-grant-id" style="color: #6366F1; font-weight: 600; font-size: 0.8rem;">
                                                                        {{ $hrdfClaim->hrdf_grant_id }}
                                                                    </span>
                                                                @else
                                                                    <span class="cell-value" style="color: #9CA3AF; font-style: italic;">-</span>
                                                                @endif
                                                            </div> --}}
                                                            <div class="booking-cell">
                                                                <span class="cell-label">Submitted By</span>
                                                                <span class="cell-value">{{ $booking->submitted_by }}</span>
                                                            </div>
                                                            <div class="booking-cell">
                                                                <span class="cell-label">Date & Time</span>
                                                                <span class="cell-value">{{ $booking->submitted_at->format('d M Y, h:i A') }}</span>
                                                            </div>
                                                            <div class="booking-cell">
                                                                <span class="cell-label">Participants</span>
                                                                <span class="cell-value participant-count">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                                                                    {{ max($booking->attendees->count(), $booking->expected_attendees ?? 0) }}
                                                                </span>
                                                            </div>
                                                            <div class="booking-cell">
                                                                <span class="cell-label">Status</span>
                                                                <span class="status-badge status-{{ strtolower($booking->status) }}">{{ $booking->status }}</span>
                                                            </div>
                                                            <div class="booking-cell actions-cell">
                                                                <div class="action-buttons" wire:click.stop>
                                                                    @if($booking->status === 'BOOKING')
                                                                        <button wire:click="openApplyModal({{ $booking->id }})" class="action-btn apply">
                                                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                                                            Apply
                                                                        </button>
                                                                    @endif
                                                                    @if(auth()->user()->role_id == 3 && $booking->status === 'APPLY')
                                                                        <button wire:click="openApproveModal({{ $booking->id }})" class="action-btn approve">
                                                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                                                            Approve
                                                                        </button>
                                                                    @endif
                                                                    @if($booking->submitted_by === auth()->user()->name || auth()->user()->role_id == 3)
                                                                        <button wire:click="openCancelModal({{ $booking->id }})" class="action-btn cancel">
                                                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                                                            Cancel
                                                                        </button>
                                                                    @endif
                                                                </div>
                                                                <button class="expand-attendees-btn {{ $this->isBookingExpanded($booking->id) ? 'expanded' : '' }}">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                                                </button>
                                                            </div>
                                                        </div>
                                                        {{-- Expandable Attendee Details --}}
                                                        @if($this->isBookingExpanded($booking->id))
                                                            <div class="attendee-details">
                                                                <div class="attendee-header-row">
                                                                    <h6 class="attendee-section-title">
                                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                                                                        Attendee Details
                                                                    </h6>
                                                                    <span class="attendee-count-badge">{{ $booking->attendees->count() }} registered</span>
                                                                </div>
                                                                @if($booking->attendees->count() > 0)
                                                                    <div class="attendee-grid">
                                                                        @foreach($booking->attendees as $index => $attendee)
                                                                            <div class="attendee-item">
                                                                                <div class="attendee-avatar">{{ substr($attendee->name, 0, 1) }}</div>
                                                                                <div class="attendee-info">
                                                                                    <span class="attendee-name">{{ $attendee->name }}</span>
                                                                                    <span class="attendee-email">{{ $attendee->email }}</span>
                                                                                    @if($attendee->phone)
                                                                                        <span class="attendee-phone">{{ $attendee->phone }}</span>
                                                                                    @endif
                                                                                </div>
                                                                                <div class="attendee-status {{ strtolower($attendee->attendance_status ?? 'registered') }}">
                                                                                    {{ $attendee->attendance_status ?? 'REGISTERED' }}
                                                                                </div>
                                                                            </div>
                                                                        @endforeach
                                                                    </div>
                                                                @else
                                                                    <div class="no-attendees">
                                                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                                                                        <p>No attendee details added yet. Expected: {{ $booking->expected_attendees ?? 0 }} participant(s)</p>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Show WEBINAR bookings only for WEBINAR or HRDF_WEBINAR sessions --}}
                                    @if(in_array($sessionData['training_category'], ['WEBINAR', 'HRDF_WEBINAR']) && $bookings->has('WEBINAR'))
                                        <div class="booking-section">
                                            <div class="booking-section-header">
                                                <div class="section-icon webinar">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                                                </div>
                                                <h5 class="section-title">Webinar Training Requests</h5>
                                                <span class="section-count">{{ $bookings['WEBINAR']->count() }} booking(s)</span>
                                            </div>
                                            <div class="booking-list">
                                                @foreach($bookings['WEBINAR'] as $booking)
                                                    <div class="booking-card {{ $this->isBookingExpanded($booking->id) ? 'expanded' : '' }}">
                                                        <div class="booking-row" wire:click="toggleBooking({{ $booking->id }})">
                                                            <div class="booking-cell id-cell">
                                                                <span class="cell-label">Handover ID</span>
                                                                <span class="cell-value handover-id">{{ $booking->handover_id }}</span>
                                                            </div>
                                                            <div class="booking-cell">
                                                                <span class="cell-label">Company</span>
                                                                <span class="cell-value company">{{ $booking->company_name }}</span>
                                                            </div>
                                                            <div class="booking-cell">
                                                                <span class="cell-label">Submitted By</span>
                                                                <span class="cell-value">{{ $booking->submitted_by }}</span>
                                                            </div>
                                                            <div class="booking-cell">
                                                                <span class="cell-label">Date & Time</span>
                                                                <span class="cell-value">{{ $booking->submitted_at->format('d M Y, h:i A') }}</span>
                                                            </div>
                                                            <div class="booking-cell">
                                                                <span class="cell-label">Participants</span>
                                                                <span class="cell-value participant-count">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                                                                    {{ max($booking->attendees->count(), $booking->expected_attendees ?? 0) }}
                                                                </span>
                                                            </div>
                                                            <div class="booking-cell">
                                                                <span class="cell-label">Status</span>
                                                                <span class="status-badge status-{{ strtolower($booking->status) }}">{{ $booking->status }}</span>
                                                            </div>
                                                            <div class="booking-cell actions-cell">
                                                                <div class="action-buttons" wire:click.stop>
                                                                    @if($booking->status === 'BOOKING')
                                                                        <button wire:click="openApplyModal({{ $booking->id }})" class="action-btn apply">
                                                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                                                            Apply
                                                                        </button>
                                                                    @endif
                                                                    @if(auth()->user()->role_id == 3 && $booking->status === 'APPLY')
                                                                        <button wire:click="openApproveModal({{ $booking->id }})" class="action-btn approve">
                                                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                                                            Approve
                                                                        </button>
                                                                    @endif
                                                                    @if($booking->submitted_by === auth()->user()->name || auth()->user()->role_id == 3)
                                                                        <button wire:click="openCancelModal({{ $booking->id }})" class="action-btn cancel">
                                                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                                                            Cancel
                                                                        </button>
                                                                    @endif
                                                                </div>
                                                                <button class="expand-attendees-btn {{ $this->isBookingExpanded($booking->id) ? 'expanded' : '' }}">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                                                </button>
                                                            </div>
                                                        </div>
                                                        {{-- Expandable Attendee Details --}}
                                                        @if($this->isBookingExpanded($booking->id))
                                                            <div class="attendee-details">
                                                                <div class="attendee-header-row">
                                                                    <h6 class="attendee-section-title">
                                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                                                                        Attendee Details
                                                                    </h6>
                                                                    <span class="attendee-count-badge">{{ $booking->attendees->count() }} registered</span>
                                                                </div>
                                                                @if($booking->attendees->count() > 0)
                                                                    <div class="attendee-grid">
                                                                        @foreach($booking->attendees as $index => $attendee)
                                                                            <div class="attendee-item">
                                                                                <div class="attendee-avatar">{{ substr($attendee->name, 0, 1) }}</div>
                                                                                <div class="attendee-info">
                                                                                    <span class="attendee-name">{{ $attendee->name }}</span>
                                                                                    <span class="attendee-email">{{ $attendee->email }}</span>
                                                                                    @if($attendee->phone)
                                                                                        <span class="attendee-phone">{{ $attendee->phone }}</span>
                                                                                    @endif
                                                                                </div>
                                                                                <div class="attendee-status {{ strtolower($attendee->attendance_status ?? 'registered') }}">
                                                                                    {{ $attendee->attendance_status ?? 'REGISTERED' }}
                                                                                </div>
                                                                            </div>
                                                                        @endforeach
                                                                    </div>
                                                                @else
                                                                    <div class="no-attendees">
                                                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                                                                        <p>No attendee details added yet. Expected: {{ $booking->expected_attendees ?? 0 }} participant(s)</p>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    @if(!$bookings->count())
                                        <div class="no-bookings">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                                            <p>No training requests for this session yet</p>
                                            @if(in_array($sessionData['status'], ['current_week', 'future']) && $session->status === 'SCHEDULED')
                                                <button wire:click="showAddRequestModal({{ $session->id }})" class="btn-add-first">
                                                    Be the first to add a request
                                                </button>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- STEP 5-10: Add Training Request Modal --}}
        @if($showRequestModal)
            <div class="modal-overlay" wire:click.self="closeRequestModal">
                <div class="modal-container request-modal">
                    <div class="modal-header">
                        <div class="modal-header-content">
                            <h3>Add Training Request</h3>
                            <p class="modal-subtitle">Fill in the details below to submit a new training request</p>
                        </div>
                        <button wire:click="closeRequestModal" class="modal-close">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                        </button>
                    </div>

                    <div class="modal-body">
                        {{-- STEP 6: Choose Training Type --}}
                        @if(!$selectedTrainingType)
                            <div class="step-section">
                                <h4 class="step-title">Select Training Type</h4>
                                <div class="training-type-grid">
                                    @foreach($trainingTypes as $key => $label)
                                        @if(
                                            ($selectedSessionCategory === 'HRDF_WEBINAR') ||
                                            ($selectedSessionCategory === 'HRDF' && $key === 'HRDF') ||
                                            ($selectedSessionCategory === 'WEBINAR' && $key === 'WEBINAR')
                                        )
                                            <button wire:click="selectTrainingType('{{ $key }}')" class="training-type-card {{ $key === 'HRDF' ? 'hrdf' : 'webinar' }}">
                                                <div class="type-icon">
                                                    @if($key === 'HRDF')
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                                                    @else
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                                                    @endif
                                                </div>
                                                <span class="type-label">{{ $label }}</span>
                                                <span class="type-description">{{ $key === 'HRDF' ? 'HRDF Claimable Training' : 'Free Online Webinar' }}</span>
                                            </button>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @else
                            {{-- STEP 7-9: Training Request Form --}}
                            <div class="step-section">
                                <div class="selected-type-header">
                                    <div class="selected-type-badge {{ $selectedTrainingType === 'HRDF' ? 'hrdf' : 'webinar' }}">
                                        @if($selectedTrainingType === 'HRDF')
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                                        @else
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                                        @endif
                                        {{ $trainingTypes[$selectedTrainingType] }}
                                    </div>
                                    <button wire:click="$set('selectedTrainingType', '')" class="change-type-btn">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                        Change
                                    </button>
                                </div>

                                {{-- Common Fields --}}
                                <div class="form-grid form-grid-3">
                                    {{-- Row 1: Status (span 1), Company (span 2) --}}
                                    <div class="form-group">
                                        <label class="form-label">Status</label>
                                        <select wire:model.live="hrdfStatus" class="form-select">
                                            @foreach($hrdfStatuses as $key => $label)
                                                <option value="{{ $key }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-span-2 form-group">
                                        <label class="form-label">Company / Lead ID <span class="required">*</span></label>
                                        <div class="search-input-wrapper">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                            <input type="text" wire:model.live.debounce.500ms="companySearchTerm"
                                                   placeholder="Search..."
                                                   class="form-input search-input">
                                            @if($companySearchTerm && !$selectedLeadId)
                                                <div class="search-results">
                                                    @foreach($this->searchCompanies() as $lead)
                                                        <div wire:click="selectLead({{ $lead->id }})" class="search-result-item">
                                                            <span class="lead-id">{{ str_pad($lead->id, 5, '0', STR_PAD_LEFT) }}</span>
                                                            <span class="company-name">{{ $lead->companyDetail->company_name ?? 'No Company' }}</span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Row 2: Training Category (span 1), HRDF Grant ID (span 2) --}}
                                    <div class="form-group">
                                        <label class="form-label">Training Category <span class="required">*</span></label>
                                        <select wire:model="trainingCategory" class="form-select">
                                            <option value="">Select Category</option>
                                            @foreach($trainingCategories as $key => $label)
                                                <option value="{{ $key }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- HRDF Grant ID Search - Only show for HRDF training type --}}
                                    @if($selectedTrainingType === 'HRDF')
                                        <div class="col-span-2 form-group">
                                            <label class="form-label">HRDF Grant ID</label>
                                            <div class="search-input-wrapper">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                                                <input type="text" wire:model.live.debounce.500ms="hrdfGrantSearchTerm"
                                                       placeholder="Search by Grant ID or Company..."
                                                       class="form-input search-input"
                                                       @if($selectedHrdfClaimId) readonly @endif>
                                                @if($selectedHrdfClaimId)
                                                    <button wire:click="clearHrdfClaim" type="button" class="clear-search-btn" title="Clear selection">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                                    </button>
                                                @endif
                                                @if($hrdfGrantSearchTerm && !$selectedHrdfClaimId)
                                                    <div class="search-results">
                                                        @forelse($this->searchHrdfClaims() as $claim)
                                                            <div wire:click="selectHrdfClaim({{ $claim->id }})" class="search-result-item">
                                                                <span class="lead-id" style="background: #6366F1; color: white;">{{ $claim->hrdf_grant_id }}</span>
                                                                <span class="company-name">{{ $claim->company_name }}</span>
                                                            </div>
                                                        @empty
                                                            <div class="search-result-item no-results">
                                                                <span style="color: #9CA3AF; font-style: italic;">No matching HRDF claims found</span>
                                                            </div>
                                                        @endforelse
                                                    </div>
                                                @endif
                                            </div>
                                            <small class="form-hint" style="color: #6B7280; font-size: 0.75rem; margin-top: 4px; display: block;">Optional - Link to an existing HRDF claim</small>
                                        </div>
                                    @endif
                                </div>

                                {{-- Attendees Section --}}
                                @php
                                    $attendeesOptional = ($hrdfStatus === 'BOOKING');
                                @endphp

                                <div class="attendees-section {{ $attendeesOptional ? 'attendees-optional' : '' }}">
                                    <div class="attendees-header">
                                        <h5 class="attendees-title">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                                            Training Attendees
                                            @if(!$attendeesOptional)
                                                <span class="required">*</span>
                                            @else
                                                <span class="optional-badge">(Optional)</span>
                                            @endif
                                        </h5>
                                        <button wire:click="addAttendee" type="button" class="btn-add-attendee">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                                            Add Attendee
                                        </button>
                                    </div>

                                    @if($attendeesOptional)
                                        <div class="attendees-note">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                                            Attendee details are optional for "Booking" status. You can add them later using the Apply button.
                                        </div>

                                        <div class="form-group participant-count-section">
                                            <label class="form-label">Number of Participants <span class="required">*</span></label>
                                            <input type="number"
                                                   wire:model="hrdfParticipantCount"
                                                   class="form-input participant-count-input"
                                                   min="1"
                                                   max="{{ $selectedTrainingType === 'HRDF' ? 50 : 100 }}"
                                                   placeholder="Enter number">
                                            <small class="form-hint">Expected participants (1-{{ $selectedTrainingType === 'HRDF' ? 50 : 100 }})</small>
                                        </div>
                                    @endif

                                    @foreach($attendees as $index => $attendee)
                                        <div class="attendee-card">
                                            <div class="attendee-card-header">
                                                <span class="attendee-number">Attendee {{ $index + 1 }}</span>
                                                @if(count($attendees) > 1)
                                                    <button wire:click="removeAttendee({{ $index }})" type="button" class="btn-remove-attendee">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                                    </button>
                                                @endif
                                            </div>

                                            <div class="attendee-form-grid">
                                                <div class="form-group">
                                                    <label class="form-label">Full Name @if(!$attendeesOptional)<span class="required">*</span>@endif</label>
                                                    <input type="text" wire:model="attendees.{{ $index }}.name" class="form-input uppercase-input" placeholder="Enter name" oninput="this.value = this.value.toUpperCase()">
                                                </div>

                                                <div class="form-group">
                                                    <label class="form-label">Email Address @if(!$attendeesOptional)<span class="required">*</span>@endif</label>
                                                    <input type="email" wire:model="attendees.{{ $index }}.email" class="form-input" placeholder="Enter email">
                                                </div>

                                                <div class="form-group">
                                                    <label class="form-label">Phone Number</label>
                                                    <input type="text" wire:model="attendees.{{ $index }}.phone" class="form-input" placeholder="Enter phone">
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="modal-footer">
                        @if($errors->any())
                            <div class="validation-errors">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                                <ul>
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="modal-footer-buttons">
                            <button wire:click="closeRequestModal" class="btn btn-secondary">Cancel</button>
                            @if($selectedTrainingType)
                                <button wire:click="submitRequest" class="btn btn-primary">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                    Submit Request
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Apply Modal --}}
        @if($showApplyModal)
            <div class="modal-overlay" wire:click.self="closeApplyModal">
                <div class="modal-container request-modal">
                    <div class="modal-header">
                        <div class="modal-header-content">
                            <h3>Apply - Add Attendee Details</h3>
                            <p class="modal-subtitle">Complete the attendee information to submit the application</p>
                        </div>
                        <button wire:click="closeApplyModal" class="modal-close">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                        </button>
                    </div>

                    <div class="modal-body">
                        @if($applyBooking)
                            <div class="apply-booking-info">
                                <div class="info-grid">
                                    <div class="info-item">
                                        <span class="info-label">Handover ID</span>
                                        <span class="info-value highlight">{{ $applyBooking->handover_id }}</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Company</span>
                                        <span class="info-value">{{ $applyBooking->company_name }}</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Expected Participants</span>
                                        <span class="info-value">{{ $applyBooking->expected_attendees ?? 'N/A' }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="attendees-section">
                                <div class="attendees-header">
                                    <h5 class="attendees-title">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                                        Training Attendees <span class="required">*</span>
                                    </h5>
                                    @php
                                        $expectedCount = $applyBooking->expected_attendees ?? 999;
                                        $canAddMore = count($applyAttendees) < $expectedCount;
                                    @endphp
                                    <button wire:click="addApplyAttendee"
                                            type="button"
                                            class="btn-add-attendee {{ !$canAddMore ? 'btn-disabled' : '' }}"
                                            {{ !$canAddMore ? 'disabled' : '' }}>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                                        Add ({{ count($applyAttendees) }}/{{ $expectedCount }})
                                    </button>
                                </div>

                                @foreach($applyAttendees as $index => $attendee)
                                    <div class="attendee-card">
                                        <div class="attendee-card-header">
                                            <span class="attendee-number">Attendee {{ $index + 1 }}</span>
                                            @if(count($applyAttendees) > 1)
                                                <button wire:click="removeApplyAttendee({{ $index }})" type="button" class="btn-remove-attendee">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                                </button>
                                            @endif
                                        </div>

                                        <div class="attendee-form-grid">
                                            <div class="form-group">
                                                <label class="form-label">Full Name <span class="required">*</span></label>
                                                <input type="text" wire:model="applyAttendees.{{ $index }}.name" class="form-input uppercase-input" placeholder="Enter name" oninput="this.value = this.value.toUpperCase()">
                                            </div>

                                            <div class="form-group">
                                                <label class="form-label">Email Address <span class="required">*</span></label>
                                                <input type="email" wire:model="applyAttendees.{{ $index }}.email" class="form-input" placeholder="Enter email">
                                            </div>

                                            <div class="form-group">
                                                <label class="form-label">Phone Number</label>
                                                <input type="text" wire:model="applyAttendees.{{ $index }}.phone" class="form-input" placeholder="Enter phone">
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="modal-footer">
                        @if($errors->any())
                            <div class="validation-errors">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                                <ul>
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="modal-footer-buttons">
                            <button wire:click="closeApplyModal" class="btn btn-secondary">Cancel</button>
                            <button wire:click="submitApply" class="btn btn-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                Submit Apply
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Approve Confirmation Modal --}}
        @if($showApproveModal)
            <div class="modal-overlay" wire:click.self="closeApproveModal">
                <div class="modal-container confirm-modal">
                    <div class="modal-header confirm-header">
                        <div class="confirm-icon approve">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        </div>
                    </div>

                    <div class="modal-body confirm-body">
                        @if($approveBooking)
                            <h3 class="confirm-title">Confirm Approval</h3>
                            <p class="confirm-message">Are you sure you want to approve this training request?</p>
                            <p class="confirm-note">Email notifications will be sent to all attendees upon approval.</p>
                        @endif
                    </div>

                    <div class="modal-footer confirm-footer">
                        <button wire:click="closeApproveModal" class="btn btn-secondary">Cancel</button>
                        <button wire:click="confirmApprove" class="btn btn-success">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            Approve
                        </button>
                    </div>
                </div>
            </div>
        @endif

        {{-- Cancel Confirmation Modal --}}
        @if($showCancelModal)
            <div class="modal-overlay" wire:click.self="closeCancelModal">
                <div class="modal-container confirm-modal">
                    <div class="modal-header confirm-header">
                        <div class="confirm-icon cancel">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                        </div>
                    </div>

                    <div class="modal-body confirm-body">
                        @if($cancelBooking)
                            <h3 class="confirm-title">CONFIRM CANCELLATION</h3>
                            <p class="confirm-message">ARE YOU SURE YOU WANT TO CANCEL THIS TRAINING REQUEST?</p>
                            <div class="cancel-booking-info">
                                <div class="info-row">
                                    <span class="info-label">HANDOVER ID:</span>
                                    <span class="info-value">{{ $cancelBooking->handover_id }}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">COMPANY:</span>
                                    <span class="info-value">{{ strtoupper($cancelBooking->company_name) }}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">TRAINING TYPE:</span>
                                    <span class="info-value">{{ $cancelBooking->training_type }}</span>
                                </div>
                            </div>
                            <div class="cancel-reason-section">
                                <label class="cancel-reason-label">CANCEL REASON <span class="required">*</span></label>
                                <textarea
                                    wire:model="cancelReason"
                                    class="cancel-reason-input"
                                    placeholder="PLEASE PROVIDE A REASON FOR CANCELLATION..."
                                    rows="3"
                                    style="text-transform: uppercase;"
                                ></textarea>
                            </div>
                            <p class="confirm-warning">THIS ACTION CANNOT BE UNDONE.</p>
                        @endif
                    </div>

                    <div class="modal-footer confirm-footer">
                        <button wire:click="closeCancelModal" class="btn btn-secondary">Keep It</button>
                        <button wire:click="confirmCancel" class="btn btn-danger">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                            Cancel Request
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Flatpickr CSS --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <style>
        /* ===== Base Styles ===== */
        .training-request-container {
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

        /* ===== Filter Styles ===== */
        .filter-row {
            display: flex;
            gap: 20px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-group + .filter-group {
            padding-left: 20px;
            border-left: 1px solid #e2e8f0;
        }

        .filter-group-label {
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .year-dropdown {
            padding: 8px 32px 8px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            color: #334155;
            background: white;
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
            transition: all 0.2s ease;
            min-width: 90px;
        }

        .year-dropdown:hover {
            border-color: #93c5fd;
            background-color: #f8fafc;
        }

        .year-dropdown:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        /* ===== Date Filter ===== */
        .date-filter-wrapper {
            position: relative;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .date-filter-input {
            padding: 7px 14px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            color: #334155;
            background: white;
            min-width: 160px;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 1px 2px rgba(0,0,0,0.04);
        }

        .date-filter-input:hover {
            border-color: #93c5fd;
            background: #eff6ff;
        }

        .date-filter-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .clear-date-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border: none;
            background: #fee2e2;
            color: #dc2626;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .clear-date-btn:hover {
            background: #fecaca;
            transform: scale(1.05);
        }

        /* ===== Selection Header ===== */
        .selection-header {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 24px;
        }

        .selection-card {
            display: flex;
            gap: 16px;
            padding: 24px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05), 0 1px 2px rgba(0,0,0,0.1);
            border: 1px solid #e2e8f0;
            transition: all 0.2s ease;
        }

        .selection-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }

        .selection-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            border-radius: 12px;
            color: white;
            flex-shrink: 0;
        }

        .selection-content {
            flex: 1;
        }

        .selection-step {
            font-size: 11px;
            font-weight: 600;
            color: #6366f1;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .selection-title {
            font-size: 16px;
            font-weight: 700;
            color: #1e293b;
            margin: 4px 0 12px 0;
        }

        .trainer-selection,
        .year-selection {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .option-pill {
            display: inline-flex;
            align-items: center;
            padding: 10px 18px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
            background: white;
        }

        .option-pill:hover {
            border-color: #6366f1;
            background: #f5f3ff;
        }

        .option-pill.selected {
            border-color: #6366f1;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
        }

        .pill-text {
            font-size: 14px;
            font-weight: 600;
        }

        /* ===== Sessions Container ===== */
        .sessions-container {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05), 0 1px 2px rgba(0,0,0,0.1);
            border: 1px solid #e2e8f0;
        }

        .sessions-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 24px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e2e8f0;
        }

        .sessions-header-content {
            flex: 1;
        }

        .sessions-title {
            font-size: 22px;
            font-weight: 700;
            color: #1e293b;
            margin: 0 0 4px 0;
        }

        .sessions-subtitle {
            font-size: 14px;
            color: #64748b;
            margin: 0;
        }

        .legend-compact {
            display: flex;
            gap: 16px;
            background: #f8fafc;
            padding: 10px 16px;
            border-radius: 8px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #64748b;
        }

        .legend-dot {
            width: 12px;
            height: 12px;
            border-radius: 4px;
        }

        .legend-dot.past {
            background: linear-gradient(135deg, #94a3b8, #64748b);
        }

        .legend-dot.available {
            background: linear-gradient(135deg, #22c55e, #16a34a);
        }

        /* ===== Sessions Grid ===== */
        .sessions-grid {
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

        /* Keep status-available for backwards compatibility */
        .session-card.status-available {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border-left: 4px solid #22c55e;
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
            flex: 1;
        }

        .session-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 80px;
            padding: 10px 16px;
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

        /* Keep available for backwards compatibility */
        .session-badge.available {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
        }

        .session-info {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .session-dates-inline {
            display: flex;
            gap: 8px;
        }

        .date-chip {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background: white;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .date-label {
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
        }

        .date-value {
            font-size: 13px;
            font-weight: 600;
            color: #1e293b;
        }

        .session-category-badge {
            display: inline-flex;
            padding: 4px 10px;
            background: #e0e7ff;
            color: #4338ca;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
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

        .session-stats {
            display: flex;
            gap: 16px;
        }

        .stat-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 10px 16px;
            background: white;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            min-width: 90px;
        }

        .stat-item.combined {
            border-color: #c7d2fe;
            background: #eef2ff;
        }

        .stat-item.hrdf {
            border-color: #fde68a;
            background: #fefce8;
        }

        .stat-item.webinar {
            border-color: #a7f3d0;
            background: #ecfdf5;
        }

        .stat-value {
            font-size: 16px;
            font-weight: 700;
            color: #1e293b;
        }

        .stat-label {
            font-size: 11px;
            color: #64748b;
            font-weight: 500;
        }

        .session-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn-add-request {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-add-request:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
        }

        .warning-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            background: #fef3c7;
            color: #92400e;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            border: 1px solid #fcd34d;
        }

        .expand-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            color: #64748b;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .expand-btn:hover {
            background: #f8fafc;
        }

        .expand-btn.expanded svg {
            transform: rotate(180deg);
        }

        .expand-btn svg {
            transition: transform 0.2s ease;
        }

        /* ===== Session Details ===== */
        .session-details {
            border-top: 1px solid #e2e8f0;
            padding: 20px;
            background: rgba(255,255,255,0.8);
        }

        .booking-section {
            margin-bottom: 24px;
        }

        .booking-section:last-child {
            margin-bottom: 0;
        }

        .booking-section-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }

        .section-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 10px;
            color: white;
        }

        .section-icon.hrdf {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }

        .section-icon.webinar {
            background: linear-gradient(135deg, #10b981, #059669);
        }

        .section-title {
            font-size: 15px;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
        }

        .section-count {
            font-size: 12px;
            color: #64748b;
            background: #f1f5f9;
            padding: 4px 10px;
            border-radius: 20px;
        }

        .booking-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .booking-card {
            background: white;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            transition: all 0.2s ease;
        }

        .booking-card:hover {
            border-color: #cbd5e1;
        }

        .booking-card.expanded {
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }

        .booking-row {
            display: grid;
            grid-template-columns: 1fr 1.5fr 1fr 1.2fr 0.8fr 0.8fr auto;
            gap: 16px;
            padding: 16px 20px;
            align-items: center;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .booking-row:hover {
            background: #f8fafc;
        }

        .booking-cell {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .cell-label {
            font-size: 10px;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .cell-value {
            font-size: 13px;
            color: #334155;
            font-weight: 500;
        }

        .cell-value.handover-id {
            font-weight: 700;
            color: #6366f1;
        }

        .cell-value.company {
            font-weight: 600;
            color: #1e293b;
        }

        .cell-value.participant-count {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #6366f1;
        }

        .actions-cell {
            flex-direction: row;
            align-items: center;
            gap: 8px;
        }

        .action-buttons {
            display: flex;
            gap: 6px;
        }

        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .action-btn.apply {
            background: #e0f2fe;
            color: #0369a1;
        }

        .action-btn.apply:hover {
            background: #0ea5e9;
            color: white;
        }

        .action-btn.approve {
            background: #dcfce7;
            color: #166534;
        }

        .action-btn.approve:hover {
            background: #22c55e;
            color: white;
        }

        .action-btn.cancel {
            background: #fee2e2;
            color: #dc2626;
        }

        .action-btn.cancel:hover {
            background: #ef4444;
            color: white;
        }

        .expand-attendees-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            background: #f1f5f9;
            border: none;
            border-radius: 6px;
            color: #64748b;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .expand-attendees-btn:hover {
            background: #e2e8f0;
        }

        .expand-attendees-btn.expanded svg {
            transform: rotate(180deg);
        }

        .expand-attendees-btn svg {
            transition: transform 0.2s ease;
        }

        /* ===== Status Badges ===== */
        .status-badge {
            display: inline-flex;
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .status-badge.status-booking,
        .status-badge.status-booked {
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

        .status-badge.status-cancelled {
            background: #fee2e2;
            color: #dc2626;
        }

        /* ===== Attendee Details (Expandable) ===== */
        .attendee-details {
            background: #f8fafc;
            padding: 20px;
            border-top: 1px solid #e2e8f0;
        }

        .attendee-header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .attendee-section-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 700;
            color: #334155;
            margin: 0;
        }

        .attendee-count-badge {
            font-size: 12px;
            color: #64748b;
            background: white;
            padding: 4px 12px;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
        }

        .attendee-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 12px;
        }

        .attendee-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px;
            background: white;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
        }

        .attendee-avatar {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            border-radius: 10px;
            font-weight: 700;
            font-size: 16px;
            flex-shrink: 0;
        }

        .attendee-info {
            flex: 1;
            min-width: 0;
        }

        .attendee-name {
            display: block;
            font-weight: 600;
            color: #1e293b;
            font-size: 13px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .attendee-email {
            display: block;
            font-size: 12px;
            color: #64748b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .attendee-phone {
            display: block;
            font-size: 11px;
            color: #94a3b8;
        }

        .attendee-status {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .attendee-status.registered {
            background: #dcfce7;
            color: #166534;
        }

        .attendee-status.attended {
            background: #dbeafe;
            color: #1e40af;
        }

        .attendee-status.absent {
            background: #fee2e2;
            color: #dc2626;
        }

        .no-attendees {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            padding: 30px;
            color: #94a3b8;
            text-align: center;
        }

        .no-attendees p {
            margin: 0;
            font-size: 13px;
        }

        .no-bookings {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 16px;
            padding: 48px 24px;
            color: #94a3b8;
            text-align: center;
        }

        .no-bookings p {
            margin: 0;
            font-size: 14px;
        }

        .btn-add-first {
            padding: 10px 20px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-add-first:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
        }

        /* ===== Modal Styles ===== */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 24px;
        }

        .modal-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
            width: 100%;
            max-width: 800px;
            max-height: 90vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .confirm-modal {
            max-width: 420px;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 24px;
            border-bottom: 1px solid #e2e8f0;
        }

        .modal-header-content h3 {
            font-size: 20px;
            font-weight: 700;
            color: #1e293b;
            margin: 0 0 4px 0;
        }

        .modal-subtitle {
            font-size: 13px;
            color: #64748b;
            margin: 0;
        }

        .modal-close {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            background: #f1f5f9;
            border: none;
            border-radius: 10px;
            color: #64748b;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .modal-close:hover {
            background: #e2e8f0;
            color: #334155;
        }

        .modal-body {
            padding: 24px;
            overflow-y: auto;
            flex: 1;
        }

        .modal-footer {
            display: flex;
            flex-direction: column;
            gap: 16px;
            padding: 20px 24px;
            border-top: 1px solid #e2e8f0;
            background: #f8fafc;
        }

        .modal-footer-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }

        /* ===== Training Type Selection ===== */
        .step-section {
            margin-bottom: 24px;
        }

        .step-title {
            font-size: 16px;
            font-weight: 700;
            color: #334155;
            margin: 0 0 16px 0;
        }

        .training-type-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }

        .training-type-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
            padding: 28px 20px;
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .training-type-card:hover {
            border-color: #6366f1;
            background: #f5f3ff;
            transform: translateY(-2px);
        }

        .training-type-card.hrdf:hover {
            border-color: #f59e0b;
            background: #fffbeb;
        }

        .training-type-card.webinar:hover {
            border-color: #10b981;
            background: #ecfdf5;
        }

        .type-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 56px;
            height: 56px;
            background: #f1f5f9;
            border-radius: 14px;
            color: #64748b;
        }

        .training-type-card:hover .type-icon {
            background: currentColor;
            color: white;
        }

        .training-type-card.hrdf:hover .type-icon {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }

        .training-type-card.webinar:hover .type-icon {
            background: linear-gradient(135deg, #10b981, #059669);
        }

        .training-type-card:hover .type-icon svg {
            stroke: white;
        }

        .type-label {
            font-size: 15px;
            font-weight: 700;
            color: #1e293b;
        }

        .type-description {
            font-size: 12px;
            color: #64748b;
            text-align: center;
        }

        .selected-type-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .selected-type-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
        }

        .selected-type-badge.hrdf {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            color: #92400e;
        }

        .selected-type-badge.webinar {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #065f46;
        }

        .change-type-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            background: #f1f5f9;
            border: none;
            border-radius: 8px;
            color: #64748b;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .change-type-btn:hover {
            background: #e2e8f0;
            color: #334155;
        }

        /* ===== Form Styles ===== */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .form-grid.form-grid-3 {
            grid-template-columns: repeat(3, 1fr);
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group.col-span-2 {
            grid-column: span 2;
        }

        .form-label {
            font-size: 13px;
            font-weight: 600;
            color: #334155;
        }

        .form-label .required {
            color: #ef4444;
        }

        .form-input,
        .form-select {
            padding: 12px 14px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.2s ease;
            background: white !important;
            background-image: none !important;
        }

        .form-input:focus,
        .form-select:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .uppercase-input {
            text-transform: uppercase;
        }

        .uppercase-input::placeholder {
            text-transform: none;
        }

        .search-input-wrapper {
            position: relative;
        }

        .search-input-wrapper svg {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }

        .search-input-wrapper .clear-search-btn {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: #fee2e2;
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .search-input-wrapper .clear-search-btn:hover {
            background: #fecaca;
        }

        .search-input-wrapper .clear-search-btn svg {
            position: static;
            transform: none;
            color: #dc2626;
            width: 12px;
            height: 12px;
        }

        .search-input {
            padding-left: 42px;
            width: 100%;
        }

        .search-input[readonly] {
            background: #f0fdf4;
            border-color: #86efac;
            color: #166534;
            padding-right: 40px;
        }

        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 2px solid #e2e8f0;
            border-top: none;
            border-radius: 0 0 10px 10px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 10;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .search-result-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 14px;
            cursor: pointer;
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.2s ease;
        }

        .search-result-item:hover {
            background: #f8fafc;
        }

        .search-result-item:last-child {
            border-bottom: none;
        }

        .lead-id {
            font-weight: 700;
            color: #6366f1;
            font-size: 13px;
        }

        .company-name {
            color: #334155;
            font-size: 13px;
        }

        .search-result-item.no-results {
            cursor: default;
            justify-content: center;
        }

        .search-result-item.no-results:hover {
            background: transparent;
        }

        /* ===== Attendees Section ===== */
        .attendees-section {
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid #e2e8f0;
        }

        .attendees-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .attendees-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 15px;
            font-weight: 700;
            color: #334155;
            margin: 0;
        }

        .optional-badge {
            font-weight: 400;
            color: #94a3b8;
            font-size: 13px;
        }

        .btn-add-attendee {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 16px;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-add-attendee:hover:not(.btn-disabled) {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
        }

        .btn-add-attendee.btn-disabled {
            background: #94a3b8;
            cursor: not-allowed;
            opacity: 0.7;
        }

        .attendees-note {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 14px;
            background: #fef3c7;
            border: 1px solid #fcd34d;
            border-radius: 10px;
            font-size: 13px;
            color: #92400e;
            margin-bottom: 16px;
        }

        .participant-count-section {
            background: #f0fdf4;
            border: 1px solid #86efac;
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 20px;
        }

        .participant-count-input {
            max-width: 160px;
        }

        .form-hint {
            font-size: 12px;
            color: #64748b;
            margin-top: 4px;
        }

        .attendee-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 12px;
        }

        .attendee-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 14px;
        }

        .attendee-number {
            font-size: 13px;
            font-weight: 700;
            color: #6366f1;
        }

        .btn-remove-attendee {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            background: #fee2e2;
            border: none;
            border-radius: 6px;
            color: #dc2626;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-remove-attendee:hover {
            background: #ef4444;
            color: white;
        }

        .attendee-form-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
        }

        /* ===== Validation Errors ===== */
        .validation-errors {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 14px;
            background: #fee2e2;
            border: 1px solid #fca5a5;
            border-radius: 10px;
            color: #dc2626;
        }

        .validation-errors ul {
            margin: 0;
            padding: 0 0 0 16px;
            font-size: 13px;
        }

        .validation-errors li {
            margin-bottom: 4px;
        }

        .validation-errors li:last-child {
            margin-bottom: 0;
        }

        /* ===== Buttons ===== */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
        }

        .btn-secondary:hover {
            background: #e2e8f0;
        }

        .btn-primary {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
        }

        .btn-success {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
        }

        .btn-success:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(34, 197, 94, 0.4);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }

        .btn-danger:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
        }

        /* ===== Apply Modal Info ===== */
        .apply-booking-info {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .info-label {
            font-size: 11px;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-value {
            font-size: 14px;
            font-weight: 600;
            color: #334155;
        }

        .info-value.highlight {
            color: #6366f1;
            font-weight: 700;
        }

        /* ===== Confirm Modal Styles ===== */
        .confirm-header {
            justify-content: center;
            border-bottom: none;
            padding-bottom: 0;
        }

        .confirm-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 72px;
            height: 72px;
            border-radius: 50%;
            color: white;
        }

        .confirm-icon.approve {
            background: linear-gradient(135deg, #22c55e, #16a34a);
        }

        .confirm-icon.cancel {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }

        .confirm-body {
            text-align: center;
            padding-top: 12px;
        }

        .confirm-title {
            font-size: 20px;
            font-weight: 700;
            color: #1e293b;
            margin: 0 0 8px 0;
        }

        .confirm-message {
            font-size: 14px;
            color: #64748b;
            margin: 0 0 16px 0;
        }

        .confirm-note {
            font-size: 13px;
            color: #94a3b8;
            font-style: italic;
            margin: 0;
        }

        .confirm-warning {
            font-size: 13px;
            color: #ef4444;
            font-weight: 600;
            margin: 16px 0 0 0;
        }

        .cancel-booking-info {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 16px;
            text-align: left;
        }

        .cancel-booking-info .info-row {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }

        .cancel-booking-info .info-row:last-child {
            margin-bottom: 0;
        }

        .cancel-booking-info .info-label {
            font-weight: 600;
            color: #64748b;
            min-width: 120px;
            font-size: 13px;
        }

        .cancel-booking-info .info-value {
            color: #334155;
            font-size: 13px;
            text-transform: uppercase;
        }

        .cancel-reason-section {
            margin-top: 16px;
            text-align: left;
        }

        .cancel-reason-label {
            display: block;
            font-weight: 600;
            color: #374151;
            font-size: 13px;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .cancel-reason-label .required {
            color: #ef4444;
        }

        .cancel-reason-input {
            width: 100%;
            padding: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 13px;
            resize: vertical;
            min-height: 80px;
            text-transform: uppercase;
        }

        .cancel-reason-input:focus {
            outline: none;
            border-color: #ef4444;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
        }

        .cancel-reason-input::placeholder {
            text-transform: uppercase;
            color: #9ca3af;
        }

        .confirm-footer {
            justify-content: center;
            flex-direction: row;
            gap: 12px;
        }

        /* ===== Responsive ===== */
        @media (max-width: 1024px) {
            .booking-row {
                grid-template-columns: repeat(3, 1fr);
                gap: 12px;
            }

            .booking-cell:nth-child(n+4) {
                display: none;
            }

            .actions-cell {
                display: flex !important;
            }
        }

        @media (max-width: 768px) {
            .selection-header {
                grid-template-columns: 1fr;
            }

            .filter-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
            }

            .session-header {
                flex-wrap: wrap;
            }

            .session-stats {
                width: 100%;
                justify-content: flex-start;
                margin-top: 12px;
            }

            .form-grid,
            .attendee-form-grid {
                grid-template-columns: 1fr;
            }

            .training-type-grid {
                grid-template-columns: 1fr;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .booking-row {
                grid-template-columns: 1fr 1fr;
            }

            .attendee-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    {{-- Flatpickr JS --}}
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
</x-filament-panels::page>
