<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Renewal Status Update — Reseller Portal</title>
</head>
<body style="margin:0; padding:20px; font-family: Arial, Helvetica, sans-serif; line-height:1.6; color:#333333; background:#f5f6fa;">
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:640px; margin:0 auto;">
    <tr>
        <td style="padding:24px; background:#ffffff; border-radius:8px;">
            <p style="margin:0 0 14px;">Dear Valued Reseller,</p>

            <p style="margin:0 0 14px;">
                We would like to inform you of an update to the renewal status handling in the Reseller Portal,
                effective today. This update is introduced to improve renewal tracking accuracy and ensure
                reminders are sent correctly.
            </p>

            <p style="margin:18px 0 6px;"><strong>What's new:</strong></p>
            <ul style="margin:0 0 14px 22px; padding:0;">
                <li>Renewal status will <strong>no longer be automatically changed</strong> to "Done Renewal"</li>
                <li>Once you have completed a renewal, you may <strong>manually update the status to "Done Renewal"</strong></li>
                <li>If updated by mistake, you may <strong>revert it back to "Pending"</strong> at any time</li>
            </ul>

            <p style="margin:18px 0 6px;"><strong>How to update the status:</strong></p>
            <ul style="margin:0 0 14px 22px; padding:0;">
                <li>An <strong>action button</strong> is available in the portal to update the status from <strong>Pending</strong> to <strong>Done Renewal</strong></li>
                <li>The same option can be used to revert the status back to <strong>Pending</strong>, if needed</li>
            </ul>

            <p style="margin:18px 0 6px;"><strong>Reminder mechanism:</strong></p>
            <ul style="margin:0 0 14px 22px; padding:0;">
                <li>While status is <strong>Pending</strong>, the system will send <strong>weekly reminders every Monday at 8:00 AM</strong></li>
                <li>Once marked as <strong>Done Renewal</strong>, reminders will <strong>no longer be sent</strong></li>
            </ul>

            @php
                $screenshotPath = public_path('img/reseller-portal-renewal-status.png');
            @endphp
            @if (file_exists($screenshotPath))
                <p style="margin:18px 0 8px; text-align:center;">
                    <img src="{{ $message->embed($screenshotPath) }}"
                         alt="Reseller Portal — Renewal Status example"
                         style="max-width:100%; height:auto; border:1px solid #e5e7eb; border-radius:8px;">
                </p>
            @endif

            <p style="margin:18px 0 0;">If you have any questions or require assistance, please feel free to let us know.</p>
        </td>
    </tr>
</table>
</body>
</html>
