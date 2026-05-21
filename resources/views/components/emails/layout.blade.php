@props([
    'headerTitle' => 'CRM Ticketing System',
    'headerSubtitle' => 'Notification',
    'showDate' => true
])

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f5f6f8;
            font-family: Arial, Helvetica, sans-serif;
            color: #333333;
        }

        .email-wrapper {
            width: 100%;
            padding: 32px 0;
            background-color: #f5f6f8;
        }

        .email-container {
            max-width: 680px;
            margin: 0 auto;
            background-color: #ffffff;
            border: 1px solid #e5e7eb;
            box-shadow: 0 6px 14px rgba(0, 0, 0, 0.06);
        }

        /* ===== Header ===== */
        .header {
            padding: 20px 30px;
            background-color: #fafafa;
            border-bottom: 1px solid #e5e7eb;
            text-align: center;
        }

        .header-title {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
        }

        .header-subtitle {
            margin-top: 4px;
            font-size: 12px;
            color: #6b7280;
        }

        /* ===== Content ===== */
        .content {
            padding: 30px;
        }

        .section {
            margin-bottom: 28px;
            padding-bottom: 28px;
            border-bottom: 1px solid #f0f0f0;
        }

        .section:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }

        /* ===== Footer ===== */
        .footer {
            padding: 20px 30px;
            background-color: #fafafa;
            border-top: 1px solid #e5e7eb;
            font-size: 12px;
            color: #666666;
            text-align: center;
        }

        a {
            color: #2563eb;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-container">

            <!-- Header -->
            <div class="header">
                <p class="header-title">{{ $headerTitle }}</p>
                <p class="header-subtitle">
                    {{ $headerSubtitle }}@if($showDate) · {{ now()->format('l, F j, Y') }}@endif
                </p>
            </div>

            <!-- Main Content -->
            <div class="content">
                {{ $slot }}
            </div>

            <!-- Footer -->
            <div class="footer">
                <p>
                    This is an automated notification from TimeTec. Sent at {{ now()->setTimezone('+08:00')->format('H:i') }}
                </p>
            </div>

        </div>
    </div>
</body>
</html>
