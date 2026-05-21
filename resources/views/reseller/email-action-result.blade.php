<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TimeTec CRM - {{ $fbId }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .email-container {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 30px;
        }
        .header {
            margin-bottom: 20px;
        }
        .greeting {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .content {
            margin-bottom: 20px;
        }
        .info-section {
            background-color: #fff;
            padding: 15px;
            border-left: 4px solid #431fa1;
            margin: 15px 0;
        }
        .info-row {
            margin: 8px 0;
        }
        .info-label {
            font-weight: bold;
            color: #431fa1;
        }
        .footer {
            margin-top: 30px;
            font-size: 14px;
            color: #666;
        }
        .signature {
            margin-top: 20px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <div class="greeting">Dear Reseller</div>
        </div>

        <div class="content">
            <p>{{ $message }}</p>
        </div>

        <div class="info-section">
            <div class="info-row">
                <span class="info-label">ID:</span> {{ $fbId }}
            </div>
            <div class="info-row">
                <span class="info-label">Category:</span> Renewal Quotation
            </div>
            <div class="info-row">
                <span class="info-label">Status:</span>
                <span style="color: {{ $success ? '#059669' : '#dc2626' }}; font-weight: bold;">
                    {{ $success ? ($action === 'proceed' ? 'Confirmed' : 'Cancelled') : 'No Action Taken' }}
                </span>
            </div>
            @if(isset($handover))
            <div class="info-row">
                <span class="info-label">Reseller Company Name:</span> {{ $handover->reseller_company_name }}
            </div>
            <div class="info-row">
                <span class="info-label">Subscriber Company Name:</span> {{ $handover->subscriber_name }}
            </div>
            @endif
        </div>

        <div class="footer">
            <div class="signature">
                Regards<br>
                TimeTec HR CRM
            </div>
        </div>
    </div>
</body>
</html>
