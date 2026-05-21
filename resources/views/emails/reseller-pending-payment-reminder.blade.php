<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; color: #333; line-height: 1.6; margin: 0; padding: 0; }
        .container { max-width: 800px; margin: 0 auto; padding: 20px; }
        .header { background-color: #1a56db; color: #fff; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .header h1 { margin: 0; font-size: 20px; }
        .content { padding: 20px; background-color: #f9fafb; border: 1px solid #e5e7eb; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th { background-color: #1a56db; color: #fff; padding: 10px 12px; text-align: left; font-size: 13px; }
        td { padding: 8px 12px; border-bottom: 1px solid #e5e7eb; font-size: 13px; }
        tr:nth-child(even) { background-color: #f3f4f6; }
        .overdue-green { color: #059669; font-weight: normal; }
        .overdue-red { color: #dc2626; font-weight: bold; }
        .footer { padding: 15px 20px; text-align: center; font-size: 12px; color: #6b7280; border: 1px solid #e5e7eb; border-top: none; border-radius: 0 0 8px 8px; }

        @media only screen and (max-width: 600px) {
            .container { padding: 10px; }
            .header { padding: 15px 10px; }
            .header h1 { font-size: 16px; }
            .content { padding: 12px; }
            table { width: 100%; }
            th, td { padding: 6px 4px; font-size: 10px; word-wrap: break-word; overflow-wrap: break-word; }
            p { font-size: 13px; }
            .footer { font-size: 10px; padding: 10px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>TIMETEC | PENDING RESELLER PAYMENT</h1>
        </div>
        <div class="content">
            <p>Dear <strong>{{ $resellerCompanyName }}</strong>,<br>
            You have <strong>{{ $handovers->count() }}</strong> pending payment(s) that require your attention:</p>

            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>ID</th>
                        <th>Subscriber Name</th>
                        <th>Last Modified</th>
                        <th>Overdue</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($handovers as $index => $handover)
                        @php
                            $today = now()->startOfDay();
                            $updatedAt = $handover->updated_at->startOfDay();
                            $daysDiff = $today->diffInDays($updatedAt);
                        @endphp
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td style="font-weight: 600;">{{ $handover->fb_id ?? $handover->fd_id ?? $handover->fe_id ?? $handover->id }}</td>
                            <td>{{ $handover->subscriber_name }}</td>
                            <td>{{ $handover->updated_at->format('d M Y, H:i') }}</td>
                            <td>
                                @if($daysDiff == 0)
                                    <span class="overdue-green">0 Day</span>
                                @else
                                    <span class="overdue-red">-{{ $daysDiff }} Days</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <p>Please log in to the <strong>Reseller Portal</strong> to upload the payment slip for the above pending payment(s).</p>
        </div>
        <div class="footer">
            This email notification will be reminded every Monday at 9am from TimeTec CRM. Please do not reply to this email.
        </div>
    </div>
</body>
</html>
