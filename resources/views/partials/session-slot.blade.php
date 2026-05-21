<!-- filepath: /var/www/html/timeteccrm/resources/views/partials/session-slot.blade.php -->

@if(isset($sessionDetails['status']))
    @php
        // Determine card style based on session status
        $cardStyle = '';
        $isClickable = true;
        $isBooked = isset($sessionDetails['booked']) && $sessionDetails['booked'];

        if ($sessionDetails['status'] === 'past') {
            $cardStyle = 'background-color: #C2C2C2; cursor: not-allowed;';
            $isClickable = false;
        } elseif ($sessionDetails['status'] === 'leave') {
            $cardStyle = 'background-color: #E9EBF0;';
            $isClickable = false;
        } elseif ($sessionDetails['status'] === 'holiday') {
            $cardStyle = 'background-color: #C2C2C2;';
            $isClickable = false;
        } elseif ($sessionDetails['status'] === 'available') {
            $cardStyle = 'background-color: #C6FEC3;';
        } elseif ($sessionDetails['status'] === 'implementation_session') {
            $cardStyle = 'background-color: #FEE2E2;';
        } elseif ($sessionDetails['status'] === 'skip_email_teams') {
            $cardStyle = 'background-color: #c3e4fe;';
        } elseif ($sessionDetails['status'] === 'implementer_request') {
            $cardStyle = 'background-color: #FEF9C3;';
        } elseif ($sessionDetails['status'] === 'cancelled') {
            $sessionDateTime = Carbon\Carbon::parse($weekDays[$loop->parent->iteration - 1]['carbonDate'] . ' ' . $sessionDetails['start_time']);
            if (Carbon\Carbon::now() > $sessionDateTime) {
                $cardStyle = 'background-color: #C2C2C2; cursor: not-allowed;';
                $isClickable = false;
            } else {
                $cardStyle = 'background-color: #C6FEC3;';
                $isClickable = true;
            }
        }
    @endphp

    @if($isBooked)
        <!-- Display Booked Session -->
        <div class="appointment-card" style="{{ $cardStyle }}"
            wire:click="showAppointmentDetails({{ $sessionDetails['appointment']->id ?? 'null' }})">
            <div class="appointment-card-bar"></div>
            <div class="appointment-card-info">
                <div class="appointment-demo-type">
                    {{ $sessionDetails['appointment']->type !== 'REVIEW SESSION' ? str_replace(' SESSION', '', $sessionDetails['appointment']->type) : 'REVIEW SESSION' }}
                </div>
                <div class="appointment-appointment-type">
                    {{ $sessionDetails['appointment']->appointment_type }}
                    @if($sessionDetails['status'] === 'implementer_request' && $sessionDetails['appointment']->request_status)
                        | <span style="text-transform:uppercase">{{ $sessionDetails['appointment']->request_status }}</span>
                    @elseif($sessionDetails['status'] === 'implementation_session' && $sessionDetails['appointment']->status)
                        | <span style="text-transform:uppercase">{{ $sessionDetails['appointment']->status }}</span>
                    @endif
                </div>
                <div class="appointment-company-name" title="{{ $sessionDetails['appointment']->company_name }}">
                    @if(isset($sessionDetails['appointment']->lead_id) && $sessionDetails['appointment']->lead_id)
                        <a target="_blank" rel="noopener noreferrer" href="{{ $sessionDetails['appointment']->url }}">
                            {{ $sessionDetails['appointment']->company_name }}
                        </a>
                    @else
                        {{ $sessionDetails['appointment']->company_name ?? $sessionDetails['appointment']->title ?? 'N/A' }}
                    @endif
                </div>
                <div class="appointment-time">{{ $sessionDetails['appointment']->start_time }} -
                    {{ $sessionDetails['appointment']->end_time }}</div>
            </div>
        </div>
    @else
        <!-- Display Available or Unavailable Session Slot -->
        <div class="available-session-card" style="{{ $cardStyle }}"
            @if($isClickable && auth()->user()->role_id != 2)
                wire:click="bookSession('{{ $row['implementerId'] }}', '{{ $weekDays[$loop->parent->iteration - 1]['carbonDate'] }}', '{{ $sessionName }}', '{{ $sessionDetails['start_time'] }}', '{{ $sessionDetails['end_time'] }}')"
            @endif>
            <div class="available-session-bar"></div>
            <div class="available-session-info">
                @if($sessionDetails['status'] === 'leave')
                    <div class="available-session-name">ON LEAVE</div>
                @elseif($sessionDetails['status'] === 'holiday')
                    <div class="available-session-name">PUBLIC HOLIDAY</div>
                @elseif($sessionDetails['status'] === 'past')
                    <div class="available-session-name">PAST SESSION</div>
                @elseif($sessionDetails['status'] === 'cancelled' && !$isClickable)
                    <div class="available-session-name">CANCELLED SESSION</div>
                @else
                    <div class="available-session-name">{{ $sessionName }}<br>AVAILABLE SLOT</div>
                @endif
                <div class="available-session-time">{{ $sessionDetails['formatted_start'] }} - {{ $sessionDetails['formatted_end'] }}</div>
            </div>
        </div>
    @endif
@endif
