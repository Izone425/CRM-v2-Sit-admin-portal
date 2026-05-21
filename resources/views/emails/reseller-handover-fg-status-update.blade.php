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
        <div class="header">
            <div class="greeting">Dear Reseller</div>
        </div>

        <div class="content">
            <p>Your ticket has been updated.</p>
        </div>

        <div class="info-section">
            <div class="info-row">
                <span class="info-label">ID:</span> {{ $ticketId }}<br>
                <span class="info-label">Category:</span> {{ $category }}<br>
                <span class="info-label">Status:</span> <span style="color: #dc2626; font-weight: bold;">{{ $statusLabel }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Reseller Company Name:</span> {{ $handover->reseller_company_name }}<br>
                <span class="info-label">Subscriber Company Name:</span> {{ $handover->subscriber_name }}<br>
                @if(isset($isRejected) && $isRejected)
                <br><span class="info-label" style="color: #dc2626;">Reason Reject:</span> <span style="color: #dc2626;">{!! nl2br(e($rejectReason ?? 'N/A')) !!}</span>
                @elseif(isset($rejectionReason) && $rejectionReason)
                <br><span class="info-label" style="color: #dc2626;">Reason Reject:</span> <span style="color: #dc2626;">{!! nl2br(e($rejectionReason)) !!}</span>
                @endif
            </div>
            @if($status !== 'completed' && $handover->timetec_proforma_invoice)
            <div class="info-row">
                <span class="info-label">TT Proforma Invoice:</span>
                @if($invoiceUrl)
                    <a href="{{ $invoiceUrl }}" style="color: #431fa1; text-decoration: underline; font-weight: bold;">{{ $handover->timetec_proforma_invoice }}</a>
                @else
                    {{ $handover->timetec_proforma_invoice }}
                @endif
                @if(!empty($handover->admin_reseller_remark) && $handover->status === 'pending_quotation_confirmation')
                <br><span class="info-label">Admin Remark:</span> {{ $handover->admin_reseller_remark }}
                @endif
                @if(isset($autocountInvoiceNumber) && $autocountInvoiceNumber)
                <br><span class="info-label">Invoice:</span>
                @if(isset($autocountInvoiceUrl) && $autocountInvoiceUrl)
                    <a href="{{ $autocountInvoiceUrl }}" style="color: #431fa1; text-decoration: underline; font-weight: bold;">{{ $autocountInvoiceNumber }}</a>
                @else
                    {{ $autocountInvoiceNumber }}
                @endif
                @endif
                {{-- @if(isset($selfBilledInvoiceUrl) && $selfBilledInvoiceUrl)
                <br><span class="info-label">Self Billed Invoice [Draft]:</span>
                    <a href="{{ $selfBilledInvoiceUrl }}" style="color: #431fa1; text-decoration: underline; font-weight: bold;">{{ $selfBilledInvoiceNumber ?? 'View' }}</a>
                @endif --}}
            </div>
            @endif
        </div>

        @if(isset($actionResult))
        <div style="margin: 25px 0; padding: 15px; border-radius: 8px; text-align: center; background-color: {{ $actionResult === 'proceed' ? '#d1fae5' : ($actionResult === 'cancel' ? '#fee2e2' : '#fef3c7') }}; border: 1px {{ $actionResult === 'proceed' ? '#059669' : ($actionResult === 'cancel' ? '#dc2626' : '#f59e0b') }};">
            @if($actionResult === 'proceed')
                <span style="font-weight: bold; color: #059669;">You have answered Proceed at {{ $actionTime }}</span>
            @elseif($actionResult === 'cancel')
                <span style="font-weight: bold; color: #dc2626;">You have answered Cancel Order at {{ $actionTime }}</span>
            @else
                <span style="font-weight: bold; color: #92400e;">{!! nl2br(e($actionMessage)) !!}</span>
            @endif
        </div>
        @elseif($status === 'pending_quotation_confirmation' && isset($proceedUrl) && $proceedUrl)
        <div style="margin: 25px 0; text-align: center;">
            <a href="{{ $proceedUrl }}" style="display: inline-block; padding: 12px 30px; background-color: #059669; color: white; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 14px; margin-right: 10px;">
                Proceed
            </a>
            <a href="{{ $cancelUrl }}" style="display: inline-block; padding: 12px 30px; background-color: #dc2626; color: white; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 14px;">
                Cancel Order
            </a>
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
