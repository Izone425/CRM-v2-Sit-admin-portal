<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Handover Completion</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #f8f9fa; border-radius: 10px; padding: 30px; margin-bottom: 20px;">
        <h2 style="color: #10b981; margin-top: 0;">
            Project Handover Completion
        </h2>

        <p>Dear Valued Client,</p>

        <p>We are pleased to inform you that the project handover for <strong>{{ $data['company_name'] }}</strong> has been successfully completed and approved.</p>

        <div style="background-color: white; border-left: 4px solid #10b981; padding: 15px; margin: 20px 0;">
            <p style="margin: 5px 0;"><strong>Software ID:</strong> {{ $data['sw_id'] }}</p>
            <p style="margin: 5px 0;"><strong>Company Name:</strong> {{ $data['company_name'] }}</p>
            <p style="margin: 5px 0;"><strong>Implementer:</strong> {{ $data['implementer_name'] }}</p>
            <p style="margin: 5px 0;"><strong>Approved Date:</strong> {{ $data['approved_at'] }}</p>
        </div>

        @if($data['team_lead_remark'])
            <div style="background-color: #fff3cd; border: 1px solid #ffc107; border-radius: 5px; padding: 15px; margin: 20px 0;">
                <p style="margin: 0 0 10px 0;"><strong>Team Lead Remarks:</strong></p>
                <p style="margin: 0;">{!! nl2br(e($data['team_lead_remark'])) !!}</p>
            </div>
        @endif

        @if(!empty($data['additional_notes']))
            <div style="background-color: #e3f2fd; border: 1px solid #2196f3; border-radius: 5px; padding: 15px; margin: 20px 0;">
                <p style="margin: 0 0 10px 0;"><strong>Additional Notes:</strong></p>
                <p style="margin: 0;">{!! nl2br(e($data['additional_notes'])) !!}</p>
            </div>
        @endif

        <div style="background-color: #e8f5e9; border: 1px solid #4caf50; border-radius: 5px; padding: 15px; margin: 20px 0;">
            <p style="margin: 0; color: #2e7d32;">
                <strong>What's Next?</strong><br>
                Your project has been successfully handed over. Our support team will continue to be available for any assistance you may need.
            </p>
        </div>

        <p style="margin-top: 30px;">
            Thank you for choosing TimeTec HR. We look forward to continuing to serve you.
        </p>

        <p style="margin-top: 30px;">
            Best regards,<br>
            <strong>{{ $data['implementer_name'] }}</strong><br>
            <strong>TimeTec CRM Implementation Team</strong>
        </p>
    </div>

    <div style="text-align: center; color: #6b7280; font-size: 12px; margin-top: 20px;">
        <p>This is an automated message from TimeTec CRM System.</p>
        <p>If you have any questions, please contact your implementation team.</p>
    </div>
</body>
</html>
