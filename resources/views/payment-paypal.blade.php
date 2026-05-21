<html>
<body onload="document.form1.submit()">
<form id="form1" name="form1" action="{{ $paypalUrl }}" method="post">
    <div style="clear:both; height:250px;"></div>
    <center><p style="font-size:16px; color:#333;">Redirecting to PayPal...</p></center>

    <input type="hidden" name="cmd" value="_cart">
    <input type="hidden" name="upload" value="1">
    <input type="hidden" name="business" value="{{ $paypalBusiness }}">
    <input type="hidden" name="currency_code" value="{{ $invoice['currency'] ?? 'MYR' }}">
    <input type="hidden" name="no_shipping" value="1">
    <input type="hidden" name="custom" value="ORDER, {{ $record->id }}">
    <input type="hidden" name="invoice" value="{{ $record->invoice_no }}">
    <input type="hidden" name="item_name_1" value="Payment for Invoice {{ $record->invoice_no }}">
    <input type="hidden" name="amount_1" value="{{ number_format($invoice['grand_total'], 2, '.', '') }}">

    <input type="hidden" name="address1" value="{{ $companyDetail?->company_address1 ?? '' }}">
    <input type="hidden" name="address2" value="{{ $companyDetail?->company_address2 ?? '' }}">
    <input type="hidden" name="city" value="{{ $companyDetail?->state ?? '' }}">
    <input type="hidden" name="country" value="{{ $companyDetail?->country ?? 'Malaysia' }}">
    <input type="hidden" name="email" value="{{ $invoice['email'] ?? '' }}">
    <input type="hidden" name="first_name" value="{{ $invoice['pic_name'] ?? '' }}">
    <input type="hidden" name="zip" value="{{ $companyDetail?->postcode ?? '' }}">

    <input type="hidden" name="notify_url" value="{{ $notifyUrl }}">
    <input type="hidden" name="return" value="{{ $returnUrl }}">
    <input type="hidden" name="cancel_return" value="{{ $cancelUrl }}">
</form>
</body>
</html>
