{{--
    Ticket Details Component
    Supports different display modes based on email scenario
    Modes: created, completed, verified_live, reopened, rejected_config, rejected_change_request, all_mandays_completed
--}}
@php
    $mode = $config['detail_mode'] ?? 'standard';
@endphp

<div style="background-color: #f9fafb; padding: 20px; border-radius: 5px; margin: 20px 0;">
    <table style="width: 100%; border-collapse: collapse;">
        @if($mode != 'rejected_change_request')
            <tr>
                <td style="padding: 8px 0; font-weight: bold; width: 30%;">Ticket ID:</td>
                <td style="padding: 8px 0;">{{ $ticket->ticket_id }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; font-weight: bold;">Title:</td>
                <td style="padding: 8px 0;">{{ $ticket->title }}</td>
            </tr>
        @endif
        {{-- Mode: created (Ticket Created - All Priorities) --}}
        @if($mode === 'created')
            <tr>
                <td style="padding: 8px 0; font-weight: bold;">Priority:</td>
                <td style="padding: 8px 0;">{{ $ticket->priority?->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; font-weight: bold;">Type:</td>
                <td style="padding: 8px 0;">
                    @if($action === 'created_p1')
                        Software Bug
                    @elseif($action === 'created_p2')
                        Backend Assistance
                    @elseif($action === 'created_p3_p5')
                        Enhancement / Feature Request
                    @elseif($action === 'created_p4a')
                        Custom Development / Feature Request
                    @else
                        {{ $ticket->type ?? 'N/A' }}
                    @endif
                </td>
            </tr>
            <tr>
                <td style="padding: 8px 0; font-weight: bold;">Created by:</td>
                <td style="padding: 8px 0;">{{ $ticket->requestor?->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; font-weight: bold;">Created at:</td>
                <td style="padding: 8px 0;">{{ $ticket->created_at->addHours(8)->format('M d, Y g:i A') }}</td>
            </tr>

        {{-- Mode: completed (Ticket Completed) --}}
        @elseif($mode === 'completed')
            <tr>
                <td style="padding: 8px 0; font-weight: bold;">Priority:</td>
                <td style="padding: 8px 0;">{{ $ticket->priority?->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; font-weight: bold;">Completed by:</td>
                <td style="padding: 8px 0;">{{ $actionBy?->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; font-weight: bold;">Completed at:</td>
                <td style="padding: 8px 0;">{{ $ticket->completion_date ? \Carbon\Carbon::parse($ticket->completion_date)->addHours(8)->format('M d, Y g:i A') : now()->addHours(8)->format('M d, Y g:i A') }}</td>
            </tr>

        {{-- Mode: reopened (Ticket Reopened) --}}
        @elseif($mode === 'reopened')
            <tr>
                <td style="padding: 8px 0; font-weight: bold;">Priority:</td>
                <td style="padding: 8px 0;">{{ $ticket->priority?->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; font-weight: bold;">Reopened by:</td>
                <td style="padding: 8px 0;">{{ $reopener->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; font-weight: bold;">Reopened at:</td>
                <td style="padding: 8px 0;">{{ $reopenedAt ? $reopenedAt->addHours(8)->format('M d, Y g:i A') : 'N/A' }}</td>
            </tr>

        {{-- Mode: rejected_config (Ticket Rejected - System Config) --}}
        @elseif($mode === 'rejected_config')
            <tr>
                <td style="padding: 8px 0; font-weight: bold;">Rejected by:</td>
                <td style="padding: 8px 0;">{{ $rejector->name ?? 'N/A' }} ({{ $rejector?->roles?->first()?->name ?? 'N/A' }})</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; font-weight: bold;">Rejected at:</td>
                <td style="padding: 8px 0;">{{ $rejectedAt ? $rejectedAt->addHours(8)->format('M d, Y g:i A') : 'N/A' }}</td>
            </tr>

        {{-- Mode: rejected_change_request or pdt_rejected_request --}}
        @elseif($mode === 'rejected_change_request' || $mode === 'pdt_rejected_request')
            <tr>
                <td style="padding: 8px 0; font-weight: bold;">Original Ticket ID:</td>
                <td style="padding: 8px 0;">{{ $ticket->ticket_id }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; font-weight: bold;">Title:</td>
                <td style="padding: 8px 0;">{{ $ticket->title }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; font-weight: bold;">Rejected by:</td>
                <td style="padding: 8px 0;">{{ $rejector->name ?? 'N/A' }} ({{ $rejector?->roles?->first()?->name ?? 'N/A' }})</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; font-weight: bold;">Rejected at:</td>
                <td style="padding: 8px 0;">{{ $rejectedAt ? $rejectedAt->addHours(8)->format('M d, Y g:i A') : 'N/A' }}</td>
            </tr>

        {{-- Mode: all_mandays_completed (All RFQ Mandays Completed) --}}
        @elseif($mode === 'all_mandays_completed')
            <tr>
                <td style="padding: 8px 0; font-weight: bold;">Customer:</td>
                <td style="padding: 8px 0;">{{ $ticket->company_name ?? 'N/A' }}</td>
            </tr>
        @endif
    </table>
</div>
