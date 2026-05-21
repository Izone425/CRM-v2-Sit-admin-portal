<?php

namespace App\Http\Controllers;

use App\Models\HrSalesInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoicePaymentController extends Controller
{
    // PayPal V2 REST API credentials
    const PAYPAL_CLIENT_ID = 'AcNO-D51oim4iydy2yovnocNf3EdXzt_Jqg1chyLDlLnsQ4lf07mD5foplPyfZrhKMDEpucMaC5ys529';
    const PAYPAL_CLIENT_SECRET = 'EKyjmf-3THRgj9xfzKqIc0wjYy3v30uLCZjNao75Rg03A59Q2yLW1yXlrAYJNn_ciTFm00Pl93hwgiog';
    const PAYPAL_API_URL = 'https://api-m.paypal.com';

    // Razer credentials
    const RAZER_MERCHANT_ID = 'timeteccloud';
    const RAZER_VERIFY_KEY = '55ab22060800f6f49a6a2ef0467adc27';
    const RAZER_URL = 'https://pay.merchant.razer.com/RMS/pay/timeteccloud/';

    /**
     * Show invoice payment page with PayPal/Razer buttons
     */
    public function show(Request $request)
    {
        $iIn = $request->query('iIn');
        if (!$iIn) abort(404);

        $record = $this->decryptAndFindInvoice($iIn);
        if (!$record) abort(404, 'Invoice not found');

        $invoice = $this->buildInvoiceData($record);
        $items = $this->buildItems($record);

        return view('invoice-payment', [
            'invoice' => $invoice,
            'items' => $items,
            'encryptedParam' => $iIn,
        ]);
    }

    /**
     * PayPal V2 — create order via REST API
     */
    public function paypalCreateOrder(Request $request)
    {
        $iIn = $request->input('iIn');
        if (!$iIn) return response()->json(['error' => 'Invalid invoice'], 400);

        $record = $this->decryptAndFindInvoice($iIn);
        if (!$record) return response()->json(['error' => 'Invoice not found'], 404);

        $invoice = $this->buildInvoiceData($record);

        if (in_array(strtolower($invoice['payment_status'] ?? ''), ['paid', 'completed'])) {
            return response()->json(['error' => 'Invoice already paid'], 400);
        }

        $accessToken = $this->getPaypalAccessToken();
        if (!$accessToken) {
            return response()->json(['error' => 'Failed to authenticate with PayPal'], 500);
        }

        $amount = number_format($invoice['grand_total'], 2, '.', '');
        $currency = $invoice['currency'] ?? 'MYR';

        $orderData = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'amount' => [
                    'currency_code' => $currency,
                    'value' => $amount,
                ],
                'description' => 'Payment for Invoice ' . $record->invoice_no,
                'invoice_id' => $record->invoice_no,
            ]],
        ];

        $response = \Illuminate\Support\Facades\Http::withToken($accessToken)
            ->post(self::PAYPAL_API_URL . '/v2/checkout/orders', $orderData);

        $result = $response->json();

        Log::info('PayPal v2 order created', [
            'invoice_no' => $record->invoice_no,
            'order_id' => $result['id'] ?? null,
            'amount' => $amount,
            'currency' => $currency,
        ]);

        if (!empty($result['id'])) {
            return response()->json($result);
        }

        return response()->json(['error' => 'Failed to create PayPal order', 'details' => $result], 500);
    }

    /**
     * PayPal V2 — capture order via REST API
     */
    public function paypalCaptureOrder(Request $request)
    {
        $iIn = $request->input('iIn');
        $orderID = $request->input('orderID');

        if (!$iIn || !$orderID) {
            return response()->json(['error' => 'Missing parameters'], 400);
        }

        $record = $this->decryptAndFindInvoice($iIn);
        if (!$record) return response()->json(['error' => 'Invoice not found'], 404);

        $accessToken = $this->getPaypalAccessToken();
        if (!$accessToken) {
            return response()->json(['error' => 'Failed to authenticate with PayPal'], 500);
        }

        $response = \Illuminate\Support\Facades\Http::withToken($accessToken)
            ->withBody('{}', 'application/json')
            ->post(self::PAYPAL_API_URL . '/v2/checkout/orders/' . $orderID . '/capture');

        $result = $response->json();

        if (!empty($result['status']) && $result['status'] === 'COMPLETED') {
            $capture = $result['purchase_units'][0]['payments']['captures'][0] ?? [];
            $txnId = $capture['id'] ?? $orderID;
            $payerEmail = $result['payer']['email_address'] ?? '';

            Log::info('PayPal v2 capture completed', [
                'invoice_no' => $record->invoice_no,
                'order_id' => $orderID,
                'txn_id' => $txnId,
                'payer_email' => $payerEmail,
                'amount' => $capture['amount']['value'] ?? '',
            ]);

            $this->markInvoiceAsPaid($record->invoice_no, $txnId, 'PayPal');

            return response()->json($result);
        }

        Log::error('PayPal v2 capture failed', [
            'invoice_no' => $record->invoice_no,
            'order_id' => $orderID,
            'response' => $result,
        ]);

        return response()->json(['error' => 'Capture failed', 'details' => $result], 500);
    }

    /**
     * Get PayPal OAuth2 access token
     */
    protected function getPaypalAccessToken(): ?string
    {
        $response = \Illuminate\Support\Facades\Http::asForm()
            ->withBasicAuth(self::PAYPAL_CLIENT_ID, self::PAYPAL_CLIENT_SECRET)
            ->post(self::PAYPAL_API_URL . '/v1/oauth2/token', [
                'grant_type' => 'client_credentials',
            ]);

        if ($response->successful()) {
            return $response->json('access_token');
        }

        Log::error('PayPal access token failed', ['status' => $response->status(), 'body' => $response->body()]);
        return null;
    }

    /**
     * Razer — redirect to Razer payment gateway
     */
    public function razer(Request $request)
    {
        $iIn = $request->query('iIn');
        if (!$iIn) abort(404);

        $record = $this->decryptAndFindInvoice($iIn);
        if (!$record) abort(404, 'Invoice not found');

        $invoice = $this->buildInvoiceData($record);
        $sw = $record->softwareHandover;
        $companyDetail = $sw?->lead?->companyDetail;

        $orderId = 'ORD_' . now()->format('Ymd') . '_' . rand(1000, 9999);
        $amount = number_format($invoice['grand_total'], 2, '.', '');
        $currency = $invoice['currency'] ?? 'MYR';

        // SHA512 vcode for TTCloud account type
        $vcode = hash('sha512', $amount . self::RAZER_MERCHANT_ID . $orderId . self::RAZER_VERIFY_KEY . $currency);

        $billName = $invoice['pic_name'] ?? '';
        $billEmail = $invoice['email'] ?? '';
        $billPhone = preg_replace('/[^0-9]/', '', $invoice['phone'] ?? '');
        $billDesc = 'Payment for Invoice ' . $record->invoice_no;

        $razerUrl = self::RAZER_URL . '?' . http_build_query([
            'amount' => $amount,
            'orderid' => $orderId,
            'currency' => $currency,
            'bill_name' => $billName,
            'bill_email' => $billEmail,
            'bill_mobile' => $billPhone,
            'bill_desc' => $billDesc,
            'vcode' => $vcode,
        ]);

        Log::info('Razer payment initiated', [
            'invoice_no' => $record->invoice_no,
            'order_id' => $orderId,
            'amount' => $amount,
            'currency' => $currency,
        ]);

        return redirect()->away($razerUrl);
    }

    /**
     * PayPal IPN (legacy - kept for backward compatibility)
     */
    public function paypalIpn(Request $request)
    {
        Log::info('PayPal IPN received (legacy)', ['payload' => $request->all()]);
        return response('OK', 200);
    }

    /**
     * Payment success page (fallback if IPN doesn't arrive)
     */
    public function success(Request $request)
    {
        $invoiceNo = $request->query('invoice', '');
        $txnId = $request->query('tx', $request->query('txn_id', ''));

        Log::info('Payment success page visited', ['invoice_no' => $invoiceNo, 'txn_id' => $txnId]);

        // Fallback: mark as paid if IPN hasn't processed it yet
        if ($invoiceNo) {
            $this->markInvoiceAsPaid($invoiceNo, $txnId ?: 'PayPal-Success-Page', 'PayPal');
        }

        return view('payment-result', [
            'status' => 'success',
            'message' => 'Payment completed successfully.',
            'invoiceNo' => $invoiceNo,
        ]);
    }

    /**
     * Payment failed page
     */
    public function failed(Request $request)
    {
        $invoiceNo = $request->query('invoice', '');

        Log::info('Payment failed page visited', ['invoice_no' => $invoiceNo]);

        return view('payment-result', [
            'status' => 'failed',
            'message' => 'Payment failed. Please try again.',
            'invoiceNo' => $invoiceNo,
        ]);
    }

    /**
     * Mark invoice as paid and create official receipt (idempotent - safe to call multiple times)
     */
    protected function markInvoiceAsPaid(string $invoiceNo, string $refNo = '-', string $paymentMethod = 'Online'): void
    {
        $invoice = HrSalesInvoice::where('invoice_no', $invoiceNo)->first();

        if (!$invoice) {
            Log::warning('Invoice not found for payment update', ['invoice_no' => $invoiceNo]);
            return;
        }

        if ($invoice->payment_status === 'paid') {
            Log::info('Invoice already paid, skipping', ['invoice_no' => $invoiceNo]);
            return;
        }

        try {
            DB::beginTransaction();

            $invoice->update([
                'payment_status' => 'paid',
                'status' => 'paid',
                'payment_method' => $paymentMethod,
            ]);

            Log::info('Invoice marked as paid', [
                'invoice_no' => $invoiceNo,
                'ref_no' => $refNo,
                'payment_method' => $paymentMethod,
            ]);

            // Create official receipt if not already exists
            if (!\App\Models\HrOfficialReceipt::where('invoice_no', $invoiceNo)->exists()) {
                $prefix = 'OR' . now()->format('ym');
                $lastOr = \App\Models\HrOfficialReceipt::where('or_no', 'like', $prefix . '%')
                    ->orderBy('or_no', 'desc')
                    ->value('or_no');
                $nextSeq = $lastOr ? ((int) substr($lastOr, strlen($prefix))) + 1 : 1;
                $orNo = $prefix . str_pad($nextSeq, 6, '0', STR_PAD_LEFT);

                $handover = $invoice->softwareHandover;

                \App\Models\HrOfficialReceipt::create([
                    'or_no' => $orNo,
                    'receipt_date' => now()->toDateString(),
                    'company_name' => $invoice->company_name,
                    'subscriber_name' => $handover?->pic_name,
                    'description' => $paymentMethod . ' Payment for ' . $invoiceNo . ($refNo !== '-' ? ' (Ref: ' . $refNo . ')' : ''),
                    'currency' => $invoice->currency ?? 'MYR',
                    'amount' => $invoice->invoice_amount ?? 0,
                    'status' => 'paid',
                    'created_by' => $paymentMethod . ' Payment',
                    'invoice_no' => $invoiceNo,
                    'payment_method' => $paymentMethod,
                    'ref_no' => $refNo,
                    'software_handover_id' => $invoice->software_handover_id,
                    'handover_id' => $invoice->handover_id,
                ]);

                Log::info('Official receipt created for payment', [
                    'invoice_no' => $invoiceNo,
                    'or_no' => $orNo,
                    'ref_no' => $refNo,
                    'payment_method' => $paymentMethod,
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to mark invoice as paid', [
                'invoice_no' => $invoiceNo,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Decrypt iIn param and find invoice
     */
    protected function decryptAndFindInvoice(string $iIn): ?HrSalesInvoice
    {
        $decrypted = openssl_decrypt(base64_decode($iIn), 'AES-128-ECB', 'Epicamera@99');
        if ($decrypted === false) return null;

        return HrSalesInvoice::with(['items.product', 'softwareHandover.lead.companyDetail'])->find((int) $decrypted);
    }

    protected function buildInvoiceData(HrSalesInvoice $record): array
    {
        $sw = $record->softwareHandover;
        $companyDetail = $sw?->lead?->companyDetail;
        $companyName = $record->company_name ?? $sw?->company_name ?? '-';
        $picName = $sw?->pic_name ?? $companyDetail?->contact_person ?? '';
        $email = $companyDetail?->email ?? '';
        $phone = $companyDetail?->mobile_phone ?? $companyDetail?->phone ?? '';

        $items = $this->buildItems($record);
        $subtotal = array_sum(array_column($items, 'total_before_tax'));
        $totalDiscount = 0;
        foreach ($items as $item) {
            $totalDiscount += ($item['discount'] / 100) * $item['total_before_tax'];
        }

        $taxRate = (float) ($record->tax_rate ?? 0);
        $taxableAmount = $subtotal - $totalDiscount;
        $taxAmount = $taxableAmount * ($taxRate / 100);
        $grandTotal = $taxableAmount + $taxAmount;

        // Check reseller
        $subscriber = null;
        $displayCustomer = $companyName;
        $displayPic = $picName;
        $displayEmail = $email;
        $displayPhone = $phone;
        $displayAddress = $companyDetail?->address ?? '';

        if ($sw && $sw->reseller_id) {
            $resellerBasic = DB::table('resellers')->find($sw->reseller_id);
            $resellerV2 = DB::table('reseller_v2')->where('reseller_id', $resellerBasic?->id)->first();
            if ($resellerV2 || $resellerBasic) {
                $displayCustomer = $resellerV2?->company_name ?? $resellerBasic?->company_name ?? '-';
                $displayPic = $resellerV2?->name ?? $resellerV2?->contact_person ?? '';
                $displayEmail = $resellerV2?->email ?? '';
                $displayPhone = $resellerV2?->phone ?? '';
                $displayAddress = trim(implode(', ', array_filter([
                    $resellerV2?->address ?? '', $resellerV2?->city ?? '', $resellerV2?->state ?? '', $resellerV2?->country ?? '',
                ])));
                $subscriber = ['company_name' => $companyName, 'email' => $email];
            }
        }

        return [
            'reference_no' => $record->invoice_no,
            'date' => $record->invoice_date?->format('d-m-Y') ?? '-',
            'payment_status' => strtolower($record->payment_status ?? 'unpaid'),
            'currency' => $record->currency ?? 'MYR',
            'tax_rate' => $taxRate,
            'trx_rate' => $record->currency === 'USD' ? '4.1765' : '1',
            'customer' => $displayCustomer,
            'pic_name' => $displayPic,
            'email' => $displayEmail,
            'phone' => $displayPhone,
            'address' => $displayAddress,
            'subtotal' => round($subtotal, 2),
            'discount' => round($totalDiscount, 2),
            'tax_amount' => round($taxAmount, 2),
            'grand_total' => round($grandTotal, 2),
            'subscriber' => $subscriber,
        ];
    }

    protected function buildItems(HrSalesInvoice $record): array
    {
        $items = [];
        foreach ($record->items as $item) {
            $period = null;
            if ($item->license_start_date && $item->license_end_date) {
                $period = $item->license_start_date->format('d/m/Y') . ' - ' . $item->license_end_date->format('d/m/Y');
            }
            $items[] = [
                'description' => $item->license_type ?? 'TimeTec License',
                'quantity' => $item->quantity ?? 0,
                'unit_price' => (float) ($item->unit_price ?? 0),
                'subscription_period' => $item->subscription_period ?? 1,
                'discount' => (float) ($item->discount ?? 0),
                'total_before_tax' => (float) ($item->total_before_tax ?? 0),
                'period' => $period,
            ];
        }
        return $items;
    }
}
