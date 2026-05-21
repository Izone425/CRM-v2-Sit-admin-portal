<x-emails.layout
    headerTitle="CRM Ticketing System"
    headerSubtitle="Workflow Update"
    :showDate="false"
>
    {{-- Handle comment scenario --}}
    @if($action === 'comment_created')
        <p>Hi {{ $recipient->name ?? 'there' }},</p>

        <p>{{ $config['greeting'] ?? '' }}</p>

        <div style="background-color: #f9fafb; padding: 20px; border-radius: 5px; margin: 20px 0;">
            <p><strong>{{ $additionalData['comment']->user->name ?? 'Someone' }}</strong> commented:</p>
            <div style="background-color: #ffffff; padding: 15px; border-left: 3px solid #3b82f6; margin: 10px 0;">
                {!! $additionalData['comment']->comment ?? '' !!}
            </div>
            <p style="color: #718096; font-size: 0.875rem;">
                {{ $additionalData['comment']->created_at->diffForHumans() }}
            </p>
        </div>

        <p><strong>Ticket Details:</strong></p>
        <div style="background-color: #f9fafb; padding: 15px; border-radius: 5px; margin: 10px 0;">
            <p><strong>Ticket ID:</strong> {{ $ticket->ticket_id }}</p>
            <p><strong>Title:</strong> {{ $ticket->title }}</p>
            <p><strong>Priority:</strong> {{ $ticket->priority?->name ?? 'N/A' }}</p>
        </div>

        <p>{{ $config['cta_text'] ?? 'View the full conversation and respond if needed.' }}</p>

        @php
            $commentUrl = 'https://dt.timeteccloud.com/tickets/' . $ticket->ticket_id . '#comments';

            if ($ticket->product) {
                $productName = $ticket->product->name;

                if (in_array($productName, ['TimeTec HR - Version 1', 'TimeTec HR - Version 2'])) {
                    $commentUrl = "https://crm.timeteccloud.com/admin/ticket-list/" . $ticket->ticket_id . "#comments";
                }
            }
        @endphp

        <x-emails.button :url="$commentUrl">
            {{ $config['button_text'] ?? 'View Comment' }}
        </x-emails.button>
    @else
        @php
            $recipientRole = $additionalData['recipient_role'] ?? '';
            $teamGreetings = [
                'RND Developer' => 'Hi, R&D Team',
                'RND Team Lead' => 'Hi, R&D Team',
                'RND TimeTec Project CRM Developer' => 'Hi, R&D Team',
                'PDT' => 'Hi, PDT Team',
                'PDT Team Lead' => 'Hi, PDT Team',
                'QC' => 'Hi, QC Team',
                'QC Team Lead' => 'Hi, QC Team',
            ];

            $greeting = isset($teamGreetings[$recipientRole])
                ? $teamGreetings[$recipientRole]
                : 'Hi ' . ($recipient->name ?? 'there');
        @endphp

        <p>{{ $greeting }},</p>

        <p>{{ $config['greeting'] ?? '' }}</p>

        <x-emails.ticket-details
            :ticket="$ticket"
            :config="$config"
            :action="$action"
            :actionBy="$actionBy"
            :reopener="$reopener ?? null"
            :reopenedAt="$reopenedAt ?? null"
            :rejector="$rejector ?? null"
            :rejectedAt="$rejectedAt ?? null"
            :pdtMandays="$pdtMandays ?? null"
            :rndMandays="$rndMandays ?? null"
            :qcMandays="$qcMandays ?? null"
            :totalMandays="$totalMandays ?? null"
        />

        <!-- @if($config['show_description'] ?? false)
            <p><strong>Description:</strong></p>
            <div style="background-color: #ffffff; padding: 15px; border-left: 3px solid #3b82f6; margin: 10px 0;">
                {!! $ticket->description !!}
            </div>
        @endif -->

        @if($config['show_original_reporter'] ?? false)
            <p><strong>Original Reporter:</strong> {{ $ticket->requestor?->name ?? 'N/A' }}</p>
        @endif

        @if($config['show_customer'] ?? false)
            <p><strong>Customer:</strong> {{ $ticket->company_name ?? 'N/A' }}</p>
        @endif

        @if($action === 'all_mandays_completed')
            <p><strong>Total Estimation Breakdown:</strong></p>
            <ul style="margin: 10px 0; padding-left: 20px;">
                @if(isset($pdtMandays))
                    <li style="margin: 5px 0;">PDT (Analysis & Design): {{ $pdtMandays }} mandays</li>
                @endif
                @if(isset($rndMandays))
                    <li style="margin: 5px 0;">R&D (Development): {{ $rndMandays }} mandays</li>
                @endif
                @if(isset($totalMandays))
                    <li style="margin: 5px 0;"><strong>Total Project Effort:</strong> {{ $totalMandays }} mandays</li>
                @endif
            </ul>
        @endif

        @if($config['show_reopen_reason'] ?? false)
            <div style="background-color: #fef2f2; border-left: 4px solid #ef4444; padding: 15px; margin: 15px 0;">
                <p style="margin: 0; font-weight: bold; color: #dc2626;">Reason for Reopening:</p>
                <div style="margin: 5px 0 0 0;">
                    {!! $reopenReason ?? 'Not specified' !!}
                </div>
            </div>
        @endif

        @if($config['show_rejection_reason'] ?? false)
            <div style="background-color: #fef2f2; border-left: 4px solid #ef4444; padding: 15px; margin: 15px 0;">
                <p style="margin: 0; font-weight: bold; color: #dc2626;">Rejection Reason:
                    @if($action === 'rejected_config')
                        System Configuration
                    @elseif($action === 'rejected_change_request')
                        Change Request
                    @elseif($action === 'pdt_rejected_request')
                        {!! $rejectionReason ?? 'Not specified' !!}
                    @else
                        {{ $rejectionReason ?? 'Not specified' }}
                    @endif
                </p>
            </div>
        @endif

        @if($config['show_change_request_next_steps'] ?? false)
            <div style="background-color: #eff6ff; border-left: 4px solid #3b82f6; padding: 15px; margin: 15px 0;">
                <p style="margin: 0; font-weight: bold; color: #1e40af;">Next Steps:</p>
                <p style="margin: 5px 0 0 0;">Please create a new ticket with priority P3, P4, or P5 (depending on urgency) to submit this as an enhancement request. Enhancement tickets go through the PDT review process for evaluation and planning.</p>
            </div>
        @endif

        @if($config['show_rfq_action'] ?? false)
            <div style="background-color: #fefce8; border-left: 4px solid #eab308; padding: 15px; margin: 15px 0;">
                <p style="margin: 0; font-weight: bold; color: #854d0e;">Action Required:</p>
                <p style="margin: 5px 0 0 0;">Please review the requirements and provide your team's manday estimation. All teams (PDT & R&D) need to submit estimates before we can provide the quotation to the customer.</p>
            </div>
        @endif

        @if($config['show_verification_steps'] ?? false)
            <div style="background-color: #f0fdf4; border-left: 4px solid #16a34a; padding: 15px; margin: 15px 0;">
                <p style="margin: 0; font-weight: bold; color: #15803d;">Next Steps:</p>
                <p style="margin: 5px 0 0 0;">Please verify the fix in the production environment. Once verified, you can mark this ticket as closed.</p>
            </div>
        @endif

        @if(!($config['dont_show_cta'] ?? false))
            <p>{{ $config['cta_text'] ?? 'Please review the ticket details and take necessary action.' }}</p>
        @endif

        @php
            $baseUrl = 'https://dt.timeteccloud.com/tickets/' . $ticket->ticket_id;
        @endphp

        <x-emails.button :url="$baseUrl">
            {{ $config['button_text'] ?? 'View Ticket Details' }}
        </x-emails.button>
    @endif
</x-emails.layout>
