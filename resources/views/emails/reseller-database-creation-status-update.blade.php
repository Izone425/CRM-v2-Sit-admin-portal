<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
            margin: 0;
        }
        .info-label {
            font-weight: bold;
            color: #431fa1;
        }
        .remark-section {
            background-color: #fff;
            padding: 15px;
            border-left: 4px solid {{ $status === 'completed' ? '#059669' : '#dc2626' }};
            margin: 15px 0;
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
            <p>Your database creation request has been updated.</p>
        </div>

        <div class="info-section">
            <div class="info-row">
                <span class="info-label">ID:</span> {{ $databaseCreation->formatted_id }}
            </div>
            <div class="info-row">
                <span class="info-label">Category:</span> Trial Account
            </div>
            <div class="info-row">
                <span class="info-label">Status:</span>
                <span style="color: {{ $status === 'completed' ? '#059669' : '#dc2626' }}; font-weight: bold;">
                    {{ $statusLabel }}
                </span>
            </div>
            @if($status === 'completed' && $databaseCreation->completed_at)
            <div class="info-row">
                <span class="info-label">Completed At:</span> {{ \Carbon\Carbon::parse($databaseCreation->completed_at)->format('d M Y, h:i A') }}
            </div>
            @endif
            @if($status === 'rejected' && $databaseCreation->rejected_at)
            <div class="info-row">
                <span class="info-label">Rejected At:</span> {{ \Carbon\Carbon::parse($databaseCreation->rejected_at)->format('d M Y, h:i A') }}
            </div>
            @endif
            @if($status === 'completed' && $databaseCreation->admin_attachment_path)
                @php
                    $attachments = is_string($databaseCreation->admin_attachment_path) ? json_decode($databaseCreation->admin_attachment_path, true) : $databaseCreation->admin_attachment_path;
                    $attachments = is_array($attachments) ? $attachments : [];
                @endphp
                @if(count($attachments) > 0)
                <div class="info-row">
                    <span class="info-label">Attachments:</span>
                    @foreach($attachments as $index => $attachment)
                        <a href="{{ url('storage/' . $attachment) }}" style="color: #431fa1; text-decoration: underline;">File {{ $index + 1 }}</a>@if(!$loop->last) / @endif
                    @endforeach
                </div>
                @endif
            @endif
            <div class="info-row">
                <span class="info-label">Reseller Company Name:</span> {{ strtoupper($databaseCreation->reseller_company_name) }}
            </div>
            <div class="info-row">
                <span class="info-label">Subscriber Name:</span> {{ $databaseCreation->company_name }}
            </div>
        </div>

        @if($status === 'completed' && $databaseCreation->admin_remark)
            <div class="remark-section">
                <div class="info-row">
                    <span class="info-label">Admin Remark:</span><br>
                    {!! nl2br(e($databaseCreation->admin_remark)) !!}
                </div>
            </div>
        @endif

        @if($status === 'rejected' && $databaseCreation->reject_reason)
            <div class="remark-section">
                <div class="info-row">
                    <span class="info-label">Reject Reason:</span><br>
                    {!! nl2br(e($databaseCreation->reject_reason)) !!}
                </div>
            </div>
        @endif

        <div class="footer">
            <div class="signature">
                Regards<br>
                TimeTec HR CRM
            </div>
        </div>
    </div>
</body>
</html>
