<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .training-info {
            margin: 25px 0;
            padding: 15px;
            background: #f9f9f9;
            border-left: 4px solid #0078d4;
        }
        .training-day-title {
            font-weight: bold;
            color: #0078d4;
            margin-bottom: 10px;
        }
        .info-line {
            margin: 5px 0;
        }
        .info-label {
            display: inline-block;
            width: 120px;
            font-weight: normal;
        }
        .info-value {
            display: inline;
        }
        .requirements {
            margin: 20px 0;
            padding: 15px;
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 5px;
        }
        .requirements ol {
            margin: 10px 0;
            padding-left: 20px;
        }
        .requirements li {
            margin-bottom: 15px;
        }
        .sub-point {
            margin-left: 20px;
            font-size: 0.95em;
            color: #555;
        }
        a {
            color: #0078d4;
        }
    </style>
</head>
<body>
<p>Dear Customer,</p>

<p>Greetings from TimeTec HR team.</p>

<p>Firstly, thank you for choosing TimeTec HR and we are happy to have you with us. We hope you are all geared up for your training sessions.</p>

<p>We have already booked your Online HRDF Training slots on the dates below.</p>

<p>Please be informed that the training will be conducted via Microsoft Teams at 09.00AM and the link will be open at 08.45AM. There are 4 key points you will need to adhere to before clicking 'Join Meeting' on the meeting link. These are the requirements from HRDF:</p>

<div class="requirements">
    <ol>
        <li>
            <strong>Webcam</strong> - Kindly ensure your webcam is functioning as we need to take pictures at few intervals for HRDF record purposes.
            <div class="sub-point">If your webcam is nonfunctional, you may also log on using your mobile phone simultaneously.</div>
            <div class="sub-point">You are not required to have your camera on throughout the training session.</div>
        </li>
        <li>
            <strong>Attendance Checking</strong> - HRDF attendance checking will be from 9:00 AM to 9:30 AM.
            <div class="sub-point">Please join the meeting early to capture your attendance photo, as other participants will be waiting.</div>
            <div class="sub-point">The training session will begin promptly at 9:30 AM.</div>
        </li>
        <li>
            <strong>Profile Name Display</strong> - Before you join the meeting, please ensure that your profile name is based on this format:
            <div class="sub-point">> COMPANY NAME-PARTICIPANT FULL NAME</div>
            <div class="sub-point">> (eg. TIMETEC CLOUD SDN BHD-MOHD FAIROS)</div>
        </li>
        <li>
            <strong>Lunch Break</strong> - Lunch break is scheduled at 12.30PM - 01.30PM.
        </li>
    </ol>
</div>

<p>Please find below the training deck & training link.</p>
<p><strong>Training Mode:</strong> Online Training via Microsoft Teams</p>

<div class="training-info">
    <p class="training-day-title">Day 1 | {{ $day1Date }} | {{ $day1Module }}</p>
    <div class="info-line">
        <span class="info-label">Time:</span>
        <span class="info-value">9AM-5PM</span>
    </div>
    @if($day1DeckLink)
    <div class="info-line">
        <span class="info-label">Training Deck:</span>
        <span class="info-value"><a href="{{ $day1DeckLink }}">Click here to view</a></span>
    </div>
    @endif
    <div class="info-line">
        <span class="info-label">Training Link:</span>
        <span class="info-value"><a href="{{ $session->day1_meeting_link }}">Click here to join</a></span>
    </div>
    <div class="info-line">
        <span class="info-label">Meeting ID:</span>
        <span class="info-value">{{ $day1MeetingId }}</span>
    </div>
    <div class="info-line">
        <span class="info-label">Password:</span>
        <span class="info-value">{{ $day1Password }}</span>
    </div>
</div>

<div class="training-info">
    <p class="training-day-title">Day 2 | {{ $day2Date }} | {{ $day2Module }}</p>
    <div class="info-line">
        <span class="info-label">Time:</span>
        <span class="info-value">9AM-5PM</span>
    </div>
    @if($day2DeckLink)
    <div class="info-line">
        <span class="info-label">Training Deck:</span>
        <span class="info-value"><a href="{{ $day2DeckLink }}">Click here to view</a></span>
    </div>
    @endif
    <div class="info-line">
        <span class="info-label">Training Link:</span>
        <span class="info-value"><a href="{{ $session->day2_meeting_link }}">Click here to join</a></span>
    </div>
    <div class="info-line">
        <span class="info-label">Meeting ID:</span>
        <span class="info-value">{{ $day2MeetingId }}</span>
    </div>
    <div class="info-line">
        <span class="info-label">Password:</span>
        <span class="info-value">{{ $day2Password }}</span>
    </div>
</div>

<div class="training-info">
    <p class="training-day-title">Day 3 | {{ $day3Date }} | {{ $day3Module }}</p>
    <div class="info-line">
        <span class="info-label">Time:</span>
        <span class="info-value">9AM-5PM</span>
    </div>
    @if($day3DeckLink)
    <div class="info-line">
        <span class="info-label">Training Deck:</span>
        <span class="info-value"><a href="{{ $day3DeckLink }}">Click here to view</a></span>
    </div>
    @endif
    <div class="info-line">
        <span class="info-label">Training Link:</span>
        <span class="info-value"><a href="{{ $session->day3_meeting_link }}">Click here to join</a></span>
    </div>
    <div class="info-line">
        <span class="info-label">Meeting ID:</span>
        <span class="info-value">{{ $day3MeetingId }}</span>
    </div>
    <div class="info-line">
        <span class="info-label">Password:</span>
        <span class="info-value">{{ $day3Password }}</span>
    </div>
</div>

<p>We hope to see you during the training. Have a pleasant day ahead.</p>

<p>Thank you for choosing TimeTec!</p>
</body>
</html>
