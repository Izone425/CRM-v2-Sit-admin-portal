<div>
    @if($isLoading)
        <div style="display:flex; align-items:center; justify-content:center; padding:48px 0;">
            <svg style="width:32px; height:32px; color:#06b6d4; animation:spin 1s linear infinite;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle style="opacity:0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path style="opacity:0.75;" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span style="margin-left:8px; color:#6b7280;">Loading invoice...</span>
        </div>
    @elseif($hasError)
        <div style="padding:48px; text-align:center; background:#fff; border-radius:8px; border:1px solid #e5e7eb;">
            <p style="font-size:1rem; font-weight:600; color:#dc2626; margin-bottom:8px;">Error Loading Invoice</p>
            <p style="font-size:0.875rem; color:#6b7280; margin-bottom:16px;">{{ $errorMessage }}</p>
            <button wire:click="goBack" style="background:#2563eb; color:#fff; padding:8px 20px; border-radius:6px; border:none; cursor:pointer; font-size:0.875rem;">Go Back</button>
        </div>
    @else
        {{-- Action Buttons --}}
        <div class="flex gap-2 mb-6">
            <button wire:click="goBack"
                style="background-color: #06b6d4; color: #000;"
                class="px-4 py-2 text-sm font-medium transition-colors rounded hover:bg-cyan-600">
                Back
            </button>
            <button onclick="printInvoice()"
                style="background-color: #06b6d4; color: #000;"
                class="px-4 py-2 text-sm font-medium transition-colors rounded hover:bg-cyan-600">
                Print
            </button>
            @php
                $invStatus  = strtolower($invoice['status'] ?? '');
                $payStatus  = strtolower($invoice['payment_status'] ?? '');
                $isPaid     = $invStatus === 'paid' || $payStatus === 'paid';
            @endphp
            @if($invStatus === 'pending' && !$isPaid)
                <button wire:click="openPaymentModal"
                    style="background-color: #16a34a; color: #000;"
                    class="px-4 py-2 text-sm font-medium transition-colors rounded hover:bg-green-700">
                    Add Payment
                </button>
                <button wire:click="openCancelModal"
                    style="background-color: #dc2626; color: #000;"
                    class="px-4 py-2 text-sm font-medium transition-colors rounded hover:bg-red-700">
                    Cancel Invoice
                </button>
                <button wire:click="copyPaymentLink"
                    style="background-color: #4b5563; color: #000;"
                    class="px-4 py-2 text-sm font-medium transition-colors rounded hover:bg-gray-700">
                    Copy Payment Link
                </button>
            @elseif(in_array(strtolower($invoice['status'] ?? ''), ['cancel', 'cancelled']))
                <button wire:click="reactivateInvoice"
                    style="background-color: #16a34a; color: #fff;"
                    class="px-4 py-2 text-sm font-medium transition-colors rounded hover:bg-green-700">
                    Reactivate Invoice
                </button>
            @endif
        </div>
        <br>
        {{-- Cancelled Hint Box --}}
        @if(in_array(strtolower($invoice['status'] ?? ''), ['cancel', 'cancelled']))
        <div style="display:flex; justify-content:center;">
            <div style="background:#fef2f2; border:1px solid #fca5a5; border-left:4px solid #dc2626; border-radius:8px; padding:16px 20px; margin-bottom:16px; width:729px;">
                <div style="display:flex; align-items:start; gap:12px;">
                    <svg style="width:24px; height:24px; color:#dc2626; flex-shrink:0; margin-top:2px;" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                        <p style="font-weight:700; color:#991b1b; margin:0; font-size:1rem;">THIS INVOICE HAS BEEN CANCELLED</p>
                        @if(!empty($invoice['cancel_remark']))
                            <p style="color:#991b1b; margin:6px 0 0; font-size:0.9rem; font-weight:600;">REASON: {{ strtoupper($invoice['cancel_remark']) }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endif

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
                            @php $rowNum = 0; @endphp
                            @forelse($itemGroups as $group)
                                @if(!empty($group['label']))
                                    <tr style="background:#e8f0fe;">
                                        <td colspan="7" style="font-size:12px; font-weight:bold; color:#005baa; padding:6px 8px; text-align:left;">
                                            {{ $group['label'] }}@if(!empty($group['period'])) [{{ $group['period'] }}]@endif
                                        </td>
                                    </tr>
                                @endif
                                @foreach($group['items'] as $item)
                                    @php $rowNum++; @endphp
                                    <tr>
                                        <td style="font-size:12px; vertical-align:text-top;">{{ $rowNum }}.</td>
                                        <td style="text-align:left; font-size:12px; vertical-align:text-top;">
                                            TimeTec Suite - {{ $item['description'] }}
                                            @if(empty($group['label']) && !empty($item['period']))
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
                                @endforeach
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
    @endif

    {{-- Payment Modal --}}
    @if($showPaymentModal)
        <div style="position:fixed; inset:0; z-index:50; display:flex; align-items:center; justify-content:center; background:rgba(0,0,0,0.5);" wire:click.self="closePaymentModal">
            <div style="background:#fff; border-radius:8px; width:640px; max-height:90vh; overflow-y:auto; box-shadow:0 20px 60px rgba(0,0,0,0.3);">
                {{-- Header --}}
                <div style="padding:20px 24px; border-bottom:1px solid #e5e7eb;">
                    <h2 style="font-size:1.25rem; font-weight:700; color:#111827; margin:0;">Add Official Receipt</h2>
                    <p style="font-size:0.8rem; color:#6b7280; margin:4px 0 0;">This is created when receive payment from customer for topup credit or payment for invoice.</p>
                </div>

                {{-- Form --}}
                <div style="padding:24px;">
                    <table style="width:100%; border-collapse:collapse;">
                        {{-- Company --}}
                        <tr>
                            <td style="padding:12px 0; font-size:0.875rem; font-weight:600; color:#374151; width:160px; vertical-align:top;">Company <span style="color:red;">*</span>:</td>
                            <td style="padding:12px 0;">
                                <input type="text" wire:model="paymentForm.company" readonly
                                    style="width:100%; padding:8px 12px; border:1px solid #d1d5db; border-radius:4px; font-size:0.875rem; background:#f3f4f6; color:#374151;">
                            </td>
                        </tr>
                        {{-- Total Amount --}}
                        <tr>
                            <td style="padding:12px 0; font-size:0.875rem; font-weight:600; color:#374151; vertical-align:top;">Total Amount <span style="color:red;">*</span>:</td>
                            <td style="padding:12px 0;">
                                <div style="display:flex; gap:8px;">
                                    <select wire:model="paymentForm.currency" style="padding:8px 12px; border:1px solid #d1d5db; border-radius:4px; font-size:0.875rem; background:#fff; width:100px;">
                                        <option value="MYR">MYR</option>
                                        <option value="USD">USD</option>
                                        <option value="SGD">SGD</option>
                                    </select>
                                    <input type="number" wire:model="paymentForm.amount" step="0.01"
                                        style="flex:1; padding:8px 12px; border:1px solid #d1d5db; border-radius:4px; font-size:0.875rem;">
                                </div>
                            </td>
                        </tr>
                        {{-- Bill Title --}}
                        <tr>
                            <td style="padding:12px 0; font-size:0.875rem; font-weight:600; color:#374151; vertical-align:top;">Bill Title:</td>
                            <td style="padding:12px 0;">
                                <input type="text" wire:model="paymentForm.bill_title"
                                    style="width:100%; padding:8px 12px; border:1px solid #d1d5db; border-radius:4px; font-size:0.875rem;">
                                <p style="font-size:0.75rem; color:#9ca3af; margin:4px 0 0;">E.g. Top Up Credit</p>
                            </td>
                        </tr>
                        {{-- Payment Method --}}
                        <tr>
                            <td style="padding:12px 0; font-size:0.875rem; font-weight:600; color:#374151; vertical-align:top;">Payment Method <span style="color:red;">*</span>:</td>
                            <td style="padding:12px 0;">
                                <select wire:model="paymentForm.payment_method" style="padding:8px 12px; border:1px solid #d1d5db; border-radius:4px; font-size:0.875rem; background:#fff;">
                                    <option value="Bank Transfer">Bank Transfer</option>
                                    <option value="Credit Card">Credit Card</option>
                                    <option value="Online Banking">Online Banking</option>
                                    <option value="Cheque">Cheque</option>
                                    <option value="Cash">Cash</option>
                                    <option value="Others">Others</option>
                                </select>
                            </td>
                        </tr>
                        {{-- Ref No --}}
                        <tr>
                            <td style="padding:12px 0; font-size:0.875rem; font-weight:600; color:#374151; vertical-align:top;">Ref No.:</td>
                            <td style="padding:12px 0;">
                                <input type="text" wire:model="paymentForm.ref_no"
                                    style="width:100%; padding:8px 12px; border:1px solid #d1d5db; border-radius:4px; font-size:0.875rem;">
                                <p style="font-size:0.75rem; color:#9ca3af; margin:4px 0 0;">E.g. PBB 12345678</p>
                            </td>
                        </tr>
                        {{-- Remark --}}
                        <tr>
                            <td style="padding:12px 0; font-size:0.875rem; font-weight:600; color:#374151; vertical-align:top;">Remark:</td>
                            <td style="padding:12px 0;">
                                <textarea wire:model="paymentForm.remark" rows="4"
                                    style="width:100%; padding:8px 12px; border:1px solid #d1d5db; border-radius:4px; font-size:0.875rem; resize:vertical;"></textarea>
                            </td>
                        </tr>
                    </table>
                </div>

                {{-- Footer --}}
                <div style="padding:16px 24px; border-top:1px solid #e5e7eb; display:flex; gap:8px;">
                    <button wire:click="submitPayment"
                        style="background:#5bb5d1; color:#fff; padding:8px 24px; border:none; border-radius:4px; font-size:0.875rem; font-weight:600; cursor:pointer;">
                        Continue
                    </button>
                    <button wire:click="closePaymentModal"
                        style="background:#5bb5d1; color:#fff; padding:8px 24px; border:none; border-radius:4px; font-size:0.875rem; font-weight:600; cursor:pointer;">
                        Back
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Cancel Invoice Modal --}}
    @if($showCancelModal)
        <div style="position:fixed; inset:0; z-index:50; display:flex; align-items:center; justify-content:center; background:rgba(0,0,0,0.5);" wire:click.self="closeCancelModal">
            <div style="background:#fff; border-radius:8px; width:500px; max-height:90vh; overflow-y:auto; box-shadow:0 20px 60px rgba(0,0,0,0.3);">
                <div style="padding:20px 24px; border-bottom:1px solid #e5e7eb;">
                    <h2 style="font-size:1.25rem; font-weight:700; color:#dc2626; margin:0;">Cancel Invoice</h2>
                    <p style="font-size:0.8rem; color:#6b7280; margin:4px 0 0;">Are you sure you want to cancel this invoice?</p>
                </div>

                <div style="padding:24px;">
                    <table style="width:100%; border-collapse:collapse;">
                        <tr>
                            <td style="padding:12px 0; font-size:0.875rem; font-weight:600; color:#374151; width:120px; vertical-align:top;">Doc No:</td>
                            <td style="padding:12px 0;">
                                <input type="text" wire:model="cancelForm.doc_no" readonly
                                    style="width:100%; padding:8px 12px; border:1px solid #d1d5db; border-radius:4px; font-size:0.875rem; background:#f3f4f6; color:#374151;">
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:12px 0; font-size:0.875rem; font-weight:600; color:#374151; vertical-align:top;">Remark <span style="color:red;">*</span>:</td>
                            <td style="padding:12px 0;">
                                <textarea wire:model="cancelForm.remark" rows="4" placeholder="Reason for cancellation..."
                                    style="width:100%; padding:8px 12px; border:1px solid #d1d5db; border-radius:4px; font-size:0.875rem; resize:vertical; text-transform:uppercase;"></textarea>
                                @error('cancelForm.remark') <p style="color:#dc2626; font-size:0.75rem; margin:4px 0 0;">{{ $message }}</p> @enderror
                            </td>
                        </tr>
                    </table>
                </div>

                <div style="padding:16px 24px; border-top:1px solid #e5e7eb; display:flex; gap:8px;">
                    <button wire:click="submitCancelInvoice"
                        style="background:#dc2626; color:#fff; padding:8px 24px; border:none; border-radius:4px; font-size:0.875rem; font-weight:600; cursor:pointer;">
                        Yes, Cancel Invoice
                    </button>
                    <button wire:click="closeCancelModal"
                        style="background:#6b7280; color:#fff; padding:8px 24px; border:none; border-radius:4px; font-size:0.875rem; font-weight:600; cursor:pointer;">
                        Back
                    </button>
                </div>
            </div>
        </div>
    @endif

    <style>
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

        /* TimeTec Invoice Table Styles */
        #invoice-document table { border-collapse: collapse; width: 100%; }
        #invoice-document main > table:last-of-type thead tr { border-top: 1px solid #AAAAAA; border-bottom: 1px solid #AAAAAA; }
        #invoice-document main > table:last-of-type thead th { padding: 6px 4px; text-align: center; }
        #invoice-document main > table:last-of-type tbody td { padding: 6px 4px; text-align: center; }
        #invoice-document main > table:last-of-type tbody td:nth-child(2) { text-align: left; }
        #invoice-document #invoice { border: 1px solid #999; padding: 10px 12px; }

        @media print { }
        /* Print styles injected via JS to avoid Filament interference */
    </style>


    @script
    <script>
        window.printInvoice = function() {
            var invoiceEl = document.getElementById('invoice-document');

            // Clone the element to avoid modifying the original
            var clone = invoiceEl.cloneNode(true);
            // Remove box-shadow and border for print
            clone.style.boxShadow = 'none';
            clone.style.border = 'none';
            clone.style.width = '100%';
            clone.style.padding = '0';

            var content = clone.outerHTML;

            // Collect all stylesheets from current page
            var styles = '';
            document.querySelectorAll('style').forEach(function(s) {
                styles += s.outerHTML;
            });
            // Also collect linked stylesheets
            document.querySelectorAll('link[rel="stylesheet"]').forEach(function(l) {
                styles += l.outerHTML;
            });

            var iframe = document.createElement('iframe');
            iframe.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:none;';
            document.body.appendChild(iframe);

            var doc = iframe.contentWindow.document;
            doc.open();
            doc.write('<!DOCTYPE html><html><head><title>Sales Invoice</title>');
            doc.write(styles);
            doc.write('<style>@page{margin:8mm 10mm;size:A4}body{margin:0;padding:0;}#invoice-document{box-shadow:none!important;border:none!important;width:100%!important;padding:0!important;}</style>');
            doc.write('</head><body>' + content + '</body></html>');
            doc.close();

            // Wait for linked stylesheets to load before printing
            setTimeout(function() {
                iframe.contentWindow.focus();
                iframe.contentWindow.print();
                setTimeout(function() { document.body.removeChild(iframe); }, 1000);
            }, 500);
        };

        $wire.on('copy-to-clipboard', function(params) {
            var text = (params && params[0] && params[0].text) ? params[0].text : (params.text || '');
            if (!text) return;
            navigator.clipboard.writeText(text).then(function() {}).catch(function() {
                var el = document.createElement('textarea');
                el.value = text;
                document.body.appendChild(el);
                el.select();
                document.execCommand('copy');
                document.body.removeChild(el);
            });
        });
    </script>
    @endscript
</div>
