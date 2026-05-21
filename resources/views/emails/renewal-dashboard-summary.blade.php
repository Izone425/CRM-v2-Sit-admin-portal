<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Renewal Dashboard Summary</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.5;">
    <div style="max-width: 760px; margin: 0 auto; padding: 20px;">
        <h2 style="margin: 0 0 10px 0;">Admin Renewal Dashboard Summary</h2>
        <p style="margin: 0 0 20px 0; color: #4b5563;">
            Generated at: {{ $generatedAt->format('Y-m-d H:i:s') }}
        </p>

        @foreach ($summary as $sectionName => $rows)
            <h3 style="margin: 20px 0 10px 0;">{{ $sectionName }}</h3>
            <table width="100%" cellpadding="6" cellspacing="0" style="border-collapse: separate; border-spacing: 8px; margin-bottom: 12px; table-layout: fixed;">
                <tr>
                    @foreach ($rows as $row)
                        <td valign="top" width="25%" style="border: 1px solid #d1d5db; background: #f9fafb; border-radius: 8px; padding: 10px;">
                            <div style="font-size: 12px; color: #4b5563; min-height: 36px;">{{ $row['label'] }}</div>
                            <div style="font-size: 22px; font-weight: 700; color: #111827; margin-top: 8px; text-align: right;">{{ $row['count'] }}</div>
                        </td>
                    @endforeach
                </tr>
            </table>
        @endforeach

        <p style="margin-top: 16px; color: #6b7280; font-size: 12px;">
            This is an automated notification.
        </p>
    </div>
</body>
</html>
