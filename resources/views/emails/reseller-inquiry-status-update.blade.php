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
            <p>Your inquiry has been updated.</p>
        </div>

        <div class="info-section">
            <div class="info-row">
                <span class="info-label">ID:</span> {{ $inquiry->formatted_id }}
            </div>
            <div class="info-row">
                <span class="info-label">Category:</span> Reseller Inquiry
            </div>
            <div class="info-row">
                <span class="info-label">Status:</span>
                <span style="color: {{ $status === 'completed' ? '#059669' : '#dc2626' }}; font-weight: bold;">
                    {{ $statusLabel }}
                </span>
            </div>
            @if($status === 'completed' && $inquiry->completed_at)
            <div class="info-row">
                <span class="info-label">Completed At:</span> {{ \Carbon\Carbon::parse($inquiry->completed_at)->format('d M Y, h:i A') }}
            </div>
            @endif
            @if($status === 'rejected' && $inquiry->rejected_at)
            <div class="info-row">
                <span class="info-label">Rejected At:</span> {{ \Carbon\Carbon::parse($inquiry->rejected_at)->format('d M Y, h:i A') }}
            </div>
            @endif
            <div class="info-row">
                <span class="info-label">Reseller Company Name:</span> {{ strtoupper($inquiry->reseller_company_name) }}
            </div>
            {{-- <div class="info-row">
                <span class="info-label">Subscriber Name:</span> {{ $inquiry->subscriber_name }}
            </div>
            <div class="info-row">
                <span class="info-label">Title:</span> {{ $inquiry->title }}
            </div> --}}
        </div>

        @if($status === 'completed' && $inquiry->admin_remark)
            <div class="remark-section">
                @if($inquiry->admin_attachment_path)
                    @php
                        $attachments = is_string($inquiry->admin_attachment_path) ? json_decode($inquiry->admin_attachment_path, true) : $inquiry->admin_attachment_path;
                        $attachments = is_array($attachments) ? $attachments : [];
                    @endphp
                    @if(count($attachments) > 0)
                    <div class="info-row">
                        <span class="info-label">Admin Attachments:</span>
                        @foreach($attachments as $index => $attachment)
                            <a href="{{ url('storage/' . $attachment) }}" style="color: #431fa1; text-decoration: underline;">File {{ $index + 1 }}</a>@if(!$loop->last) / @endif
                        @endforeach
                    </div>
                    @endif
                @endif
                <div class="info-row">
                    <span class="info-label">Admin Remark:</span><br>
                    {!! nl2br(e($inquiry->admin_remark)) !!}
                </div>
            </div>
        @endif

        @if($status === 'rejected' && $inquiry->reject_reason)
            <div class="remark-section">
                @if($inquiry->reject_attachment_path)
                    @php
                        $rejectAttachments = is_string($inquiry->reject_attachment_path) ? json_decode($inquiry->reject_attachment_path, true) : $inquiry->reject_attachment_path;
                        $rejectAttachments = is_array($rejectAttachments) ? $rejectAttachments : [];
                    @endphp
                    @if(count($rejectAttachments) > 0)
                    <div class="info-row">
                        <span class="info-label">Admin Attachments:</span>
                        @foreach($rejectAttachments as $index => $attachment)
                            <a href="{{ url('storage/' . $attachment) }}" style="color: #431fa1; text-decoration: underline;">File {{ $index + 1 }}</a>@if(!$loop->last) / @endif
                        @endforeach
                    </div>
                    @endif
                @endif
                <div class="info-row">
                    <span class="info-label">Reject Reason:</span><br>
                    {!! nl2br(e($inquiry->reject_reason)) !!}
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
