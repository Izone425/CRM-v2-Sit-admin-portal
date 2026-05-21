<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commission Handover</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f3f4f6;
            margin: 0;
            padding: 40px 20px;
            color: #1f2937;
        }
        .card {
            max-width: 520px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            overflow: hidden;
            border-top: 6px solid {{ $actionResult === 'proceed' ? '#059669' : '#d97706' }};
        }
        .body {
            padding: 30px;
        }
        h1 {
            font-size: 20px;
            margin: 0 0 16px;
            color: {{ $actionResult === 'proceed' ? '#059669' : '#d97706' }};
        }
        .meta {
            background: #f9fafb;
            padding: 12px 16px;
            border-radius: 6px;
            margin: 18px 0;
            font-size: 14px;
        }
        .meta strong {
            color: #431fa1;
        }
        .message {
            white-space: pre-line;
            font-size: 14px;
            line-height: 1.6;
            color: #374151;
        }
        .footer {
            margin-top: 24px;
            font-size: 13px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="body">
            @if($actionResult === 'proceed')
                <h1>✓ Proceeded Successfully</h1>
                <p class="message">Thank you. This handover has been moved to <strong>Pending Finance</strong>.</p>
            @else
                <h1>⚠ Already Processed</h1>
                <p class="message">{{ $actionMessage }}</p>
            @endif

            <div class="meta">
                <strong>ID:</strong> {{ $handover->fh_id ?? '' }}<br>
                <strong>Reseller:</strong> {{ strtoupper($handover->reseller_name ?? 'N/A') }}<br>
                <strong>Subscriber:</strong> {{ strtoupper($handover->subscriber_name ?? 'N/A') }}<br>
                <strong>AP Number:</strong> {{ $handover->ap_invoice_no ?? 'N/A' }}<br>
                <strong>Amount:</strong> {{ $handover->currency ?? 'MYR' }} {{ number_format($handover->amount ?? 0, 2) }}
            </div>

            <p class="footer">Action time: {{ $actionTime }}<br>You may now close this tab.</p>
        </div>
    </div>
</body>
</html>
