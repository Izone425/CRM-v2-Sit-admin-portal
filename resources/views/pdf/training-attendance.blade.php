<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Attendance List</title>
    <style>
        @page {
            margin: 1.5cm 2cm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            line-height: 1.5;
            color: #000;
            margin: 0;
            padding: 0;
        }

        .title {
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 15px;
        }

        /* Meeting Info - plain text like Excel */
        .meeting-info {
            margin-bottom: 15px;
        }
        .meeting-info table {
            border-collapse: collapse;
        }
        .meeting-info td {
            padding: 1px 0;
            font-size: 10px;
            vertical-align: top;
        }
        .meeting-info .label {
            font-weight: bold;
            width: 130px;
        }

        /* Attendance Table - Excel style borders */
        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .attendance-table th,
        .attendance-table td {
            border: 1px solid #000;
            padding: 4px 6px;
            font-size: 10px;
            vertical-align: middle;
        }
        .attendance-table th {
            font-weight: bold;
            text-align: left;
        }
        .attendance-table .minutes-col {
            text-align: right;
        }

        /* Certification */
        .certification-text {
            font-size: 10px;
            margin-bottom: 15px;
        }

        /* Signature rows */
        .sig-section {
            margin-bottom: 15px;
        }
        .sig-row {
            font-size: 10px;
            margin-bottom: 3px;
        }
        .sig-row .label {
            display: inline-block;
            width: 120px;
            font-weight: bold;
        }
        .sig-space-sm {
            height: 30px;
        }
        .sig-space-lg {
            height: 60px;
        }
        .sig-line {
            display: inline-block;
            width: 180px;
            border-bottom: 1px solid #000;
        }

        /* Client section */
        .client-title {
            font-weight: bold;
            text-decoration: underline;
            font-size: 10px;
            margin-bottom: 3px;
        }
        .client-company {
            font-weight: bold;
            font-size: 10px;
            margin-bottom: 6px;
        }
    </style>
</head>
<body>
    <div class="title">ATTENDANCE LIST</div>

    <div class="meeting-info">
        <table>
            <tr><td class="label">Title:</td><td>{{ $title ?? 'TIMETEC HR - OPERATIONAL MODULES' }}</td></tr>
            <tr><td class="label">Date:</td><td>{{ $date ?? '-' }}</td></tr>
            <tr><td class="label">Duration:</td><td>{{ $duration ?? '-' }}</td></tr>
            <tr><td class="label">Participant Minutes</td><td>{{ $participantMinutes ?? '-' }}</td></tr>
            <tr><td class="label">Start Time:</td><td>{{ $startTime ?? '9:00 AM' }}</td></tr>
            <tr><td class="label">Participant Count:</td><td>{{ $participantCount ?? 0 }}</td></tr>
            <tr><td class="label">Max Participants:</td><td>{{ $maxParticipants ?? '-' }}</td></tr>
        </table>
    </div>

    <table class="attendance-table">
        <thead>
            <tr>
                <th>Meeting ID</th>
                <th>Name</th>
                <th>Join Time</th>
                <th>Leave Time</th>
                <th class="minutes-col">Minutes</th>
            </tr>
        </thead>
        <tbody>
            @forelse($attendees ?? [] as $attendee)
                <tr>
                    <td>{{ $attendee['meeting_id'] ?? $meetingId ?? '-' }}</td>
                    <td>{{ $attendee['name'] ?? '-' }}</td>
                    <td>{{ $attendee['join_time'] ?? '-' }}</td>
                    <td>{{ $attendee['leave_time'] ?? '-' }}</td>
                    <td class="minutes-col">{{ $attendee['minutes'] ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; padding: 15px;">No attendance records available</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <p class="certification-text">I certify that all trainees listed above had fully attended the training.</p>

    <div class="sig-section">
        <div class="sig-row"><span class="label">Company Name</span>: TimeTec Cloud Sdn Bhd</div>
        <div class="sig-row"><span class="label">Name</span>: {{ $trainerName ?? 'Mohd Hanif Bin Razali' }}</div>
        <div class="sig-row"><span class="label">Position</span>: Trainer - TimeTec HR</div>
        <div class="sig-row"><span class="label">Signature</span>:</div>
        <br>
        <div style="margin-left: 125px; width: 180px; border-bottom: 1px solid #000;"></div>
        <div class="sig-row"><span class="label">Training Provider</span>:</div>
        <div class="sig-row"><span class="label">Stamp</span></div>
        <div class="sig-space-sm"></div>
        <div class="sig-row"><span class="label">Date</span>: {{ $signatureDate ?? '-' }}</div>
    </div>

    <div class="sig-section">
        <div class="client-title">For Client Use:-</div>
        <div class="client-company">{{ $clientCompany ?? '-' }}</div>
        <div class="sig-row"><span class="label">Name</span>: </span></div>
        <div class="sig-row"><span class="label">Position</span>: </div>
        <div class="sig-row"><span class="label">Signature</span>:</div>
        <br>
        <div style="margin-left: 125px; width: 180px; border-bottom: 1px solid #000;"></div>
        <div class="sig-row"><span class="label">Company Stamp</span>:</div>
        <div class="sig-space-sm"></div>
        <div class="sig-row"><span class="label">Date</span>: {{ $signatureDate ?? '-' }}</div>
    </div>
</body>
</html>
