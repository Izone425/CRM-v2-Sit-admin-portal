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
            padding: 8px 0;
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
        <div class="content">
            <p><span class="greeting">Dear Reseller</span><br>
            Your commission handover ticket has been updated.@if($status === 'pending_reseller')<br>
            Please review and click the <strong>Proceed</strong> button below to forward this to TimeTec Finance.@elseif($status !== 'completed')<br>
            Please log in to the reseller portal to proceed to the next step.@endif</p>
        </div>

        <div class="info-section">
            <div class="info-row">
                <span class="info-label">ID:</span> {{ $ticketId }}<br>
                <span class="info-label">Status:</span> <span style="color: {{ $status === 'completed' ? '#059669' : '#dc2626' }}; font-weight: bold;">{{ $statusLabel }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Reseller Name:</span> {{ strtoupper($handover->reseller_name ?? 'N/A') }}<br>
                <span class="info-label">Subscriber Name:</span> {{ strtoupper($handover->subscriber_name ?? 'N/A') }}
            </div>
            <div class="info-row">
                <span class="info-label">AP Number:</span>
                @if($apInvoiceUrl)
                    <a href="{{ $apInvoiceUrl }}" style="color: #431fa1; text-decoration: underline; font-weight: bold;">{{ $handover->ap_invoice_no }}</a>
                @else
                    {{ $handover->ap_invoice_no ?? 'N/A' }}
                @endif
                <br>
                <span class="info-label">TT Number:</span>
                @if($ttInvoiceUrl)
                    <a href="{{ $ttInvoiceUrl }}" style="color: #431fa1; text-decoration: underline; font-weight: bold;">{{ $handover->tt_invoice_no }}</a>
                @else
                    {{ $handover->tt_invoice_no ?? '-' }}
                @endif
                @if($handover->autocount_inv_no)
                <br><span class="info-label">Invoice Number:</span> {{ $handover->autocount_inv_no }}
                @endif
            </div>
            <div class="info-row">
                <span class="info-label">Currency:</span> {{ $handover->currency ?? 'MYR' }}<br>
                <span class="info-label">Commission Amount:</span> {{ $handover->currency ?? 'MYR' }} {{ number_format($handover->amount, 2) }}
            </div>
            @if(isset($proceedUrl) && $proceedUrl)
            <div class="info-row" style="text-align: center; margin-top: 10px;">
                <a href="{{ $proceedUrl }}" style="display:inline-block;background:#059669;color:#ffffff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:bold;font-size:14px;">
                    Proceed to Pending Finance
                </a>
            </div>
            @endif
            @if($status === 'completed' && ((isset($paymentSlipUrl) && $paymentSlipUrl) || (isset($selfBilledEinvoiceUrl) && $selfBilledEinvoiceUrl)))
            <div class="info-row">
                @if(isset($paymentSlipUrl) && $paymentSlipUrl)
                <span class="info-label">Payment Slip:</span>
                <a href="{{ $paymentSlipUrl }}" style="color: #431fa1; text-decoration: underline; font-weight: bold;">View</a>
                @endif
                @if(isset($selfBilledEinvoiceUrl) && $selfBilledEinvoiceUrl)
                @if(isset($paymentSlipUrl) && $paymentSlipUrl)<br>@endif
                <span class="info-label">Self-Billed e-Invoice:</span>
                <a href="{{ $selfBilledEinvoiceUrl }}" style="color: #431fa1; text-decoration: underline; font-weight: bold;">View</a>
                @endif
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
