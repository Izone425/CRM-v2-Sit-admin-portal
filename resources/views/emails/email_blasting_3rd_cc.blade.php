<!DOCTYPE html>
<html>
<head>
    <title>Follow-Up on HR Cloud Suite used by 1,500+ organizations in Malaysia</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <p>Dear <strong>{{ $lead['lastName'] }}</strong>,</p>

    <p>I hope this email finds you well. I’m <strong>{{ $leadOwnerName }}</strong> from TimeTec. We work with more than <strong>1,500 organizations across Malaysia</strong> in industries such as Manufacturing, Retail &amp; F&amp;B, Construction, and Engineering, helping them modernize and simplify their HR operations through our HR Cloud Suite.</p>

    <p>I wanted to reach out to introduce our platform, designed to help HR teams reduce manual work, improve accuracy, and operate more efficiently at scale.</p>

    <ul>
        <li><strong>Attendance:</strong> Shift Management &amp; OT Control</li>
        <li><strong>Payroll:</strong> Automated Payroll &amp; Statutory Report</li>
        <li><strong>Claim:</strong> Claims Submission &amp; Approval</li>
        <li><strong>Leave:</strong> Leave Application &amp; Approval</li>
    </ul>

    <p>As part of a special promotion, you can receive a <strong>FREE Biometric Device</strong> with your subscription to our Time Attendance module (terms and conditions apply).</p>

    <p>Let’s schedule a demo at your convenience to explore how our tailored solutions can benefit your organization and provide you with details on how to claim your FREE Biometric Device.</p>

    <p>To help you get started, please review our brochure
        <a href="https://www.timeteccloud.com/download/brochure/TimeTecHR-E.pdf" target="_blank">here</a>
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
