<!DOCTYPE html>
<html>
<head>
    <title>Still Looking to Simplify Your HR Processes?</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <p>Dear <strong>{{ $lead['lastName'] }}</strong>,</p>

    <p>I hope you are well. I'm <strong>{{ $leadOwnerName }}</strong> from TimeTec. Some time ago, you requested our HR checklist.</p>

    <p>We wanted to follow up in case you are still looking into ways to simplify your HR processes. TimeTec provides HR Cloud Solutions that help companies manage <strong>attendance, payroll, leave,</strong> and <strong>claim</strong> with less manual work.</p>

    <p>We are also currently running a promotion, where you can receive a <strong>FREE Biometric Device</strong> with subscription to our TimeTec Attendance module, subject to terms and conditions.</p>

    <p>If you are open to exploring this further, we would be happy to arrange a short demo.</p>

    <p>You may review our brochure
        <a href="https://www.timeteccloud.com/download/brochure/TimeTecHR-E.pdf" target="_blank">here</a>.
    </p>

    <p>Best regards,</p>
    <p>{{ $leadOwnerName }}<br>
        {{ $lead['position'] }}<br>
        TimeTec Cloud Sdn Bhd<br>
        Office: +603-8070 9933<br>
        WhatsApp: {{ $lead['leadOwnerMobileNumber'] ?? 'N/A' }}
    </p>
</body>
</html>
