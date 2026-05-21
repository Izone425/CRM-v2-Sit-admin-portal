{{-- filepath: /var/www/html/timeteccrm/resources/views/emails/finance-handover-completed.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance Handover Completed</title>
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
            border-left: 4px solid #2563eb;
        }
        .attachment-box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #f57c00;
        }
        .content p {
            margin: 4px 0;
        }
    </style>
</head>
<body>
    <div class="content">
        <p>Hi Reseller, payment has been made for your hardware installation.</p>

        <div class="info-box">
            <p><strong>Finance ID:</strong> {{ $handoverId }}</p>
            <p><strong>SalesPerson:</strong> {{ $salesperson }}</p>
            <p><strong>Company Name:</strong> {{ $companyName }}</p>
            <p><strong>Reseller Name:</strong> {{ $resellerName }}</p>
        </div>

        <div class="attachment-box">
            <p><strong>Invoice by Reseller:</strong>
                @if(!empty($invoiceResellerFiles))
                    @foreach($invoiceResellerFiles as $file)
                        <a href="{{ $file['url'] }}" target="_blank">View</a>@if(!$loop->last), @endif
                    @endforeach
                @else
                    N/A
                @endif
            </p>
            <p><strong>Payment Slip by Finance:</strong>
                @if(!empty($paymentSlipFiles))
                    @foreach($paymentSlipFiles as $file)
                        <a href="{{ $file['url'] }}" target="_blank">View</a>@if(!$loop->last), @endif
                    @endforeach
                @else
                    N/A
                @endif
            </p>
        </div>
    </div>
</body>
</html>
