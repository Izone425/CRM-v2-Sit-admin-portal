<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Offset Payment Rejected</title>
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
            background-color: #ffffff;
            padding: 20px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        .info-table th,
        .info-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        .info-table th {
            background-color: #f8f9fa;
            font-weight: bold;
            width: 30%;
        }
        .reject-reason {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 5px;
            padding: 15px;
            margin: 15px 0;
            color: #dc2626;
        }
    </style>
</head>
<body>
    <div class="content">
        <p>Hi {{ $requestorName }},</p>

        <p>Your offset payment has been rejected for the following reason:</p>

        <div class="reject-reason">
            {{ $rejectReason }}
        </div>

        <table class="info-table">
            <tr>
                <th>ID:</th>
                <td><strong>{{ $formattedId }}</strong></td>
            </tr>
            <tr>
                <th>Company Name:</th>
                <td>{{ $companyName }}</td>
            </tr>
            @if($invoiceNo)
            <tr>
                <th>Invoice No:</th>
                <td>{{ $invoiceNo }}</td>
            </tr>
            @endif
        </table>
    </div>
</body>
</html>
