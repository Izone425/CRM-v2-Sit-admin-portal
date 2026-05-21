<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Closing {{ ucfirst($status) }}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #f8f9fa; border-radius: 10px; padding: 30px; margin-bottom: 20px;">
        <h2 style="color: {{ $status === 'approved' ? '#10b981' : '#ef4444' }}; margin-top: 0;">
            Project Handover Request {{ ucfirst($status) }}
        </h2>

        <p>Hi {{ $handoverRequest->implementer_name }},</p>

        <p>Your project handover request has been <strong>{{ $status }}</strong>.</p>

        <div style="background-color: white; border-left: 4px solid {{ $status === 'approved' ? '#10b981' : '#ef4444' }}; padding: 15px; margin: 20px 0;">
            <p style="margin: 5px 0;"><strong>Software ID:</strong> {{ $handoverRequest->softwareHandover?->formatted_handover_id ?? 'N/A' }}</p>
            <p style="margin: 5px 0;"><strong>Company Name:</strong> {{ $handoverRequest->company_name }}</p>
            <p style="margin: 5px 0;"><strong>Date Request:</strong> {{ $handoverRequest->date_request?->format('d M Y, H:i') }}</p>
            @if($status === 'approved')
                <p style="margin: 5px 0;"><strong>Approved At:</strong> {{ $handoverRequest->approved_at?->format('d M Y, H:i') }}</p>
            @else
                <p style="margin: 5px 0;"><strong>Rejected At:</strong> {{ $handoverRequest->rejected_at?->format('d M Y, H:i') }}</p>
            @endif
        </div>

        @if($handoverRequest->team_lead_remark)
            <div style="background-color: #fff3cd; border: 1px solid #ffc107; border-radius: 5px; padding: 15px; margin: 20px 0;">
                <p style="margin: 0 0 10px 0;"><strong>Team Lead Remark:</strong></p>
                <p style="margin: 0;">{!! nl2br(e($handoverRequest->team_lead_remark)) !!}</p>
            </div>
        @endif

        @if($status === 'rejected')
            <div style="background-color: #fff5f5; border: 1px solid #ef4444; border-radius: 5px; padding: 15px; margin: 20px 0;">
                <p style="margin: 0; color: #dc2626;">
                    <strong>Note:</strong> You can submit a new handover request from the Project Plan tab once you've addressed the concerns mentioned above.
                </p>
            </div>
        @endif

        <p style="margin-top: 30px;">
            Best regards,<br>
            <strong>TimeTec CRM Team</strong>
        </p>
    </div>

    <div style="text-align: center; color: #6b7280; font-size: 12px; margin-top: 20px;">
        <p>This is an automated message from TimeTec CRM System.</p>
        <p>Please do not reply to this email.</p>
    </div>
</body>
</html>
