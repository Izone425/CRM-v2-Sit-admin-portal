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
        .status-pending { color: #dc2626; font-weight: bold; }
        .status-done_expiring { color: #f59e0b; font-weight: bold; }
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
            <h1>TIMETEC RENEWAL NOTIFICATION</h1>
        </div>
        <div class="content">
            <p>Dear <strong>{{ $resellerName }}</strong>,<br>
            The following subscriber(s) have licenses expiring within <strong>90 days</strong> that require your attention:</p>

            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Company Name</th>
                        <th>Earliest Expiry</th>
                        <th>Days Remaining</th>
                        <th>Renewal Progress</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($companies as $index => $company)
                        @php
                            $totalInvoices = $company['total_invoice_count'] ?? count($company['invoices'] ?? []);
                            $doneInvoices  = $company['done_invoice_count'] ?? 0;
                            $badgeBg    = $doneInvoices === $totalInvoices ? '#d1fae5' : ($doneInvoices > 0 ? '#fef3c7' : '#fee2e2');
                            $badgeColor = $doneInvoices === $totalInvoices ? '#065f46' : ($doneInvoices > 0 ? '#92400e' : '#991b1b');
                            $badgeBorder= $doneInvoices === $totalInvoices ? '#6ee7b7' : ($doneInvoices > 0 ? '#fcd34d' : '#fca5a5');
                        @endphp
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ strtoupper($company['company_name']) }}</td>
                            <td>{{ \Carbon\Carbon::parse($company['expiry_date'])->format('d M Y') }}</td>
                            <td>{{ $company['days_remaining'] }} days</td>
                            <td>
                                <span style="display:inline-block; padding: 3px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; background-color: {{ $badgeBg }}; color: {{ $badgeColor }}; border: 1px solid {{ $badgeBorder }}; white-space: nowrap;">
                                    {{ $doneInvoices }}/{{ $totalInvoices }} done renewal
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="footer">
            This email notification will be remind every Monday at 8am from TimeTec CRM. Please do not reply to this email.
        </div>
    </div>
</body>
</html>
