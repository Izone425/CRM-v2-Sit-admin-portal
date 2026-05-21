<?php

use App\Livewire\AcceptInvitation;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\QuotePdfController;
use App\Models\LeadSource;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CustomerActivationController;
use App\Http\Controllers\CustomerAuthController;
use App\Http\Controllers\GenerateHardwareHandoverPdfController;
use App\Http\Controllers\GenerateHardwareHandoverV2PdfController;
use App\Http\Controllers\GenerateProformaInvoicePdfController;
use App\Http\Controllers\GenerateQuotationPdfController;
use App\Http\Controllers\GenerateSoftwareHandoverPdfController;
use App\Http\Controllers\MicrosoftAuthController;
use App\Http\Controllers\PrintPdfController;
use App\Http\Controllers\ProformaInvoiceController;
use App\Http\Controllers\GenerateInvoicePdfController;
use App\Http\Controllers\SoftwareHandoverExportController;
use App\Http\Controllers\WhatsAppController;
use App\Http\Controllers\S3FileProxyController;
use App\Http\Controllers\HrCrmAuthController;
use App\Http\Livewire\DemoRequest;
use App\Livewire\Customer\Login;
use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use App\Models\Lead;
use App\Models\CompanyDetail;
use App\Models\UtmDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return redirect(route('filament.admin.home'));
});

// Public Partner Application Form (Reseller / Distributor)
Route::get('/become-partner', \App\Livewire\PartnerApplicationForm::class)->name('partner.apply');

// S3 File Proxy Route
Route::get('/s3-file', [S3FileProxyController::class, 'serve'])->name('s3.serve')->middleware(['auth']);

// HR CRM Auth - Login as User
Route::post('/hr-crm-auth/login-as-user', [HrCrmAuthController::class, 'loginAsUser'])->name('hr.crm-auth.login-as-user')->middleware(['auth']);

// Impersonate - Switch Back
Route::get('/impersonate/leave', function () {
    $impersonatorId = session('impersonator_id');
    if ($impersonatorId) {
        session()->forget('impersonator_id');
        $user = \App\Models\User::find($impersonatorId);
        if ($user) {
            auth()->login($user);
            session()->put('password_hash_' . auth()->getDefaultDriver(), $user->getAuthPassword());
        }
        \Filament\Notifications\Notification::make()
            ->title('Switched back to your account')
            ->success()
            ->send();
    }
    return redirect('/admin');
})->name('impersonate.leave');

Route::get('/api/hr-calendar-data', function (Request $request) {
    $startDate = $request->input('startDate', Carbon::now()->startOfMonth()->format('Y-m-d'));
    $endDate = $request->input('endDate', Carbon::now()->endOfMonth()->format('Y-m-d'));

    try {
        // Get authentication token
        $authResponse = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post('https://hr-api.timeteccloud.com/api/auth-mobile/token', [
            'username' => 'hr@timeteccloud.com',
            'password' => 'Abc123456'
        ]);

        if (!$authResponse->successful()) {
            return response()->json(['error' => 'Authentication failed'], 401);
        }

        $authData = $authResponse->json();
        $token = $authData['accessToken'] ?? null;

        if (!$token) {
            return response()->json(['error' => 'Token not found'], 401);
        }

        // Get calendar data
        $calendarResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->get('https://hr-api.timeteccloud.com/api/v1/mobile-calendar/crm-calendar-list', [
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);

        if (!$calendarResponse->successful()) {
            return response()->json(['error' => 'Calendar API failed', 'status' => $calendarResponse->status()], 500);
        }

        return response()->json($calendarResponse->json());

    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
})->middleware(['auth']);

Route::get('/leaves/all', function (Request $request) {
    $startDate = $request->input('startDate', Carbon::now()->startOfYear()->format('Y-m-d'));
    $endDate = $request->input('endDate', Carbon::now()->endOfYear()->format('Y-m-d'));

    try {
        // Get authentication token
        $authResponse = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post('https://hr-api.timeteccloud.com/api/auth-mobile/token', [
            'username' => 'hr@timeteccloud.com',
            'password' => 'Abc123456'
        ]);

        if (!$authResponse->successful()) {
            return response()->json(['error' => 'Authentication failed'], 401);
        }

        $authData = $authResponse->json();
        $token = $authData['accessToken'] ?? null;

        if (!$token) {
            return response()->json(['error' => 'Token not found'], 401);
        }

        // Get calendar data
        $calendarResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->get('https://hr-api.timeteccloud.com/api/v1/mobile-calendar/crm-calendar-list', [
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);

        if (!$calendarResponse->successful()) {
            return response()->json(['error' => 'Calendar API failed', 'status' => $calendarResponse->status()], 500);
        }

        return response()->json($calendarResponse->json(), 200, [], JSON_PRETTY_PRINT);

    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
})->middleware(['auth']);

Route::get('/public-holidays', function (Request $request) {
    $startDate = $request->input('startDate', Carbon::now()->startOfYear()->format('Y-m-d'));
    $endDate = $request->input('endDate', Carbon::now()->endOfYear()->format('Y-m-d'));

    try {
        // Get authentication token
        $authResponse = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post('https://hr-api.timeteccloud.com/api/auth-mobile/token', [
            'username' => 'hr@timeteccloud.com',
            'password' => 'Abc123456'
        ]);

        if (!$authResponse->successful()) {
            return response()->json(['error' => 'Authentication failed'], 401);
        }

        $authData = $authResponse->json();
        $token = $authData['accessToken'] ?? null;

        if (!$token) {
            return response()->json(['error' => 'Token not found'], 401);
        }

        // Get calendar data (include userIds even though we only need holidays)
        $calendarResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->get('https://hr-api.timeteccloud.com/api/v1/mobile-calendar/crm-calendar-list', [
            'userIds' => '342,348,251',
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);

        if (!$calendarResponse->successful()) {
            return response()->json(['error' => 'Calendar API failed', 'status' => $calendarResponse->status()], 500);
        }

        $calendarData = $calendarResponse->json();
        $calendarList = $calendarData['calendarListView'] ?? [];

        // Extract only holidays
        $holidays = [];
        foreach ($calendarList as $day) {
            if (!empty($day['holidayName'])) {
                $holidays[] = [
                    'date' => $day['date'],
                    'day_of_week' => Carbon::parse($day['date'])->dayOfWeekIso,
                    'holiday_name' => $day['holidayName']
                ];
            }
        }

        return response()->json([
            'total_records' => count($holidays),
            'date_range' => [
                'start' => $startDate,
                'end' => $endDate
            ],
            'data' => $holidays
        ], 200, [], JSON_PRETTY_PRINT);

    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
})->middleware(['auth']);

Route::middleware('signed')
    ->get('invitation/{invitation}/accept', AcceptInvitation::class)
    ->name('invitation.accept');

Route::middleware('signed')
    ->get('quotes/{quote}/pdf', QuotePdfController::class)
    ->name('quotes.pdf');

Route::get('software-handover/{softwareHandover}/pdf', GenerateSoftwareHandoverPdfController::class)
    ->name('software-handover.pdf')
    ->middleware(['auth']);

Route::get('hardware-handover/{hardwareHandover}/pdf', GenerateHardwareHandoverPdfController::class)
    ->name('hardware-handover.pdf')
    ->middleware(['auth']);

Route::get('hardware-handover-v2/{hardwareHandover}/pdf', GenerateHardwareHandoverV2PdfController::class)
    ->name('hardware-handover-v2.pdf')
    ->middleware(['auth']);

Route::get('project-closing-pdf-preview/{handover}', function (Request $request, \App\Models\SoftwareHandover $handover) {
    $picKeys = explode(',', $request->query('pic', 'orig_0'));

    // Resolve each PIC key into contact person data
    $contactPersons = [];
    foreach ($picKeys as $picKey) {
        $picData = ['pic_name_impl' => 'N/A', 'position' => 'N/A', 'pic_email_impl' => 'N/A', 'pic_phone_impl' => 'N/A'];
        if (str_starts_with($picKey, 'orig_')) {
            $index = (int) str_replace('orig_', '', $picKey);
            $pics = is_string($handover->implementation_pics)
                ? json_decode($handover->implementation_pics, true) ?? []
                : $handover->implementation_pics ?? [];
            if (isset($pics[$index])) {
                $picData = $pics[$index];
            }
        } elseif (str_starts_with($picKey, 'new_')) {
            $index = (int) str_replace('new_', '', $picKey);
            $lead = $handover->lead;
            if ($lead && $lead->companyDetail) {
                $additionalPics = is_string($lead->companyDetail->additional_pic)
                    ? json_decode($lead->companyDetail->additional_pic, true) ?? []
                    : $lead->companyDetail->additional_pic ?? [];
                if (isset($additionalPics[$index])) {
                    $pic = $additionalPics[$index];
                    $picData = [
                        'pic_name_impl' => $pic['name'] ?? 'N/A',
                        'position' => $pic['position'] ?? 'N/A',
                        'pic_email_impl' => $pic['email'] ?? 'N/A',
                        'pic_phone_impl' => $pic['hp_number'] ?? 'N/A',
                    ];
                }
            }
        }
        $contactPersons[] = [
            'name' => $picData['pic_name_impl'] ?? 'N/A',
            'position' => $picData['position'] ?? 'N/A',
            'email' => $picData['pic_email_impl'] ?? 'N/A',
            'phone' => $picData['pic_phone_impl'] ?? 'N/A',
        ];
    }

    // Build modules string
    $moduleMap = ['ta' => 'Attendance', 'tl' => 'Leave', 'tc' => 'Claim', 'tp' => 'Payroll', 'tapp' => 'Appraisal', 'thire' => 'Hire', 'tacc' => 'Access', 'tpbi' => 'PowerBI'];
    $activeModules = [];
    foreach ($moduleMap as $field => $name) {
        if ($handover->{$field}) {
            $activeModules[] = strtoupper($name);
        }
    }

    $lead = $handover->lead;
    $companyDetail = $lead?->companyDetail;

    $pdfData = [
        'path_img' => public_path('img/logo-ttc.png'),
        'stampImg' => public_path('storage/ttc-stamp.png'),
        'companyName' => $handover->company_name ?? ($companyDetail?->company_name ?? 'N/A'),
        'contactPersons' => $contactPersons,
        'modules' => implode('/', $activeModules) ?: 'N/A',
        'implementationStartDate' => $handover->created_at ? $handover->created_at->format('d/m/Y') : 'N/A',
        'implementationCompletionDate' => now()->format('d/m/Y'),
        'implementerName' => $handover->implementer ?? 'N/A',
        'teamLeadName' => auth()->user()->name ?? 'N/A',
    ];

    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.system-go-live', $pdfData)->setPaper('a4', 'portrait');
    return $pdf->stream('System_Go_Live_Preview.pdf');
})->name('project-closing.pdf-preview')->middleware(['signed']);

Route::get('/software-handover/export-customer/{lead}/{subsidiaryId?}', [App\Http\Controllers\SoftwareHandoverExportController::class, 'exportCustomerCSV'])
    ->name('software-handover.export-customer')
    ->middleware(['auth']);

// Route::post('/software-handover/confirm-db/{softwareHandover}', [App\Http\Controllers\SoftwareHandoverExportController::class, 'confirmCreateDb'])
//     ->name('software-handover.confirm-db')
//     ->middleware(['auth']);

// Route::post('/software-handover/confirm-pending-license/{softwareHandover}', [App\Http\Controllers\SoftwareHandoverExportController::class, 'confirmCreatePendingLicense'])
//     ->name('software-handover.confirm-pending-license')
//     ->middleware(['auth']);

Route::get('/einvoice/export/{lead}/{subsidiaryId?}', [App\Http\Controllers\EInvoiceExportController::class, 'exportEInvoiceDetails'])
    ->name('einvoice.export')
    ->middleware(['auth']);

Route::get('/invoice-data/export/{softwareHandover}', [App\Http\Controllers\InvoiceDataExportController::class, 'exportInvoiceData'])
    ->name('invoice-data.export')
    ->middleware(['auth']);

Route::get('/reseller-invoice-data/export-renewal/{resellerHandover}', [App\Http\Controllers\ResellerInvoiceDataExportController::class, 'exportRenewalSales'])
    ->name('reseller-invoice-data.export-renewal')
    ->middleware(['auth']);

Route::get('/reseller-invoice-data/export-addon/{resellerHandover}', [App\Http\Controllers\ResellerInvoiceDataExportController::class, 'exportAddOnSales'])
    ->name('reseller-invoice-data.export-addon')
    ->middleware(['auth']);

Route::get('/reseller-invoice-data-fd/export-renewal/{resellerHandoverFd}', [App\Http\Controllers\ResellerInvoiceDataFdExportController::class, 'exportRenewalSales'])
    ->name('reseller-invoice-data-fd.export-renewal')
    ->middleware(['auth']);

Route::get('/reseller-invoice-data-fd/export-addon/{resellerHandoverFd}', [App\Http\Controllers\ResellerInvoiceDataFdExportController::class, 'exportAddOnSales'])
    ->name('reseller-invoice-data-fd.export-addon')
    ->middleware(['auth']);

Route::get('/reseller-invoice-data-fe/export-renewal/{resellerHandoverFe}', [App\Http\Controllers\ResellerInvoiceDataFeExportController::class, 'exportRenewalSales'])
    ->name('reseller-invoice-data-fe.export-renewal')
    ->middleware(['auth']);

Route::get('/reseller-invoice-data-fe/export-addon/{resellerHandoverFe}', [App\Http\Controllers\ResellerInvoiceDataFeExportController::class, 'exportAddOnSales'])
    ->name('reseller-invoice-data-fe.export-addon')
    ->middleware(['auth']);

Route::get('/reseller-purchase-invoice/export/{handoverId}', [App\Http\Controllers\ResellerPurchaseInvoiceExportController::class, 'export'])
    ->name('reseller-purchase-invoice.export')
    ->middleware(['auth']);

Route::get('/finance-purchase-invoice/export/{financeInvoiceId}', [App\Http\Controllers\FinanceInvoicePurchaseExportController::class, 'export'])
    ->name('finance-purchase-invoice.export')
    ->middleware(['auth']);

Route::get('/finance-purchase-invoice/export-batch', [App\Http\Controllers\FinanceInvoicePurchaseExportController::class, 'exportBatch'])
    ->name('finance-purchase-invoice.export-batch')
    ->middleware(['auth']);

Route::get('/fe-purchase-invoice/export/{handoverId}', [App\Http\Controllers\FePurchaseInvoiceExportController::class, 'export'])
    ->name('fe-purchase-invoice.export')
    ->middleware(['auth']);

Route::get('/fe-purchase-invoice/batch-export', [App\Http\Controllers\FePurchaseInvoiceExportController::class, 'batchExport'])
    ->name('fe-purchase-invoice.batch-export')
    ->middleware(['auth']);

Route::get('/rc-purchase-invoice/export/{handoverId}', [App\Http\Controllers\RcPurchaseInvoiceExportController::class, 'export'])
    ->name('rc-purchase-invoice.export')
    ->middleware(['auth']);

Route::get('/rc-purchase-invoice/batch-export', [App\Http\Controllers\RcPurchaseInvoiceExportController::class, 'batchExport'])
    ->name('rc-purchase-invoice.batch-export')
    ->middleware(['auth']);

Route::get('/finance-handover/download-reseller-invoice/{id}', [App\Http\Controllers\FinanceHandoverDownloadController::class, 'downloadResellerInvoice'])
    ->name('finance-handover.download-reseller-invoice')
    ->middleware(['auth']);

Route::get('/headcount-invoice-data/export/{headcountHandover}', [App\Http\Controllers\HeadcountInvoiceDataExportController::class, 'exportInvoiceData'])
    ->name('headcount-invoice-data.export')
    ->middleware(['auth']);

Route::get('/hrdf-invoice-data/export/{hrdfInvoice}', [App\Http\Controllers\HrdfInvoiceDataExportController::class, 'exportHrdfInvoiceData'])
    ->name('hrdf-invoice-data.export')
    ->middleware(['auth']);

Route::get('/demo-request/{lead_code}', function ($lead_code) {
    // Check if the lead_code exists in the database
    $site = LeadSource::where('lead_code', $lead_code)->first();

    if (!$site) {
        // Return a 404 response if the lead_code is not found
        abort(404);
    }

    return view('demoRequest', ['lead_code' => $lead_code]);
});

// Route::get('generate-invoice-pdf/{invoice_no}', GenerateInvoicePdfController::class)
//     ->name('invoices.generate_pdf')
//     ->middleware(['auth']);

Route::get('/referral-demo-request/{lead_code}', function ($lead_code) {
    // Check if the lead_code exists in the database
    $site = LeadSource::where('lead_code', $lead_code)->first();

    if (!$site) {
        // Return a 404 response if the lead_code is not found
        abort(404);
    }

    return view('referralDemoRequest', ['lead_code' => $lead_code]);
});

Route::get('/quotation/{quotation?}', PrintPdfController::class)->name('pdf.print-quotation');
Route::get('/quotation-v2/{quotation}', [GenerateQuotationPdfController::class, '__invoke'])
    ->name('pdf.print-quotation-v2');
Route::get('/proforma-invoice/{quotation?}', ProformaInvoiceController::class)->name('pdf.print-proforma-invoice');
Route::get('/proforma-invoice-v2/{quotation?}', GenerateProformaInvoicePdfController::class)->name('pdf.print-proforma-invoice-v2');
Route::get('/timetec-proforma-invoice/{invoiceNo}/download', \App\Http\Controllers\TimetecProformaInvoiceDownloadController::class)->name('timetec-proforma-invoice.download');
// Route::get('/license-proforma-invoice/{softwareHandover}/{invoiceNo}', \App\Http\Controllers\LicenseProformaInvoiceController::class)->where('softwareHandover', '[0-9]+')->name('pdf.license-proforma-invoice');
// Route::get('/paypal_reseller_invoice', [\App\Http\Controllers\InvoicePaymentController::class, 'show'])->name('invoice-payment');
// Route::post('/payment/paypal/create-order', [\App\Http\Controllers\InvoicePaymentController::class, 'paypalCreateOrder'])->name('payment.paypal.create-order');
// Route::post('/payment/paypal/capture-order', [\App\Http\Controllers\InvoicePaymentController::class, 'paypalCaptureOrder'])->name('payment.paypal.capture-order');
// Route::get('/payment/razer', [\App\Http\Controllers\InvoicePaymentController::class, 'razer'])->name('payment.razer');
// Route::post('/payment/paypal/ipn', [\App\Http\Controllers\InvoicePaymentController::class, 'paypalIpn'])->name('payment.paypal.ipn');
// Route::get('/payment/success', [\App\Http\Controllers\InvoicePaymentController::class, 'success'])->name('payment.success');
// Route::get('/payment/failed', [\App\Http\Controllers\InvoicePaymentController::class, 'failed'])->name('payment.failed');
Route::get('/finance-invoice/{financeInvoice}', [App\Http\Controllers\GenerateFinanceInvoicePdfController::class, '__invoke'])
    ->name('pdf.print-finance-invoice')
    ->middleware(['auth']);

Route::post('/webhook/whatsapp', function (Request $request) {
    $data = $request->all();

    if (empty($data)) {
        $data = json_decode($request->getContent(), true);
    }
    Log::info('Incoming WhatsApp Message Data:', $data);

    $sender = $data['From'] ?? 'whatsapp:unknown';
    $receiver = $data['To'] ?? 'whatsapp:unknown';
    $twilioMessageId = $data['MessageSid'] ?? '';
    $profileName = $data['ProfileName'] ?? 'Unknown';
    $numMedia = $data['NumMedia'] ?? 0;

    // Check if the message contains media (file, image, sticker, audio)
    if ($numMedia > 0 && isset($data['MediaUrl0']) && isset($data['MediaContentType0'])) {
        $mediaUrl = $data['MediaUrl0'];
        $mediaType = $data['MediaContentType0']; // Example: "application/pdf", "image/png", "audio/ogg"

        // Determine the placeholder text based on media type
        if (str_contains($mediaType, 'image')) {
            $message = "[Image]";
        } elseif (str_contains($mediaType, 'audio')) {
            $message = "[Voice Message]";
        } elseif (str_contains($mediaType, 'application') || str_contains($mediaType, 'text')) {
            $message = "[File]";
        } else {
            $message = "[Media Message]";
        }
    } else {
        $message = $data['Body'] ?? 'No message received';
        $mediaUrl = null;
        $mediaType = null;
    }

    // Store the message
    if (!str_contains($sender, 'unknown') && !str_contains($receiver, 'unknown')) {
        ChatMessage::create([
            'sender' => preg_replace('/^\+|^whatsapp:/', '', $sender),
            'receiver' => preg_replace('/^\+|^whatsapp:/', '', $receiver),
            'message' => $message,
            'twilio_message_id' => $twilioMessageId,
            'profile_name' => $profileName,
            'is_from_customer' => true,
            'media_url' => $mediaUrl,
            'media_type' => $mediaType,
            'reply_to_sid' => $request->input('OriginalRepliedMessageSid') ?? null,
        ]);
    } else {
        Log::warning('Skipped saving WhatsApp message due to missing sender or receiver.', [
            'sender' => $sender,
            'receiver' => $receiver
        ]);
    }
});

// Twilio WhatsApp message status callback
Route::post('/webhook/whatsapp/status', function (Request $request) {
    $messageSid = $request->input('MessageSid');
    $messageStatus = $request->input('MessageStatus');

    if ($messageSid && $messageStatus) {
        // Queue the DB update so the webhook responds immediately
        dispatch(function () use ($messageSid, $messageStatus) {
            ChatMessage::where('twilio_message_id', $messageSid)
                ->update(['message_status' => $messageStatus]);
        })->afterResponse();
    }

    return response('OK', 200);
});

//CUSTOMER
Route::prefix('customer')->name('customer.')->group(function () {
    // Public routes
    Route::get('/login', \App\Livewire\Customer\Login::class)->name('login')->middleware('guest:customer');
    Route::post('/login', [CustomerAuthController::class, 'login'])->name('login.submit')->middleware('guest:customer');
    Route::post('/logout', [CustomerAuthController::class, 'logout'])->name('logout');

    // // Password Reset Routes
    // Route::get('/forgot-password', \App\Livewire\Customer\ForgotPassword::class)->name('password.request');
    // Route::get('/reset-password/{token}', \App\Livewire\Customer\ResetPassword::class)->name('password.reset');

    // Account Activation
    Route::get('/activate/{token}', [CustomerActivationController::class, 'activateAccount'])->name('activate');
    Route::post('/activate/{token}', [CustomerActivationController::class, 'completeActivation'])->name('complete-activation');

    // ✅ Fix: Remove the extra '/customer' from the path since we're already in the customer prefix group
    Route::get('/dashboard', function () {
        if (!auth('customer')->check()) {
            return redirect()->route('customer.login');
        }
        return view('customer.dashboard');
    })->name('dashboard');
});

//RESELLER
Route::prefix('reseller')->name('reseller.')->group(function () {
    // Public routes
    Route::get('/login', \App\Livewire\Reseller\Login::class)->name('login')->middleware('guest:reseller');
    Route::post('/login', [App\Http\Controllers\ResellerAuthController::class, 'login'])->name('login.submit')->middleware('guest:reseller');
    Route::post('/logout', [App\Http\Controllers\ResellerAuthController::class, 'logout'])->name('logout');

    // Protected routes
    Route::get('/dashboard', function () {
        if (!auth('reseller')->check()) {
            return redirect()->route('reseller.login');
        }

        $reseller = Auth::guard('reseller')->user();

        return view('reseller.dashboard', [
            'resellerName' => $reseller->name ?? 'Reseller',
            'companyName' => $reseller->company_name ?? 'Company',
        ]);
    })->name('dashboard');

    // API route for handover counts
    Route::get('/handover/counts', [App\Http\Controllers\ResellerHandoverController::class, 'getCounts'])->name('handover.counts');
    Route::get('/inquiry/counts', [App\Http\Controllers\ResellerInquiryController::class, 'getCounts'])->name('inquiry.counts');
    Route::get('/database-creation/counts', [App\Http\Controllers\ResellerDatabaseCreationController::class, 'getCounts'])->name('database-creation.counts');
    Route::get('/database-creation/counts', [App\Http\Controllers\ResellerDatabaseCreationController::class, 'getCounts'])->name('database-creation.counts');
    Route::get('/installation-payment/counts', [App\Http\Controllers\ResellerInstallationPaymentController::class, 'getCounts'])->name('installation-payment.counts');
    Route::get('/fd-handover/counts', [App\Http\Controllers\ResellerHandoverFdController::class, 'getCounts'])->name('fd-handover.counts');
    Route::get('/fe-handover/counts', [App\Http\Controllers\ResellerHandoverFeController::class, 'getCounts'])->name('fe-handover.counts');
    Route::get('/ff-handover/counts', [App\Http\Controllers\ResellerHandoverFfController::class, 'getCounts'])->name('ff-handover.counts');
    Route::get('/fg-handover/counts', [App\Http\Controllers\ResellerHandoverFgController::class, 'getCounts'])->name('fg-handover.counts');

    // Export customer list
    Route::get('/customer/export', [App\Http\Controllers\ResellerCustomerExportController::class, 'export'])->name('customer.export');

    // Export expired licenses
    Route::get('/expired-license/export', [App\Http\Controllers\ResellerExpiredLicenseExportController::class, 'export'])->name('expired-license.export');

    // Email action routes (signed URLs)
    Route::get('/handover/{handover}/proceed', function (App\Models\ResellerHandover $handover) {
        $now = now()->format('d/m/Y h:i A');

        if ($handover->status !== 'pending_quotation_confirmation') {
            $previousAction = $handover->confirmed_proceed_at ? 'Proceed' : ($handover->status === 'inactive' ? 'Cancel Order' : 'Proceed');
            $previousTime = $handover->confirmed_proceed_at
                ? $handover->confirmed_proceed_at->format('d/m/Y h:i A')
                : $handover->updated_at->format('d/m/Y h:i A');

            return view('emails.reseller-handover-status-update', [
                'handover' => $handover,
                'ticketId' => $handover->fb_id,
                'category' => 'Renewal Quotation',
                'status' => $handover->status,
                'statusLabel' => ucwords(str_replace('_', ' ', $handover->status)),
                'invoiceUrl' => null,
                'proceedUrl' => null,
                'cancelUrl' => null,
                'actionResult' => 'already_processed',
                'actionMessage' => "This order has already been processed.\nYou have answered {$previousAction} at {$previousTime}.",
                'actionTime' => $now,
            ]);
        }

        $handover->update([
            'status' => 'pending_timetec_invoice',
            'confirmed_proceed_at' => now(),
        ]);

        // Send email notification
        if (\App\Mail\ResellerHandoverStatusUpdate::shouldSend($handover->status)) {
            try {
                \Illuminate\Support\Facades\Mail::send(new \App\Mail\ResellerHandoverStatusUpdate($handover));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to send reseller handover email', [
                    'handover_id' => $handover->id,
                    'status' => 'pending_timetec_invoice',
                    'error' => $e->getMessage()
                ]);
            }
        }

        return view('emails.reseller-handover-status-update', [
            'handover' => $handover,
            'ticketId' => $handover->fb_id,
            'category' => 'Renewal Quotation',
            'status' => 'pending_quotation_confirmation',
            'statusLabel' => 'Pending Quotation Confirmation',
            'invoiceUrl' => $handover->invoice_url,
            'proceedUrl' => null,
            'cancelUrl' => null,
            'actionResult' => 'proceed',
            'actionTime' => $now,
        ]);
    })->name('handover.proceed')->middleware('signed');

    Route::get('/handover/{handover}/cancel', function (App\Models\ResellerHandover $handover) {
        $now = now()->format('d/m/Y h:i A');

        if ($handover->status !== 'pending_quotation_confirmation') {
            $previousAction = $handover->confirmed_proceed_at ? 'Proceed' : ($handover->status === 'inactive' ? 'Cancel Order' : 'Proceed');
            $previousTime = $handover->confirmed_proceed_at
                ? $handover->confirmed_proceed_at->format('d/m/Y h:i A')
                : $handover->updated_at->format('d/m/Y h:i A');

            return view('emails.reseller-handover-status-update', [
                'handover' => $handover,
                'ticketId' => $handover->fb_id,
                'category' => 'Renewal Quotation',
                'status' => $handover->status,
                'statusLabel' => ucwords(str_replace('_', ' ', $handover->status)),
                'invoiceUrl' => null,
                'proceedUrl' => null,
                'cancelUrl' => null,
                'actionResult' => 'already_processed',
                'actionMessage' => "This order has already been processed.\nYou have answered {$previousAction} at {$previousTime}.",
                'actionTime' => $now,
            ]);
        }

        $handover->update([
            'status' => 'inactive',
        ]);

        return view('emails.reseller-handover-status-update', [
            'handover' => $handover,
            'ticketId' => $handover->fb_id,
            'category' => 'Renewal Quotation',
            'status' => 'pending_quotation_confirmation',
            'statusLabel' => 'Pending Quotation Confirmation',
            'invoiceUrl' => $handover->invoice_url,
            'proceedUrl' => null,
            'cancelUrl' => null,
            'actionResult' => 'cancel',
            'actionTime' => $now,
        ]);
    })->name('handover.cancel')->middleware('signed');

    Route::get('/handover/{handover}/invoice-proceed', function (App\Models\ResellerHandover $handover) {
        $now = now()->format('d/m/Y h:i A');

        if ($handover->status !== 'pending_invoice_confirmation') {
            $previousTime = $handover->updated_at->format('d/m/Y h:i A');

            return view('emails.reseller-handover-status-update', [
                'handover' => $handover,
                'ticketId' => $handover->fb_id,
                'category' => 'Renewal Quotation',
                'status' => $handover->status,
                'statusLabel' => ucwords(str_replace('_', ' ', $handover->status)),
                'invoiceUrl' => $handover->invoice_url,
                'proceedUrl' => null,
                'cancelUrl' => null,
                'autocountInvoiceNumber' => $handover->autocount_invoice_number,
                'autocountInvoiceUrl' => null,
                'selfBilledInvoiceUrl' => null,
                'actionResult' => 'already_processed',
                'actionMessage' => "This order has already been processed.\nYou have answered Proceed at {$previousTime}.",
                'actionTime' => $now,
            ]);
        }

        $handover->update([
            'status' => 'pending_reseller_payment',
        ]);

        // Send email notification
        if (\App\Mail\ResellerHandoverStatusUpdate::shouldSend($handover->status)) {
            try {
                \Illuminate\Support\Facades\Mail::send(new \App\Mail\ResellerHandoverStatusUpdate($handover));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to send reseller handover email', [
                    'handover_id' => $handover->id,
                    'status' => 'pending_reseller_payment',
                    'error' => $e->getMessage()
                ]);
            }
        }

        return view('emails.reseller-handover-status-update', [
            'handover' => $handover,
            'ticketId' => $handover->fb_id,
            'category' => 'Renewal Quotation',
            'status' => 'pending_invoice_confirmation',
            'statusLabel' => 'Pending Invoice Confirmation',
            'invoiceUrl' => $handover->invoice_url,
            'proceedUrl' => null,
            'cancelUrl' => null,
            'autocountInvoiceNumber' => $handover->autocount_invoice_number,
            'autocountInvoiceUrl' => null,
            'selfBilledInvoiceUrl' => null,
            'actionResult' => 'proceed',
            'actionTime' => $now,
        ]);
    })->name('handover.invoice-proceed')->middleware('signed');

    // FD (Bill as Reseller) email action routes
    Route::get('/fd-handover/{handover}/proceed', function (App\Models\ResellerHandoverFd $handover) {
        $now = now()->format('d/m/Y h:i A');

        if ($handover->status !== 'pending_quotation_confirmation') {
            $previousAction = $handover->confirmed_proceed_at ? 'Proceed' : ($handover->status === 'inactive' ? 'Cancel Order' : 'Proceed');
            $previousTime = $handover->confirmed_proceed_at
                ? $handover->confirmed_proceed_at->format('d/m/Y h:i A')
                : $handover->updated_at->format('d/m/Y h:i A');

            return view('emails.reseller-handover-status-update', [
                'handover' => $handover,
                'ticketId' => $handover->fd_id,
                'category' => 'Bill as Reseller',
                'status' => $handover->status,
                'statusLabel' => ucwords(str_replace('_', ' ', $handover->status)),
                'invoiceUrl' => null,
                'proceedUrl' => null,
                'cancelUrl' => null,
                'actionResult' => 'already_processed',
                'actionMessage' => "This order has already been processed.\nYou have answered {$previousAction} at {$previousTime}.",
                'actionTime' => $now,
            ]);
        }

        $handover->update([
            'status' => 'pending_timetec_invoice',
            'confirmed_proceed_at' => now(),
        ]);

        return view('emails.reseller-handover-status-update', [
            'handover' => $handover,
            'ticketId' => $handover->fd_id,
            'category' => 'Bill as Reseller',
            'status' => 'pending_quotation_confirmation',
            'statusLabel' => 'Pending Quotation Confirmation',
            'invoiceUrl' => $handover->invoice_url,
            'proceedUrl' => null,
            'cancelUrl' => null,
            'actionResult' => 'proceed',
            'actionTime' => $now,
        ]);
    })->name('fd-handover.proceed')->middleware('signed');

    Route::get('/fd-handover/{handover}/cancel', function (App\Models\ResellerHandoverFd $handover) {
        $now = now()->format('d/m/Y h:i A');

        if ($handover->status !== 'pending_quotation_confirmation') {
            $previousAction = $handover->confirmed_proceed_at ? 'Proceed' : ($handover->status === 'inactive' ? 'Cancel Order' : 'Proceed');
            $previousTime = $handover->confirmed_proceed_at
                ? $handover->confirmed_proceed_at->format('d/m/Y h:i A')
                : $handover->updated_at->format('d/m/Y h:i A');

            return view('emails.reseller-handover-status-update', [
                'handover' => $handover,
                'ticketId' => $handover->fd_id,
                'category' => 'Bill as Reseller',
                'status' => $handover->status,
                'statusLabel' => ucwords(str_replace('_', ' ', $handover->status)),
                'invoiceUrl' => null,
                'proceedUrl' => null,
                'cancelUrl' => null,
                'actionResult' => 'already_processed',
                'actionMessage' => "This order has already been processed.\nYou have answered {$previousAction} at {$previousTime}.",
                'actionTime' => $now,
            ]);
        }

        $handover->update(['status' => 'inactive']);

        return view('emails.reseller-handover-status-update', [
            'handover' => $handover,
            'ticketId' => $handover->fd_id,
            'category' => 'Bill as Reseller',
            'status' => 'pending_quotation_confirmation',
            'statusLabel' => 'Pending Quotation Confirmation',
            'invoiceUrl' => $handover->invoice_url,
            'proceedUrl' => null,
            'cancelUrl' => null,
            'actionResult' => 'cancel',
            'actionTime' => $now,
        ]);
    })->name('fd-handover.cancel')->middleware('signed');

    Route::get('/fd-handover/{handover}/invoice-proceed', function (App\Models\ResellerHandoverFd $handover) {
        $now = now()->format('d/m/Y h:i A');

        if ($handover->status !== 'pending_invoice_confirmation') {
            $previousTime = $handover->updated_at->format('d/m/Y h:i A');

            return view('emails.reseller-handover-status-update', [
                'handover' => $handover,
                'ticketId' => $handover->fd_id,
                'category' => 'Bill as Reseller',
                'status' => $handover->status,
                'statusLabel' => ucwords(str_replace('_', ' ', $handover->status)),
                'invoiceUrl' => $handover->invoice_url,
                'proceedUrl' => null,
                'cancelUrl' => null,
                'autocountInvoiceNumber' => $handover->autocount_invoice_number,
                'autocountInvoiceUrl' => null,
                'actionResult' => 'already_processed',
                'actionMessage' => "This order has already been processed.\nYou have answered Proceed at {$previousTime}.",
                'actionTime' => $now,
            ]);
        }

        $handover->update([
            'status' => 'pending_reseller_payment',
        ]);

        if (\App\Mail\ResellerHandoverFdStatusUpdate::shouldSend($handover->status)) {
            try {
                \Illuminate\Support\Facades\Mail::send(new \App\Mail\ResellerHandoverFdStatusUpdate($handover));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to send FD handover email', [
                    'handover_id' => $handover->id,
                    'status' => 'pending_reseller_payment',
                    'error' => $e->getMessage()
                ]);
            }
        }

        return view('emails.reseller-handover-status-update', [
            'handover' => $handover,
            'ticketId' => $handover->fd_id,
            'category' => 'Bill as Reseller',
            'status' => 'pending_invoice_confirmation',
            'statusLabel' => 'Pending Invoice Confirmation',
            'invoiceUrl' => $handover->invoice_url,
            'proceedUrl' => null,
            'cancelUrl' => null,
            'autocountInvoiceNumber' => $handover->autocount_invoice_number,
            'autocountInvoiceUrl' => null,
            'actionResult' => 'proceed',
            'actionTime' => $now,
        ]);
    })->name('fd-handover.invoice-proceed')->middleware('signed');

    // FE (Bill as End User) email action routes
    Route::get('/fe-handover/{handover}/proceed', function (App\Models\ResellerHandoverFe $handover) {
        $now = now()->format('d/m/Y h:i A');

        if ($handover->status !== 'pending_quotation_confirmation') {
            $previousAction = $handover->confirmed_proceed_at ? 'Proceed' : ($handover->status === 'inactive' ? 'Cancel Order' : 'Proceed');
            $previousTime = $handover->confirmed_proceed_at
                ? $handover->confirmed_proceed_at->format('d/m/Y h:i A')
                : $handover->updated_at->format('d/m/Y h:i A');

            return view('emails.reseller-handover-status-update', [
                'handover' => $handover,
                'ticketId' => $handover->fe_id,
                'category' => 'Bill as End User',
                'status' => $handover->status,
                'statusLabel' => ucwords(str_replace('_', ' ', $handover->status)),
                'invoiceUrl' => null,
                'proceedUrl' => null,
                'cancelUrl' => null,
                'actionResult' => 'already_processed',
                'actionMessage' => "This order has already been processed.\nYou have answered {$previousAction} at {$previousTime}.",
                'actionTime' => $now,
            ]);
        }

        $handover->update([
            'status' => 'pending_timetec_invoice',
            'confirmed_proceed_at' => now(),
        ]);

        return view('emails.reseller-handover-status-update', [
            'handover' => $handover,
            'ticketId' => $handover->fe_id,
            'category' => 'Bill as End User',
            'status' => 'pending_quotation_confirmation',
            'statusLabel' => 'Pending Quotation Confirmation',
            'invoiceUrl' => $handover->invoice_url,
            'proceedUrl' => null,
            'cancelUrl' => null,
            'actionResult' => 'proceed',
            'actionTime' => $now,
        ]);
    })->name('fe-handover.proceed')->middleware('signed');

    Route::get('/fe-handover/{handover}/cancel', function (App\Models\ResellerHandoverFe $handover) {
        $now = now()->format('d/m/Y h:i A');

        if ($handover->status !== 'pending_quotation_confirmation') {
            $previousAction = $handover->confirmed_proceed_at ? 'Proceed' : ($handover->status === 'inactive' ? 'Cancel Order' : 'Proceed');
            $previousTime = $handover->confirmed_proceed_at
                ? $handover->confirmed_proceed_at->format('d/m/Y h:i A')
                : $handover->updated_at->format('d/m/Y h:i A');

            return view('emails.reseller-handover-status-update', [
                'handover' => $handover,
                'ticketId' => $handover->fe_id,
                'category' => 'Bill as End User',
                'status' => $handover->status,
                'statusLabel' => ucwords(str_replace('_', ' ', $handover->status)),
                'invoiceUrl' => null,
                'proceedUrl' => null,
                'cancelUrl' => null,
                'actionResult' => 'already_processed',
                'actionMessage' => "This order has already been processed.\nYou have answered {$previousAction} at {$previousTime}.",
                'actionTime' => $now,
            ]);
        }

        $handover->update(['status' => 'inactive']);

        return view('emails.reseller-handover-status-update', [
            'handover' => $handover,
            'ticketId' => $handover->fe_id,
            'category' => 'Bill as End User',
            'status' => 'pending_quotation_confirmation',
            'statusLabel' => 'Pending Quotation Confirmation',
            'invoiceUrl' => $handover->invoice_url,
            'proceedUrl' => null,
            'cancelUrl' => null,
            'actionResult' => 'cancel',
            'actionTime' => $now,
        ]);
    })->name('fe-handover.cancel')->middleware('signed');

    Route::get('/fe-handover/{handover}/invoice-proceed', function (App\Models\ResellerHandoverFe $handover) {
        $now = now()->format('d/m/Y h:i A');

        if ($handover->status !== 'pending_invoice_confirmation') {
            $previousTime = $handover->updated_at->format('d/m/Y h:i A');

            return view('emails.reseller-handover-status-update', [
                'handover' => $handover,
                'ticketId' => $handover->fe_id,
                'category' => 'Bill as End User',
                'status' => $handover->status,
                'statusLabel' => ucwords(str_replace('_', ' ', $handover->status)),
                'invoiceUrl' => $handover->invoice_url,
                'proceedUrl' => null,
                'cancelUrl' => null,
                'autocountInvoiceNumber' => $handover->autocount_invoice_number,
                'autocountInvoiceUrl' => null,
                'actionResult' => 'already_processed',
                'actionMessage' => "This order has already been processed.\nYou have answered Proceed at {$previousTime}.",
                'actionTime' => $now,
            ]);
        }

        $handover->update([
            'status' => 'pending_reseller_payment',
        ]);

        if (\App\Mail\ResellerHandoverFeStatusUpdate::shouldSend($handover->status)) {
            try {
                \Illuminate\Support\Facades\Mail::send(new \App\Mail\ResellerHandoverFeStatusUpdate($handover));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to send FE handover email', [
                    'handover_id' => $handover->id,
                    'status' => 'pending_reseller_payment',
                    'error' => $e->getMessage()
                ]);
            }
        }

        return view('emails.reseller-handover-status-update', [
            'handover' => $handover,
            'ticketId' => $handover->fe_id,
            'category' => 'Bill as End User',
            'status' => 'pending_invoice_confirmation',
            'statusLabel' => 'Pending Invoice Confirmation',
            'invoiceUrl' => $handover->invoice_url,
            'proceedUrl' => null,
            'cancelUrl' => null,
            'autocountInvoiceNumber' => $handover->autocount_invoice_number,
            'autocountInvoiceUrl' => null,
            'actionResult' => 'proceed',
            'actionTime' => $now,
        ]);
    })->name('fe-handover.invoice-proceed')->middleware('signed');

    // FF Handover email action routes
    Route::get('/ff-handover/{handover}/make-payment', function (App\Models\ResellerHandoverFf $handover) {
        $now = now()->format('d/m/Y h:i A');

        if ($handover->status !== 'pending_quotation_confirmation') {
            $previousAction = $handover->payment_clicked_at ? 'Make Payment' : ($handover->status === 'inactive' ? 'Cancel Order' : 'Make Payment');
            $previousTime = $handover->payment_clicked_at
                ? $handover->payment_clicked_at->format('d/m/Y h:i A')
                : $handover->updated_at->format('d/m/Y h:i A');

            return view('emails.reseller-handover-status-update', [
                'handover' => $handover,
                'ticketId' => $handover->ff_id,
                'category' => match ($handover->category) {
                    'renewal_subscription' => 'Renewal Subscription',
                    'addon_headcount' => 'AddOn Headcount',
                    default => 'Bill as End User',
                },
                'status' => $handover->status,
                'statusLabel' => ucwords(str_replace('_', ' ', $handover->status)),
                'invoiceUrl' => null,
                'proceedUrl' => null,
                'cancelUrl' => null,
                'actionResult' => 'already_processed',
                'actionMessage' => "This order has already been processed.\nYou have answered {$previousAction} at {$previousTime}.",
                'actionTime' => $now,
            ]);
        }

        $handover->update(['payment_clicked_at' => now()]);

        $invoiceUrl = $handover->invoice_url;

        if ($invoiceUrl) {
            return redirect($invoiceUrl);
        }

        return view('emails.reseller-handover-status-update', [
            'handover' => $handover,
            'ticketId' => $handover->ff_id,
            'category' => match ($handover->category) {
                'renewal_subscription' => 'Renewal Subscription',
                'addon_headcount' => 'AddOn Headcount',
                default => 'Bill as End User',
            },
            'status' => 'pending_quotation_confirmation',
            'statusLabel' => 'Pending Quotation Confirmation',
            'invoiceUrl' => $invoiceUrl,
            'proceedUrl' => null,
            'cancelUrl' => null,
            'actionResult' => 'proceed',
            'actionTime' => $now,
        ]);
    })->name('ff-handover.make-payment')->middleware('signed');

    Route::get('/ff-handover/{handover}/cancel', function (App\Models\ResellerHandoverFf $handover) {
        $now = now()->format('d/m/Y h:i A');

        if ($handover->status !== 'pending_quotation_confirmation') {
            $previousAction = $handover->payment_clicked_at ? 'Make Payment' : ($handover->status === 'inactive' ? 'Cancel Order' : 'Make Payment');
            $previousTime = $handover->payment_clicked_at
                ? $handover->payment_clicked_at->format('d/m/Y h:i A')
                : $handover->updated_at->format('d/m/Y h:i A');

            return view('emails.reseller-handover-status-update', [
                'handover' => $handover,
                'ticketId' => $handover->ff_id,
                'category' => match ($handover->category) {
                    'renewal_subscription' => 'Renewal Subscription',
                    'addon_headcount' => 'AddOn Headcount',
                    default => 'Bill as End User',
                },
                'status' => $handover->status,
                'statusLabel' => ucwords(str_replace('_', ' ', $handover->status)),
                'invoiceUrl' => null,
                'proceedUrl' => null,
                'cancelUrl' => null,
                'actionResult' => 'already_processed',
                'actionMessage' => "This order has already been processed.\nYou have answered {$previousAction} at {$previousTime}.",
                'actionTime' => $now,
            ]);
        }

        $handover->update(['status' => 'inactive']);

        return view('emails.reseller-handover-status-update', [
            'handover' => $handover,
            'ticketId' => $handover->ff_id,
            'category' => match ($handover->category) {
                'renewal_subscription' => 'Renewal Subscription',
                'addon_headcount' => 'AddOn Headcount',
                default => 'Bill as End User',
            },
            'status' => 'pending_quotation_confirmation',
            'statusLabel' => 'Pending Quotation Confirmation',
            'invoiceUrl' => $handover->invoice_url,
            'proceedUrl' => null,
            'cancelUrl' => null,
            'actionResult' => 'cancel',
            'actionTime' => $now,
        ]);
    })->name('ff-handover.cancel')->middleware('signed');

    // FG Handover email action routes
    Route::get('/fg-handover/{handover}/proceed', function (App\Models\ResellerHandoverFg $handover) {
        $now = now()->format('d/m/Y h:i A');

        if ($handover->status !== 'pending_quotation_confirmation') {
            $previousAction = $handover->confirmed_proceed_at ? 'Proceed' : ($handover->status === 'inactive' ? 'Cancel Order' : 'Proceed');
            $previousTime = $handover->confirmed_proceed_at
                ? $handover->confirmed_proceed_at->format('d/m/Y h:i A')
                : $handover->updated_at->format('d/m/Y h:i A');

            return view('emails.reseller-handover-fg-status-update', [
                'handover' => $handover,
                'ticketId' => $handover->fg_id,
                'category' => 'Renewal Quotation',
                'status' => $handover->status,
                'statusLabel' => ucwords(str_replace('_', ' ', $handover->status)),
                'invoiceUrl' => null,
                'proceedUrl' => null,
                'cancelUrl' => null,
                'actionResult' => 'already_processed',
                'actionMessage' => "This order has already been processed.\nYou have answered {$previousAction} at {$previousTime}.",
                'actionTime' => $now,
            ]);
        }

        $handover->update([
            'status' => 'pending_timetec_invoice',
            'confirmed_proceed_at' => now(),
        ]);

        // Send email notification
        if (\App\Mail\ResellerHandoverFgStatusUpdate::shouldSend($handover->status)) {
            try {
                \Illuminate\Support\Facades\Mail::send(new \App\Mail\ResellerHandoverFgStatusUpdate($handover));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to send FG reseller handover email', [
                    'handover_id' => $handover->id,
                    'status' => 'pending_timetec_invoice',
                    'error' => $e->getMessage()
                ]);
            }
        }

        return view('emails.reseller-handover-fg-status-update', [
            'handover' => $handover,
            'ticketId' => $handover->fg_id,
            'category' => 'Renewal Quotation',
            'status' => 'pending_quotation_confirmation',
            'statusLabel' => 'Pending Quotation Confirmation',
            'invoiceUrl' => $handover->invoice_url,
            'proceedUrl' => null,
            'cancelUrl' => null,
            'actionResult' => 'proceed',
            'actionTime' => $now,
        ]);
    })->name('fg-handover.proceed')->middleware('signed');

    Route::get('/fg-handover/{handover}/cancel', function (App\Models\ResellerHandoverFg $handover) {
        $now = now()->format('d/m/Y h:i A');

        if ($handover->status !== 'pending_quotation_confirmation') {
            $previousAction = $handover->confirmed_proceed_at ? 'Proceed' : ($handover->status === 'inactive' ? 'Cancel Order' : 'Proceed');
            $previousTime = $handover->confirmed_proceed_at
                ? $handover->confirmed_proceed_at->format('d/m/Y h:i A')
                : $handover->updated_at->format('d/m/Y h:i A');

            return view('emails.reseller-handover-fg-status-update', [
                'handover' => $handover,
                'ticketId' => $handover->fg_id,
                'category' => 'Renewal Quotation',
                'status' => $handover->status,
                'statusLabel' => ucwords(str_replace('_', ' ', $handover->status)),
                'invoiceUrl' => null,
                'proceedUrl' => null,
                'cancelUrl' => null,
                'actionResult' => 'already_processed',
                'actionMessage' => "This order has already been processed.\nYou have answered {$previousAction} at {$previousTime}.",
                'actionTime' => $now,
            ]);
        }

        $handover->update([
            'status' => 'inactive',
        ]);

        return view('emails.reseller-handover-fg-status-update', [
            'handover' => $handover,
            'ticketId' => $handover->fg_id,
            'category' => 'Renewal Quotation',
            'status' => 'pending_quotation_confirmation',
            'statusLabel' => 'Pending Quotation Confirmation',
            'invoiceUrl' => $handover->invoice_url,
            'proceedUrl' => null,
            'cancelUrl' => null,
            'actionResult' => 'cancel',
            'actionTime' => $now,
        ]);
    })->name('fg-handover.cancel')->middleware('signed');

    // Reseller Commission Handover — email-based Proceed.
    Route::get('/commission-handover/{handover}/proceed', function (App\Models\ResellerCommissionHandover $handover) {
        $now = now()->format('d/m/Y h:i A');

        if ($handover->status !== 'pending_reseller') {
            $previousTime = $handover->reseller_proceeded_at
                ? $handover->reseller_proceeded_at->format('d/m/Y h:i A')
                : $handover->updated_at->format('d/m/Y h:i A');

            return view('emails.reseller-commission-action-result', [
                'handover' => $handover,
                'actionResult' => 'already_processed',
                'actionMessage' => "This handover has already been processed.\nCurrent status: " . ucwords(str_replace('_', ' ', $handover->status)) . " (at {$previousTime}).",
                'actionTime' => $now,
            ]);
        }

        $handover->update([
            'status' => 'pending_finance',
            'reseller_proceeded_at' => now(),
        ]);

        try {
            if (\App\Mail\ResellerCommissionStatusUpdate::shouldSend($handover->status)) {
                \Illuminate\Support\Facades\Mail::send(new \App\Mail\ResellerCommissionStatusUpdate($handover));
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send commission status email after email-proceed', [
                'handover_id' => $handover->id,
                'error' => $e->getMessage(),
            ]);
        }

        return view('emails.reseller-commission-action-result', [
            'handover' => $handover,
            'actionResult' => 'proceed',
            'actionMessage' => null,
            'actionTime' => $now,
        ]);
    })->name('commission-handover.proceed')->middleware('signed');
});

// Admin routes for sending activation emails
Route::middleware(['auth'])->group(function () {
    Route::post('/admin/leads/{lead}/send-activation', [CustomerActivationController::class, 'sendActivationEmail'])
         ->name('admin.leads.send-activation');

    // Admin login as reseller (does not update last_login_at)
    Route::get('/admin/reseller-login/{reseller}', function (App\Models\ResellerV2 $reseller) {
        Auth::guard('reseller')->login($reseller);
        return redirect()->route('reseller.dashboard');
    })->name('admin.reseller.login');

    // Admin route for reseller handover counts
    Route::get('/admin/reseller-handover/counts', [App\Http\Controllers\ResellerHandoverController::class, 'getAdminCounts'])
         ->name('admin.reseller-handover.counts');

    // Ticket deep link — redirect /admin/ticket-list/{ticketId} to the page with query param
    Route::get('/admin/ticket-list/{ticketId}', function ($ticketId) {
        return redirect()->to('/admin/ticket-list?ticket=' . urlencode($ticketId));
    })->where('ticketId', 'TC-.*')->name('ticket.deeplink');
});

Route::get('/hrms/implementer/{filename}', function ($filename) {
    $path = storage_path('app/public/hrms/implementer/' . $filename);

    if (!file_exists($path)) {
        abort(404);
    }

    return response()->file($path);
})->name('implementer.files');

Route::get('/project-plans/{filename}', function ($filename) {
    $path = storage_path('app/public/project-plans/' . $filename);

    if (!file_exists($path)) {
        abort(404);
    }

    // ✅ Force browser to display instead of download
    return response()->file($path, [
        'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'Content-Disposition' => 'inline; filename="' . $filename . '"',
    ]);
})->name('project-plans.view');

Route::get('/download-project-plan/{file}', function ($file) {
    $filePath = storage_path('app/public/project-plans/' . $file);

    if (!file_exists($filePath)) {
        abort(404, 'File not found');
    }

    return response()->download($filePath);
})->name('download.project-plan');

Route::get('/hrms/trainer/{filename}', function ($filename) {
    // First try with the exact filename
    $path = storage_path('app/public/hrms/trainer/' . $filename);

    // If file doesn't exist and no extension provided, try adding .mp4
    if (!file_exists($path) && !pathinfo($filename, PATHINFO_EXTENSION)) {
        $path = storage_path('app/public/hrms/trainer/' . $filename . '.mp4');
    }

    if (!file_exists($path)) {
        abort(404);
    }

    return response()->file($path);
})->name('trainer.files');

Route::get('/file/{filepath}', function ($filepath) {
    // The filepath parameter will capture everything after /file/
    $path = storage_path('app/public/' . $filepath);

    if (!file_exists($path)) {
        abort(404);
    }

    return response()->file($path);
})->where('filepath', '.*')->name('file.serve');

// Trainer files route: /trainer/{type_version}/{filename}
Route::get('/trainer/{type_version}/{filename}', function ($type_version, $filename) {
    $path = storage_path('app/public/trainer/' . $type_version . '/' . $filename);

    if (!file_exists($path)) {
        abort(404);
    }

    return response()->file($path);
})->name('trainer.file.serve');

Route::prefix('hrcrm/api/tickets')->name('api.tickets.')->group(function () {
    // Get all tickets
    Route::get('/', [App\Http\Controllers\TicketApiController::class, 'index'])->name('index');

    // Get single ticket
    Route::get('hrcrm//{ticket}', [App\Http\Controllers\TicketApiController::class, 'show'])->name('show');

    // Create ticket (called from Filament Resource)
    Route::post('/', [App\Http\Controllers\TicketApiController::class, 'store'])->name('store');

    // Update ticket
    Route::put('hrcrm//{ticket}', [App\Http\Controllers\TicketApiController::class, 'update'])->name('update');

    // Delete ticket
    Route::delete('hrcrm//{ticket}', [App\Http\Controllers\TicketApiController::class, 'destroy'])->name('destroy');
});

Route::get('/zoho/auth', function (Request $request) {
    $clientId = env('ZOHO_CLIENT_ID');
    $clientSecret = env('ZOHO_CLIENT_SECRET');
    $redirectUri = env('ZOHO_REDIRECT_URI');

    // ✅ Check if a valid access token exists
    if (Cache::has('zoho_access_token')) {
        return response()->json([
            'message' => 'Using cached Zoho access token',
            'access_token' => Cache::get('zoho_access_token')
        ]);
    }

    // ✅ If no access token, check if a refresh token exists to refresh it
    if (Cache::has('zoho_refresh_token')) {
        $refreshToken = Cache::get('zoho_refresh_token');
        $tokenResponse = Http::asForm()->post('https://accounts.zoho.com/oauth/v2/token', [
            'refresh_token' => $refreshToken,
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
            'grant_type'    => 'refresh_token',
        ]);

        $tokenData = $tokenResponse->json();
        Log::info('Zoho Token Refresh Response:', $tokenData);

        if (isset($tokenData['access_token'])) {
            Cache::put('zoho_access_token', $tokenData['access_token'], now()->addMinutes(55));
            return response()->json([
                'message' => 'Zoho access token refreshed',
                'access_token' => $tokenData['access_token']
            ]);
        }
    }

    // ✅ If no refresh token, redirect user to Zoho authentication
    $authUrl = "https://accounts.zoho.com/oauth/v2/auth?" . http_build_query([
        'client_id'     => $clientId,
        'response_type' => 'code',
        'scope'         => 'ZohoCRM.modules.all',
        'redirect_uri'  => $redirectUri,
        'access_type'   => 'offline',
        'prompt'        => 'consent',
    ]);

    return redirect()->away($authUrl);
});

Route::get('/zoho/callback', function (Request $request) {
    Log::info('Incoming Zoho Callback Data:', $request->all());

    $code = $request->query('code');
    if (!$code) {
        return response()->json(['error' => 'No authorization code received'], 400);
    }

    $clientId = env('ZOHO_CLIENT_ID');
    $clientSecret = env('ZOHO_CLIENT_SECRET');
    $redirectUri = env('ZOHO_REDIRECT_URI');

    // Exchange Code for Access Token
    $tokenResponse = Http::asForm()->post('https://accounts.zoho.com/oauth/v2/token', [
        'code'          => $code,
        'client_id'     => $clientId,
        'client_secret' => $clientSecret,
        'redirect_uri'  => $redirectUri,
        'grant_type'    => 'authorization_code',
    ]);

    $tokenData = $tokenResponse->json();
    Log::info('Zoho Token Response:', $tokenData);

    if (!isset($tokenData['access_token'])) {
        return response()->json(['error' => 'Failed to get access token', 'details' => $tokenData], 400);
    }

    // ✅ Store access token & refresh token
    Cache::put('zoho_access_token', $tokenData['access_token'], now()->addMinutes(55));
    if (isset($tokenData['refresh_token'])) {
        Cache::forever('zoho_refresh_token', $tokenData['refresh_token']);
    }

    return response()->json([
        'message' => 'Zoho authentication successful',
        'access_token' => $tokenData['access_token'],
        'refresh_token' => $tokenData['refresh_token'] ?? 'Already stored',
    ]);
});

Route::get('/zoho/leads', function (Request $request) {
    $accessToken = Cache::get('zoho_access_token');
    $apiDomain = 'https://www.zohoapis.com';

    if (!$accessToken) {
        return response()->json(['error' => 'No access token available. Please authenticate first.'], 400);
    }

    // ✅ Get the sorting parameter from the request (default to 'id')
    $sortBy = $request->query('sort_by', 'id'); // Possible values: id, Created_Time, Modified_Time

    // Fetch Leads from Zoho with sorting
    $response = Http::withHeaders([
        'Authorization' => 'Zoho-oauthtoken ' . $accessToken,
        'Content-Type'  => 'application/json',
    ])->get($apiDomain . '/crm/v2/Leads', [
        'page'     => 1,
        'per_page' => 200, // ✅ Max limit is 200, not 300
        'criteria' => '(Created_Time:after:2025-03-01)'
    ]);

    return($leadsData = $response->json());
});

// Route::get('/zoho/deals', function () {
//     $accessToken = Cache::get('zoho_access_token');
//     $apiDomain = 'https://www.zohoapis.com';

//     if (!$accessToken) {
//         return response()->json(['error' => 'No access token available. Please authenticate first.'], 400);
//     }

//     $allDeals = [];
//     $perPage = 200;
//     $page = 1;
//     $pageToken = null;

//     while (true) {
//         // ✅ API query parameters
//         $queryParams = [
//             'per_page' => $perPage,
//             'criteria' => '(Created_Time:after:2025-03-01)',
//         ];

//         if ($pageToken) {
//             $queryParams['page_token'] = $pageToken; // ✅ Use page_token for large data
//         } else {
//             $queryParams['page'] = $page; // ✅ Use normal page-based pagination first
//         }

//         // ✅ Fetch Deals from Zoho
//         $response = Http::withHeaders([
//             'Authorization' => 'Zoho-oauthtoken ' . $accessToken,
//             'Content-Type'  => 'application/json',
//         ])->get($apiDomain . '/crm/v2/Deals', $queryParams);

//         $dealsData = $response->json();

//         if (!isset($dealsData['data']) || empty($dealsData['data'])) {
//             break; // ✅ Stop if no more deals
//         }

//         // ✅ Merge deals into $allDeals
//         $allDeals = array_merge($allDeals, $dealsData['data']);

//         // ✅ Check if next_page_token exists
//         if (isset($dealsData['info']['next_page_token'])) {
//             $pageToken = $dealsData['info']['next_page_token'];
//         } else {
//             break; // ✅ Stop if no next page
//         }

//         $page++;
//     }

//     return response()->json([
//         'message' => 'All deals retrieved successfully',
//         'total_deals' => count($allDeals),
//         'deals' => $allDeals
//     ]);
// });

// Route::get('/demo-request', DemoRequest::class)->name('demo-request');

// Route::get('/auth/microsoft', function () {
//     return Socialite::driver('microsoft')->redirect();
// });

// Route::get('/auth/microsoft/callback', function () {
//     $user = Socialite::driver('microsoft')->user();
//     // Store $user->token in the database for API requests
// });

// Route::get('auth/microsoft', [MicrosoftAuthController::class, 'redirectToMicrosoft'])->name('microsoft.auth');
// Route::get('auth/microsoft/callback', [MicrosoftAuthController::class, 'handleMicrosoftCallback']);

