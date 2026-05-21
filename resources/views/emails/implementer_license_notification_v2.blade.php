<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px; }
        h3 { margin: 24px 0 8px; color: #111; }
        table { border-collapse: collapse; width: 100%; margin: 8px 0 20px; max-width: 760px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; vertical-align: top; }
        th { background-color: #f2f2f2; font-weight: 600; }
        .summary th { width: 40%; }
        .modules th { background-color: #eef2ff; }
        .modules td.module { font-weight: 600; color: #1e3a8a; }
        a { color: #0056b3; text-decoration: none; }
        .muted { color: #6b7280; font-size: 0.9em; }
    </style>
</head>
<body>
    <p>Dear Customer,</p>

    <p>We're excited to welcome you to <strong>TimeTec HR</strong>! This email confirms that your HR V2 license has been successfully activated for <strong>{{ $emailContent['company']['name'] }}</strong>.</p>

    <p>Your license summary:</p>

    <table class="summary">
        <tr>
            <th>Handover ID</th>
            <td>{{ $emailContent['handover_id'] }}</td>
        </tr>
        <tr>
            <th>Certificate ID</th>
            <td>{{ $emailContent['certificate_id'] }}</td>
        </tr>
        <tr>
            <th>Kick Off Meeting Date</th>
            <td>{{ $emailContent['licenses']['kickOffDate'] }}</td>
        </tr>
        <tr>
            <th>Buffer Period</th>
            <td>{{ $emailContent['licenses']['bufferLicense']['start'] }} – {{ $emailContent['licenses']['bufferLicense']['end'] }} ({{ $emailContent['licenses']['bufferLicense']['duration'] }})</td>
        </tr>
        <tr>
            <th>Paid Period</th>
            <td>{{ $emailContent['licenses']['paidLicense']['start'] }} – {{ $emailContent['licenses']['paidLicense']['end'] }} ({{ $emailContent['licenses']['paidLicense']['duration'] }})</td>
        </tr>
        <tr>
            <th>Next Renewal</th>
            <td>{{ $emailContent['licenses']['nextRenewal'] }}</td>
        </tr>
    </table>
{{--
    @if (!empty($emailContent['modules']))
        <h3>Module Breakdown</h3>
        <table class="modules">
            <thead>
                <tr>
                    <th>Module</th>
                    <th>Seats</th>
                    <th>Trial Period</th>
                    <th>Paid Period</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($emailContent['modules'] as $module)
                    <tr>
                        <td class="module">{{ $module['name'] }}</td>
                        <td>{{ $module['seats'] }}</td>
                        <td>
                            @if (!empty($module['trial']))
                                {{ $module['trial']['start'] }} – {{ $module['trial']['end'] }}<br>
                                <span class="muted">{{ $module['trial']['months'] }} month(s)</span>
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if (!empty($module['paid']))
                                {{ $module['paid']['start'] }} – {{ $module['paid']['end'] }}<br>
                                <span class="muted">{{ $module['paid']['months'] }} month(s)</span>
                            @else
                                <span class="muted">Pending payment confirmation</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif --}}

    <p>If you have any questions or need assistance getting started, please don't hesitate to contact our support team. They're happy to help! You can reach them by phone at 03-80709933 or by email at <a href="mailto:support@timeteccloud.com">support@timeteccloud.com</a>.</p>

    <p>Thank you for choosing TimeTec HR. We look forward to a successful partnership!</p>
</body>
</html>
