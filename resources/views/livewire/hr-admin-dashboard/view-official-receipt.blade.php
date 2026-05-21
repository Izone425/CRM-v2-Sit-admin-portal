<div>
    {{-- Loading State --}}
    @if($isLoading)
        <div style="display:flex; align-items:center; justify-content:center; padding:48px 0;">
            <svg style="width:32px; height:32px; color:#2563eb; animation:spin 1s linear infinite;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle style="opacity:0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path style="opacity:0.75;" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span style="margin-left:8px; color:#6b7280;">Loading receipt...</span>
        </div>
    @elseif($hasError)
        <div style="padding:16px; text-align:center; background:#fff; border-radius:8px; box-shadow:0 1px 3px rgba(0,0,0,0.1);">
            <p style="color:#6b7280; font-size:0.875rem;">{{ $errorMessage }}</p>
            <button wire:click="goBack" style="background:#2563eb; color:#fff; padding:6px 16px; margin-top:8px; border:none; border-radius:4px; cursor:pointer; font-size:0.75rem;">Go Back</button>
        </div>
    @else
        {{-- Action Buttons --}}
        <div style="margin-bottom:16px; display:flex; gap:8px;">
            <button wire:click="goBack" style="background:#06b6d4; color:#000; padding:8px 16px; font-size:0.875rem; font-weight:500; border:none; border-radius:4px; cursor:pointer;">Back</button>
            <button onclick="window.print()" style="background:#06b6d4; color:#000; padding:8px 16px; font-size:0.875rem; font-weight:500; border:none; border-radius:4px; cursor:pointer;">Print</button>
        </div>

        {{-- Receipt Document --}}
        <div style="display:flex; justify-content:center; padding:0 16px;">
            <div id="receipt-document" style="background:#fff; border:1px solid #999; border-radius:0; box-shadow:0 0 10px #666, 1px 1px 200px #fff inset; color:#333; padding:15px 20px; text-shadow:1px 1px 0 #fff; width:729px; text-align:left;">

                {{-- Header: Logo + Company Info --}}
                <table style="margin-bottom:20px; width:100%;">
                    <tr>
                        <td style="text-align:left; vertical-align:top; width:50%; padding:0;">
                            <img src="https://www.timeteccloud.com/templates/_admin_/images/ttc-logo.png" align="left" width="160" height="62">
                        </td>
                        <td style="text-align:right; vertical-align:top; width:50%; padding:0;">
                            <span style="font-size:11px; line-height:1.3;">
                                CP No. B16-1809-32000587<br>
                                <strong>TimeTec Cloud Sdn Bhd (832542-W)</strong><br>
                                NO. 1 &amp; 2, 18TH FLOOR, TOWER 5 @ PFCC,<br>
                                JALAN PUTERI 1/2, BANDAR PUTERI,<br>
                                47100, Puchong,<br>
                                SELANGOR, MALAYSIA.<br>
                                Tel: 603 80709933
                            </span>
                        </td>
                    </tr>
                </table>

                <main>
                    {{-- Official Receipt Banner --}}
                    <table style="margin-bottom:20px; width:100%;">
                        <tr>
                            <td colspan="2" style="padding:0 0 20px 0; height:40px;">
                                <img src="https://www.timeteccloud.com/templates/_admin_/images/officialReceipt.png" width="729" height="40">
                            </td>
                        </tr>
                        <tr>
                            {{-- Received From --}}
                            <td style="font-size:13px; text-align:left; vertical-align:top; padding:0 0 0 6px;">
                                <strong>Received From:</strong><br>
                                {{ $receipt['received_from'] ?? $receipt['company_name'] ?? '-' }}<br>
                                Malaysia
                            </td>
                            {{-- Doc Info --}}
                            <td style="font-size:13px; text-align:right; vertical-align:top; padding:0;">
                                <div style="display:inline-block; text-align:left; border:1px solid #999; padding:8px 12px;">
                                    Doc No: {{ $receipt['or_no'] ?? '-' }}<br>
                                    Date: {{ $receipt['receipt_date'] ?? '-' }}<br>
                                    Status: @if(strtoupper($receipt['status'] ?? '') === 'PAID')<font style="color:green; font-weight:bold;">PAID</font>@else<font style="color:red; font-weight:bold;">{{ strtoupper($receipt['status'] ?? 'UNPAID') }}</font>@endif<br>
                                    Received In: {{ strtoupper($receipt['payment_method'] ?? 'BANK TRANSFER') }}
                                </div>
                            </td>
                        </tr>
                    </table>

                    {{-- Items Table --}}
                    <table style="font-size:12px; width:100%; border-collapse:collapse;">
                        <thead>
                            <tr>
                                <th width="8%" style="font-size:13px; font-weight:bold; border-bottom:1px solid #aaa; padding:4px;">No.</th>
                                <th width="31%" style="text-align:left; font-size:13px; font-weight:bold; border-bottom:1px solid #aaa; padding:4px;">Description</th>
                                <th width="8%" style="font-size:13px; font-weight:bold; border-bottom:1px solid #aaa; padding:4px;">&nbsp;</th>
                                <th width="13%" style="font-size:13px; font-weight:bold; border-bottom:1px solid #aaa; padding:4px;">&nbsp;</th>
                                <th width="14%" style="font-size:13px; font-weight:bold; border-bottom:1px solid #aaa; padding:4px;">&nbsp;</th>
                                <th width="11%" style="font-size:13px; font-weight:bold; border-bottom:1px solid #aaa; padding:4px;">&nbsp;</th>
                                <th width="15%" style="font-size:13px; font-weight:bold; border-bottom:1px solid #aaa; padding:4px;">Total({{ $receipt['currency'] ?? 'MYR' }})</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="vertical-align:top; padding:8px 4px;">1.</td>
                                <td colspan="4" style="text-align:left; padding:8px 4px;">
                                    TimeTec Suite - Payment for Invoice {{ $receipt['invoice_no'] ?? '-' }}
                                    @if(!empty($invoice))
                                    <br><br>
                                    <table border="1" cellpadding="4" cellspacing="0" style="font-size:12px; border:1px solid #333; border-collapse:collapse;">
                                        <tr>
                                            <td style="padding:4px 8px; border:1px solid #333;"><b>Invoice No</b></td>
                                            <td style="padding:4px 8px; border:1px solid #333;"><b>Date</b></td>
                                            <td style="padding:4px 8px; border:1px solid #333;"><b>Amount</b></td>
                                        </tr>
                                        <tr>
                                            <td style="padding:4px 8px; border:1px solid #333;">{{ $invoice['invoice_no'] ?? '-' }}</td>
                                            <td style="padding:4px 8px; border:1px solid #333;">{{ $invoice['invoice_date'] ?? '-' }}</td>
                                            <td style="padding:4px 8px; border:1px solid #333;">{{ number_format($invoice['invoice_amount'] ?? 0, 2) }}</td>
                                        </tr>
                                    </table>
                                    @endif
                                </td>
                                <td colspan="1">&nbsp;</td>
                                <td style="vertical-align:top; padding:8px 4px;">{{ number_format($receipt['amount'] ?? 0, 2) }}</td>
                            </tr>
                            <tr><td>&nbsp;</td></tr>
                            <tr><td>&nbsp;</td></tr>
                            <tr><td>&nbsp;</td></tr>
                            <tr><td>&nbsp;</td></tr>
                            <tr>
                                <td colspan="7">&nbsp;</td>
                            </tr>
                            <tr>
                                <td colspan="5" style="font-size:12px; text-align:right;">Subtotal:</td>
                                <td colspan="2" style="font-size:12px; text-align:right;">{{ number_format($receipt['amount'] ?? 0, 2) }}</td>
                            </tr>
                            <tr>
                                <td colspan="5" style="font-size:12px; text-align:right;">Taxable Amount:</td>
                                <td colspan="2" style="font-size:12px; text-align:right;">{{ number_format($receipt['amount'] ?? 0, 2) }}</td>
                            </tr>
                            <tr>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td colspan="2" style="font-size:12px; text-align:right;"><strong>Total (Inclusive Tax):</strong></td>
                                <td colspan="2" style="font-size:12px; text-align:right;"><strong>{{ number_format($receipt['amount'] ?? 0, 2) }}</strong></td>
                            </tr>
                            <tr>
                                <td colspan="7">&nbsp;</td>
                            </tr>
                        </tbody>
                    </table>

                    {{-- Terms & Conditions --}}
                    <div style="border-top:1px solid #aaa; padding-top:10px; margin-bottom:0; font-size:11px; text-align:left;">
                        <div style="padding:2px;">Terms &amp; Conditions:</div>
                        <div>
                            <table cellspacing="0" cellpadding="0" border="0" style="font-size:11px;">
                                <tr>
                                    <td width="3%" valign="top" style="text-align:center; padding:2px;">1.</td>
                                    <td width="97%" style="text-align:left; padding:2px;">Please keep this invoice for your future reference and correspondence with TimeTec Cloud Sdn Bhd (832542-W)</td>
                                </tr>
                                <tr>
                                    <td width="3%" valign="top" style="text-align:center; padding:2px;">2.</td>
                                    <td width="97%" style="text-align:left; padding:2px;">All purchases with TimeTec Cloud Sdn Bhd are bound by the <a href="https://www.timeteccloud.com/agreement">Terms &amp; Conditions</a>.</td>
                                </tr>
                                <tr>
                                    <td width="3%" valign="top" style="text-align:center; padding:2px;">3.</td>
                                    <td width="97%" style="text-align:left; padding:2px;">Questions about your invoice, email us at <strong><a href="mailto:info@timeteccloud.com">info@timeteccloud.com</a></strong>.</td>
                                </tr>
                                <tr>
                                    <td width="3%" valign="top" style="text-align:center; padding:2px;">4.</td>
                                    <td width="97%" style="text-align:left; padding:2px;">
                                        Bank Account Details (for TT payment)<br>
                                        <table style="margin-left:10px;">
                                            <tr>
                                                <td style="text-align:left; padding:0; font-size:12px;">Banker: <b>United Overseas Bank (M) Bhd, Puchong branch</b></td>
                                            </tr>
                                            <tr>
                                                <td style="text-align:left; padding:0; font-size:12px;">Beneficiary's Name: <b>TimeTec Cloud Sdn Bhd (832542-W)</b></td>
                                            </tr>
                                            <tr>
                                                <td style="text-align:left; padding:0; font-size:12px;">
                                                    <table>
                                                        <tr>
                                                            <td style="text-align:left; padding:0; padding-right:2px; font-size:12px;">Account No.: </td>
                                                            <td style="text-align:left; padding:0; font-size:12px;"><b>2253 0814 40</b></td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="text-align:left; padding:0; font-size:12px;">Swift Code: <b>UOVBMYKL</b></td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </main>
            </div>
        </div>
    @endif

    <style>
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        @media print {
            body * { visibility: hidden; }
            #receipt-document, #receipt-document * { visibility: visible; }
            #receipt-document { position: absolute; left: 0; top: 0; width: 100%; box-shadow: none !important; border: none !important; }
        }
    </style>
</div>
