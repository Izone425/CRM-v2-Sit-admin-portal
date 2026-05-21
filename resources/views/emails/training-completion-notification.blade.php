<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Training Completion</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <p>Dear {{ $attendeeName }},</p>

    <p>Glad to see you during the online {{ $trainingType === 'HRDF' ? 'HRDF' : 'Webinar' }} training, we hope it was informational for you as it was for us.</p>

    <p>We have prepared the recording for your future reference. If you wish to download it for your safekeeping, please download it within 30 days upon receiving this email.</p>

    <p><strong>Link:</strong></p>

    @php
        $dayModules = [
            1 => 'Online Training Attendance - Day 1',
            2 => 'Online Training Leave & Claim - Day 2',
            3 => 'Online Training Payroll - Day 3',
        ];
    @endphp

    @foreach($dayModules as $day => $moduleName)
        @php
            $dayRecordings = $recordingLinks[$day] ?? [];
        @endphp

        @if(!empty($dayRecordings))
            <p>
                <strong>{{ $moduleName }}</strong><br>
                @if(is_array($dayRecordings))
                    @foreach($dayRecordings as $index => $link)
                        <a href="{{ $link }}">Part {{ $index + 1 }}</a>@if(!$loop->last) | @endif
                    @endforeach
                @else
                    <a href="{{ $dayRecordings }}">Download Recording</a>
                @endif
            </p>
        @endif
    @endforeach

    <p>To improve our training quality, we would like to request your input by filling in the short survey form before we proceed to the next step.</p>

    <p><strong>Survey form link:</strong></p>

    @foreach($dayModules as $day => $moduleName)
        @php
            $surveyLink = $surveyLinks[$day] ?? null;
        @endphp

        @if(!empty($surveyLink))
            <p><a href="{{ $surveyLink }}">{{ $moduleName }}</a></p>
        @endif
    @endforeach

    <p>Thank you for choosing TimeTec!</p>
</body>
</html>
