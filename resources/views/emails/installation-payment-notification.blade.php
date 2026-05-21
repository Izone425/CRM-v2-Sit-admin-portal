<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Installation Payment Request</title>
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
        <p>Hi, you have received a new installation payment request from a reseller.</p>

        <div class="info-box">
            <p><strong>Payment ID:</strong> {{ $paymentId }}</p>
            <p><strong>Reseller Company:</strong> <strong style="color: red;">{{ strtoupper($resellerCompanyName) }}</strong></p>
            <p><strong>Customer Name:</strong> {{ $customerName }}</p>
            <p><strong>Installation Date:</strong> {{ $installationDate }}</p>
            <p><strong>Installation Address:</strong> {{ $installationAddress }}</p>
        </div>
    </div>
</body>
</html>
