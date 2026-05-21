{{-- filepath: /var/www/html/timeteccrm/resources/views/emails/finance-handover-rejected.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance Handover Rejected</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .content {
            background: #f9fafb;
            padding: 30px;
            border-radius: 0 0 8px 8px;
            border: 1px solid #e5e7eb;
        }
        .info-box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #ef4444;
        }
        .reason-box {
            background: #fef2f2;
            border: 1px solid #f87171;
            border-radius: 8px;
            padding: 16px;
            margin: 16px 0;
        }
        .reason-label {
            font-weight: bold;
            color: #991b1b;
            margin-bottom: 8px;
        }
        .reason-text {
            color: #991b1b;
            white-space: pre-wrap;
        }

        .content p {
            margin: 4px 0;
        }
    </style>
</head>
<body>
    <div class="content">
        <p>Hi {{ $salesperson->name }},</p>

        <p>Your Finance Handover has been rejected.</p>

        <div class="info-box">
            <p><strong>Company Name:</strong> {{ $companyName }}</p>
            <p><strong>Reseller Name:</strong> {{ $resellerName }}</p>
            <p><strong>Finance Handover ID:</strong> {{ $handoverId }}</p>
            <p><strong>Status:</strong> REJECTED</p>
        </div>

        @if($rejectReason)
        <div class="reason-box">
            <div class="reason-label">Reason for Rejection:</div>
            <div class="reason-text">{{ $rejectReason }}</div>
        </div>
        @endif
    </div>
</body>
</html>
