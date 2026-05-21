<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Proforma Invoice - {{ $invoice['reference_no'] ?? '' }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
    </style>
</head>
<body style="margin:0; padding:20px 0; background:#f3f4f6; font-family:Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size:1rem; line-height:1.5; color:#1f2937; -webkit-font-smoothing:antialiased; -moz-osx-font-smoothing:grayscale;">

        {{-- Invoice Document - matching TimeTec Cloud format --}}
        <div style="display:flex; justify-content:center;">
            <div id="invoice-document" class="box" style="background: none repeat scroll 0 0 #fff; border: 1px solid #999999; border-radius: 0px; box-shadow: 0 0 10px #666666, 1px 1px 200px #fff inset; color: #333; padding: 15px 20px 15px 20px; text-shadow: 1px 1px 0 #FFFFFF; width: 729px; height:auto; position:relative; text-align: left;">

                {{-- Header --}}
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
                    {{-- Title + Bill To --}}
                    <table style="margin-bottom:20px; width:100%;">
                        <tr>
                            <td colspan="2" style="padding:0 0 40px 0; height:40px; font-size:27px; font-weight:normal; text-align:center; color:#000;">
                                PROFORMA INVOICE
                            </td>
                        </tr>
                        <tr>
                            <td style="font-size:13px; text-align:left; vertical-align:top; padding:0 0 0 6px;">
                                <strong>Bill To:</strong><br>
                                {{ $invoice['pic_name'] ?? '' }}<br>
                                @if(!empty($invoice['email'])){{ $invoice['email'] }}<br>@endif
                                @if(!empty($invoice['phone'])){{ preg_replace('/[^0-9]/', '', $invoice['phone']) }}<br>@endif
                                {{ strtoupper($invoice['customer'] ?? '-') }}<br>
                                @if(!empty($invoice['address'])){{ $invoice['address'] }},<br>@endif
                                Malaysia
                                @if(!empty($invoice['subscriber']))
                                    <br><br>
                                    <strong>Subscriber:</strong><br>
                                    {{ $invoice['subscriber']['company_name'] ?? '-' }}<br>
                                    @if(!empty($invoice['subscriber']['email'])){{ $invoice['subscriber']['email'] }}@endif
                                @endif
                            </td>
                            <td style="font-size:13px; text-align:right; vertical-align:top; padding:0;">
                                <div style="display:inline-block; text-align:left; border:1px solid #999; padding:8px 12px;">
                                    P. Invoice No: {{ $invoice['reference_no'] ?? '-' }}<br>
                                    Date: {{ $invoice['date'] ?? '-' }}<br>
                                    Status:
                                    @if(in_array(strtolower($invoice['payment_status'] ?? ''), ['paid', 'completed']))
                                        <font style="color:green; font-weight:bold;">PAID</font>
                                    @else
                                        <font style="color:red; font-weight:bold;">UNPAID</font>
                                    @endif
                                    <br>
                                    TRX Rate (RM): {{ $invoice['trx_rate'] ?? '1' }}
                                    <h2 style="margin-bottom:0; margin-top:12px; text-align:left; color:#000;">
                                        <strong>Amount Due: {{ $invoice['currency'] ?? 'MYR' }} {{ number_format($invoice['grand_total'] ?? 0, 2) }}</strong>
                                    </h2>
                                </div>
                            </td>
                        </tr>
                    </table>

                    {{-- Items Table --}}
                    <table style="width:100%;">
                        <thead>
                            <tr>
                                <th width="4%" style="font-size:13px; font-weight:bold;">No.</th>
                                <th width="59%" style="text-align:left; font-size:13px; font-weight:bold;">Description</th>
                                <th width="4%" style="font-size:13px; font-weight:bold;">Qty</th>
                                <th width="6%" style="font-size:13px; font-weight:bold;">Price</th>
                                <th width="10%" style="font-size:13px; font-weight:bold; white-space:nowrap;">Billing Cycle</th>
                                <th width="9%" style="font-size:13px; font-weight:bold;">Discount</th>
                                <th width="8%" style="font-size:13px; font-weight:bold;">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($items as $index => $item)
                                <tr>
                                    <td style="font-size:12px; vertical-align:text-top;">{{ $index + 1 }}.</td>
                                    <td style="text-align:left; font-size:12px; vertical-align:text-top;">
                                        TimeTec Suite - {{ $item['description'] }}
                                        @if(!empty($item['period']))
                                            <br>[{{ $item['period'] }}]
                                        @endif
                                    </td>
                                    <td style="font-size:12px; vertical-align:text-top;">{{ $item['quantity'] }}</td>
                                    <td style="font-size:12px; vertical-align:text-top;">{{ number_format($item['unit_price'], 2) }}</td>
                                    <td style="font-size:12px; vertical-align:text-top;">{{ $item['subscription_period'] }}</td>
                                    <td style="font-size:12px; vertical-align:text-top;">{{ number_format($item['discount'] ?? 0, 0) }}%</td>
                                    <td style="font-size:12px; vertical-align:text-top;">{{ number_format($item['total_before_tax'], 2) }}</td>
                                </tr>
                                <tr style="line-height: 0px;">
                                    <td colspan="7">&nbsp;</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" style="padding:20px 0; text-align:center; color:#999; font-size:12px;">No items found</td>
                                </tr>
                            @endforelse

                            {{-- Totals --}}
                            <tr>
                                <td colspan="5" style="border-bottom-style:hidden; font-size:12px; text-align:right; padding:4px 0;">Discount :</td>
                                <td colspan="2" style="border-bottom-style:none; font-size:12px; text-align:right; padding:4px 0;">-{{ number_format($invoice['discount'] ?? 0, 2) }}</td>
                            </tr>
                            <tr>
                                <td colspan="5" style="font-size:12px; text-align:right; padding:4px 0;">Subtotal:</td>
                                <td colspan="2" style="font-size:12px; text-align:right; padding:4px 0;">{{ number_format($invoice['subtotal'] ?? 0, 2) }}</td>
                            </tr>
                            <tr>
                                <td colspan="5" style="font-size:12px; text-align:right; padding:4px 0;">SST ({{ number_format($invoice['tax_rate'] ?? 0, 0) }}%):</td>
                                <td colspan="2" style="font-size:12px; text-align:right; padding:4px 0;">{{ number_format($invoice['tax_amount'] ?? 0, 2) }}</td>
                            </tr>
                            <tr>
                                <td colspan="5" style="font-size:12px; text-align:right; padding:4px 0;"><strong>Total sales incl SST:</strong></td>
                                <td colspan="2" style="font-size:12px; text-align:right; padding:4px 0; border-top:1px solid #AAAAAA; border-bottom:1px solid #AAAAAA;"><strong>{{ number_format($invoice['grand_total'] ?? 0, 2) }}</strong></td>
                            </tr>
                            <tr>
                                <td colspan="5" style="font-size:13px; text-align:right; padding:4px 0;"><strong>Amount Due ({{ $invoice['currency'] ?? 'MYR' }}):</strong></td>
                                <td colspan="2" style="font-size:13px; text-align:right; padding:4px 0;"><strong>{{ number_format($invoice['grand_total'] ?? 0, 2) }}</strong></td>
                            </tr>
                            <tr style="line-height: 0px;">
                                <td colspan="7">&nbsp;</td>
                            </tr>
                        </tbody>
                    </table>

                    {{-- Terms & Conditions --}}
                    <div style="border-top: 1px solid #AAAAAA; padding-top: 4px; margin-bottom: 0px; font-size:11px; text-align:left;">
                        <div style="padding:2px;">Terms & Conditions:</div>
                        <div>
                            <table width="70%" cellspacing="0" cellpadding="0" border="0" style="font-size:11px;">
                                <tbody>
                                    <tr>
                                        <td width="3%" valign="top" style="text-align:center; padding:2px;">1.</td>
                                        <td width="97%" style="text-align:left; padding:2px;">Please keep this invoice for your future reference and correspondence with TimeTec Cloud Sdn Bhd (832542-W)</td>
                                    </tr>
                                    <tr>
                                        <td width="3%" valign="top" style="text-align:center; padding:2px;">2.</td>
                                        <td width="97%" style="text-align:left; padding:2px;">All purchases with TimeTec Cloud Sdn Bhd are bound by the Terms & Conditions.</td>
                                    </tr>
                                    <tr>
                                        <td width="3%" valign="top" style="text-align:center; padding:2px;">3.</td>
                                        <td width="97%" style="text-align:left; padding:2px;">Questions about your invoice, email us at <strong>info@timeteccloud.com</strong>.</td>
                                    </tr>
                                    <tr>
                                        <td width="3%" valign="top" style="text-align:center; padding:2px;">4.</td>
                                        <td width="97%" style="text-align:left; padding:2px;">
                                            Bank Account Details (for TT payment)<br>
                                            <div style="margin-bottom:10px;">
                                                <table style="margin-left:10px;">
                                                    <tbody>
                                                        <tr>
                                                            <td style="text-align:left; padding:0; font-size:12px;">Beneficiary's Name: <b>TimeTec Cloud Sdn Bhd (832542-W)</b></td>
                                                        </tr>
                                                        <tr>
                                                            <td style="padding:0;">
                                                                <table>
                                                                    <tbody><tr>
                                                                        <td style="text-align:left; width:13%; padding:0; padding-right:2px; font-size:12px;">Banker:</td>
                                                                        <td style="text-align:left; padding:0; font-size:12px;"><b>United Overseas Bank (M) Bhd, Puchong branch</b></td>
                                                                    </tr></tbody>
                                                                </table>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td style="text-align:left; padding:0; font-size:12px;">
                                                                <table>
                                                                    <tbody><tr>
                                                                        <td style="text-align:left; width:13%; padding:0; padding-right:2px; font-size:12px;">Account No.:</td>
                                                                        <td style="text-align:left; padding:0; font-size:12px;"><b>2253 0814 40</b></td>
                                                                    </tr></tbody>
                                                                </table>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td style="padding:0;">
                                                                <table>
                                                                    <tbody><tr>
                                                                        <td style="text-align:left; width:13%; padding:0; padding-right:2px; font-size:12px;">Swift Code:</td>
                                                                        <td style="text-align:left; padding:0; font-size:12px;"><b>UOVBMYKL</b></td>
                                                                    </tr></tbody>
                                                                </table>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </main>

            </div>
        </div>

    {{-- Payment Buttons --}}
    @if(!in_array(strtolower($invoice['payment_status'] ?? ''), ['paid', 'completed']))
        <div style="max-width:500px; margin:20px auto; text-align:center;">
            <div id="paypal-button-container" style="margin-bottom:15px;"></div>
            <div id="paypal-spinner" style="display:none; padding:20px;">
                <p style="font-size:14px; color:#666;">Processing payment...</p>
            </div>
            <p id="paypal-result-message" style="font-size:14px; margin-top:10px;"></p>

            <div style="margin-top:10px;">
                <input type="button" value="Pay with Razer" class="admin-button-1"
                    style="background:#16a34a; color:#fff; border:none; padding:10px 30px; border-radius:4px; font-size:14px; font-weight:600; cursor:pointer;"
                    onclick="window.location='{{ url('/payment/razer?iIn=' . $encryptedParam) }}'">
            </div>
        </div>

        <script src="https://www.paypal.com/sdk/js?client-id={{ urlencode(\App\Http\Controllers\InvoicePaymentController::PAYPAL_CLIENT_ID) }}&currency={{ urlencode($invoice['currency'] ?? 'MYR') }}&components=buttons"></script>
        <script>
            var encryptedParam = @json($encryptedParam);
            var invoiceNo = @json($invoice['reference_no'] ?? '');

            window.paypal.Buttons({
                style: {
                    shape: "rect",
                    layout: "vertical",
                    color: "gold",
                    label: "paypal"
                },

                async createOrder() {
                    try {
                        const response = await fetch('{{ route("payment.paypal.create-order") }}', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ iIn: encryptedParam })
                        });
                        const orderData = await response.json();
                        if (orderData.id) {
                            return orderData.id;
                        }
                        const errorMessage = orderData?.details?.[0]
                            ? orderData.details[0].issue + ' ' + orderData.details[0].description
                            : (orderData.error || JSON.stringify(orderData));
                        throw new Error(errorMessage);
                    } catch (error) {
                        console.error(error);
                        document.getElementById('paypal-result-message').innerHTML =
                            '<span style="color:red;">Could not initiate PayPal Checkout: ' + error.message + '</span>';
                    }
                },

                async onApprove(data, actions) {
                    document.getElementById('paypal-spinner').style.display = 'block';
                    document.getElementById('paypal-button-container').style.display = 'none';

                    try {
                        const response = await fetch('{{ route("payment.paypal.capture-order") }}', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ iIn: encryptedParam, orderID: data.orderID })
                        });
                        const orderData = await response.json();
                        const errorDetail = orderData?.details?.[0];

                        if (errorDetail?.issue === 'INSTRUMENT_DECLINED') {
                            document.getElementById('paypal-spinner').style.display = 'none';
                            document.getElementById('paypal-button-container').style.display = 'block';
                            return actions.restart();
                        } else if (errorDetail) {
                            throw new Error(errorDetail.description);
                        } else if (orderData.status === 'COMPLETED') {
                            window.location.href = '{{ url("/payment/success") }}?invoice=' + encodeURIComponent(invoiceNo);
                        } else {
                            throw new Error(JSON.stringify(orderData));
                        }
                    } catch (error) {
                        console.error(error);
                        document.getElementById('paypal-spinner').style.display = 'none';
                        document.getElementById('paypal-button-container').style.display = 'block';
                        document.getElementById('paypal-result-message').innerHTML =
                            '<span style="color:red;">Payment failed: ' + error.message + '</span>';
                    }
                }
            }).render('#paypal-button-container');
        </script>
    @endif

    <style>
        /* TimeTec Invoice Table Styles */
        #invoice-document table { border-collapse: collapse; width: 100%; }
        #invoice-document main > table:last-of-type thead tr { border-top: 1px solid #AAAAAA; border-bottom: 1px solid #AAAAAA; }
        #invoice-document main > table:last-of-type thead th { padding: 6px 4px; text-align: center; }
        #invoice-document main > table:last-of-type tbody td { padding: 6px 4px; text-align: center; }
        #invoice-document main > table:last-of-type tbody td:nth-child(2) { text-align: left; }
        #invoice-document #invoice { border: 1px solid #999; padding: 10px 12px; }

        @media print {
            body * { visibility: hidden; }
            #invoice-document, #invoice-document * { visibility: visible; }
            #invoice-document {
                position: fixed;
                left: 0;
                top: 0;
                width: 100%;
                margin: 0;
                padding: 15px 20px;
                box-shadow: none !important;
                border: none !important;
            }
            body { margin: 0 !important; padding: 0 !important; }
        }
    </style>
</body>
</html>
