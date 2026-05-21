<?php

namespace App\Livewire\HrAdminDashboard;

use App\Models\HrSalesInvoice;
use App\Models\SoftwareHandover;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ViewSalesInvoice extends Component
{
    public ?int $invoiceId = null;
    public ?string $invoiceNo = null;
    public ?int $softwareHandoverId = null;
    public ?int $hrAccountId = null;
    public ?int $hrCompanyId = null;
    public ?string $from = null;

    // Invoice data
    public array $invoice = [];
    public array $items = [];
    public array $itemGroups = [];
    public bool $isLoading = true;
    public bool $hasError = false;
    public string $errorMessage = '';

    // Payment modal
    public bool $showPaymentModal = false;
    public array $paymentForm = [
        'company' => '',
        'currency' => 'MYR',
        'amount' => 0,
        'bill_title' => '',
        'payment_method' => 'Bank Transfer',
        'ref_no' => '',
        'remark' => '',
    ];

    // Cancel invoice modal
    public bool $showCancelModal = false;
    public array $cancelForm = [
        'doc_no' => '',
        'status' => 'cancelled',
        'remark' => '',
    ];
    public array $cancelRemarks = [];

    public function mount(
        ?int $invoiceId = null,
        ?string $invoiceNo = null,
        ?int $softwareHandoverId = null,
        ?int $hrAccountId = null,
        ?int $hrCompanyId = null,
        ?string $from = null,
    ): void {
        $this->invoiceId = $invoiceId;
        $this->invoiceNo = $invoiceNo;
        $this->softwareHandoverId = $softwareHandoverId;
        $this->hrAccountId = $hrAccountId;
        $this->hrCompanyId = $hrCompanyId;
        $this->from = $from;

        if (($this->hrAccountId === null || $this->hrCompanyId === null) && $this->softwareHandoverId) {
            $softwareHandover = SoftwareHandover::select(['id', 'hr_account_id', 'hr_company_id'])
                ->find($this->softwareHandoverId);
            $this->hrAccountId = $this->hrAccountId ?? $softwareHandover?->hr_account_id;
            $this->hrCompanyId = $this->hrCompanyId ?? $softwareHandover?->hr_company_id;
        }

        // Resolve invoiceId from invoiceNo if needed
        if (!$this->invoiceId && $this->invoiceNo) {
            // Try exact match first
            $query = HrSalesInvoice::where('invoice_no', $this->invoiceNo);
            if ($this->softwareHandoverId) {
                $query->where('software_handover_id', $this->softwareHandoverId);
            }
            $this->invoiceId = $query->value('id');

            // If not found and has softwareHandoverId, get the latest invoice for this handover
            if (!$this->invoiceId && $this->softwareHandoverId) {
                $this->invoiceId = HrSalesInvoice::where('software_handover_id', $this->softwareHandoverId)
                    ->latest()
                    ->value('id');
            }
        }

        if ($this->invoiceId) {
            $this->loadInvoice();
        } else {
            $this->hasError = true;
            $this->errorMessage = 'No invoice specified.';
            $this->isLoading = false;
        }
    }

    public function loadInvoice(): void
    {
        $this->isLoading = true;
        $this->hasError = false;

        try {
            $record = HrSalesInvoice::with(['items.product', 'softwareHandover.lead.companyDetail'])
                ->find($this->invoiceId);

            if (!$record) {
                $this->hasError = true;
                $this->errorMessage = 'Invoice not found.';
                $this->isLoading = false;
                return;
            }

            // Get company info
            $sw = $record->softwareHandover;
            $companyDetail = $sw?->lead?->companyDetail;
            $companyName = $record->company_name ?? $sw?->company_name ?? 'Unknown Company';
            $picName = $sw?->pic_name ?? $companyDetail?->contact_person ?? '';
            $email = $companyDetail?->email ?? '';
            $phone = $companyDetail?->mobile_phone ?? $companyDetail?->phone ?? '';

            // Build items
            $this->items = [];
            $subtotal = 0;
            $totalDiscount = 0;

            // Find base license start date (earliest among items) so per-year periods can be derived
            $baseStart = $record->items
                ->pluck('license_start_date')
                ->filter()
                ->sort()
                ->first();

            foreach ($record->items as $item) {
                $period = null;
                if ($item->license_start_date && $item->license_end_date) {
                    $period = $item->license_start_date->format('d/m/Y') . ' - ' . $item->license_end_date->format('d/m/Y');
                }

                $displayDesc = $item->license_type ?? 'TimeTec License';

                $itemData = [
                    'description' => $displayDesc,
                    'quantity' => $item->quantity ?? 0,
                    'unit_price' => (float) ($item->unit_price ?? 0),
                    'subscription_period' => $item->subscription_period ?? 1,
                    'discount' => (float) ($item->discount ?? 0),
                    'total_before_tax' => (float) ($item->total_before_tax ?? 0),
                    'taxation' => (float) ($item->taxation ?? 0),
                    'total_after_tax' => (float) ($item->total_after_tax ?? 0),
                    'period' => $period,
                    'year' => $item->year,
                ];

                $this->items[] = $itemData;
                $subtotal += $itemData['total_before_tax'];
                $totalDiscount += ($itemData['discount'] / 100) * $itemData['total_before_tax'];
            }

            // Build year-segregated groups with per-year license period
            $this->itemGroups = $this->buildItemGroups($this->items, $baseStart);

            // Calculate totals
            $taxRate = (float) ($record->tax_rate ?? 0);
            $taxableAmount = $subtotal - $totalDiscount;
            $taxAmount = $taxableAmount * ($taxRate / 100);
            $grandTotal = $taxableAmount + $taxAmount;

            // Check if reseller — reseller_id references `resellers` table,
            // full details are in `reseller_v2` linked via reseller_v2.reseller_id
            $reseller = null;
            $subscriber = null;
            if ($sw && $sw->reseller_id) {
                $resellerBasic = DB::table('resellers')->find($sw->reseller_id);
                $resellerV2 = DB::table('reseller_v2')
                    ->where('reseller_id', $resellerBasic?->id)
                    ->first();

                if ($resellerV2 || $resellerBasic) {
                    $reseller = [
                        'company_name' => $resellerV2?->company_name ?? $resellerBasic?->company_name ?? '-',
                        'pic_name' => $resellerV2?->name ?? $resellerV2?->contact_person ?? '',
                        'email' => $resellerV2?->email ?? '',
                        'phone' => $resellerV2?->phone ?? '',
                        'address' => trim(implode(', ', array_filter([
                            $resellerV2?->address ?? '',
                            $resellerV2?->city ?? '',
                            $resellerV2?->state ?? '',
                            $resellerV2?->country ?? '',
                        ]))),
                    ];
                    // Subscriber is the actual end-user company
                    $subscriber = [
                        'company_name' => $companyName,
                        'email' => $email,
                    ];
                }
            }

            // Generate encrypted invoice param for payment links
            // Local CRM encrypted param (for /paypal_reseller_invoice page)
            $encryptedInvoiceParam = null;
            $encrypted = openssl_encrypt((string) $record->id, 'AES-128-ECB', 'Epicamera@99');
            if ($encrypted !== false) {
                $encryptedInvoiceParam = base64_encode($encrypted);
            }

            // TimetecCloud encrypted param (for PayPal/Razer payment gateways)
            $ttcEncryptedParam = null;
            $crmInvoice = DB::connection('frontenddb')
                ->table('crm_invoice_details')
                ->where('f_invoice_no', $record->invoice_no)
                ->first(['f_id']);
            if ($crmInvoice && $crmInvoice->f_id) {
                $ttcEncrypted = openssl_encrypt((string) $crmInvoice->f_id, 'AES-128-ECB', 'Epicamera@99');
                if ($ttcEncrypted !== false) {
                    $ttcEncryptedParam = base64_encode($ttcEncrypted);
                }
            }

            $this->invoice = [
                'id' => $record->id,
                'reference_no' => $record->invoice_no,
                'encrypted_param' => $encryptedInvoiceParam,
                'ttc_encrypted_param' => $ttcEncryptedParam,
                'date' => $record->invoice_date?->format('d-m-Y') ?? '-',
                'status' => strtolower($record->status ?? 'pending'),
                'payment_status' => strtolower($record->payment_status ?? 'unpaid'),
                'currency' => $record->currency ?? 'MYR',
                'tax_rate' => $taxRate,
                'trx_rate' => $record->currency === 'USD' ? '4.1765' : '1',
                'customer' => $reseller ? $reseller['company_name'] : $companyName,
                'pic_name' => $reseller ? $reseller['pic_name'] : $picName,
                'email' => $reseller ? $reseller['email'] : $email,
                'phone' => $reseller ? $reseller['phone'] : $phone,
                'address' => $reseller ? $reseller['address'] : ($companyDetail?->address ?? ''),
                'subtotal' => round($subtotal, 2),
                'discount' => round($totalDiscount, 2),
                'taxable_amount' => round($taxableAmount, 2),
                'tax_amount' => round($taxAmount, 2),
                'grand_total' => round($grandTotal, 2),
                'reseller' => $reseller,
                'subscriber' => $subscriber,
                'cancel_remark' => $record->cancel_remark,
            ];
        } catch (\Exception $e) {
            $this->hasError = true;
            $this->errorMessage = 'Failed to load invoice: ' . $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    protected function buildItemGroups(array $items, $baseStart): array
    {
        $byYear = [];
        $ungrouped = [];

        foreach ($items as $item) {
            $yearLabel = $item['year'] ?? null;
            if ($yearLabel && preg_match('/(\d+)/', $yearLabel, $m)) {
                $yearNum = (int) $m[1];
                $byYear[$yearNum] ??= [];
                $byYear[$yearNum][] = $item;
            } else {
                $ungrouped[] = $item;
            }
        }

        ksort($byYear);

        $groups = [];

        if (!empty($ungrouped)) {
            $groups[] = ['label' => null, 'period' => null, 'items' => $ungrouped];
        }

        foreach ($byYear as $yearNum => $yearItems) {
            $ordinal = match ($yearNum) {
                1 => '1st', 2 => '2nd', 3 => '3rd',
                default => $yearNum . 'th',
            };

            $period = null;
            if ($baseStart) {
                $start = $baseStart->copy()->addYears($yearNum - 1);
                $end = $start->copy()->addYear()->subDay();
                $period = $start->format('d/m/Y') . ' - ' . $end->format('d/m/Y');
            }

            $groups[] = [
                'label' => $ordinal . ' Year Subscription',
                'period' => $period,
                'items' => $yearItems,
            ];
        }

        return $groups;
    }

    public function goBack(): void
    {
        if ($this->softwareHandoverId) {
            $tab = $this->from === 'products' ? 'products' : 'invoice';
            $this->redirect(
                url('/admin/hr-company-license-details?' . http_build_query([
                    'hrAccountId' => $this->hrAccountId,
                    'hrCompanyId' => $this->hrCompanyId,
                    'tab' => $tab,
                ])),
                navigate: false
            );
        } else {
            $this->redirect(url('/admin/hr-license'), navigate: false);
        }
    }

    public function copyPaymentLink(): void
    {
        $invoiceId = $this->invoice['id'] ?? $this->invoiceId ?? null;

        if (!$invoiceId) {
            \Filament\Notifications\Notification::make()
                ->title('Unable to generate payment link')
                ->body('No invoice ID found.')
                ->danger()
                ->send();
            return;
        }

        // Generate encrypted param from invoice ID
        $encrypted = openssl_encrypt((string) $invoiceId, 'AES-128-ECB', 'Epicamera@99');
        if ($encrypted === false) {
            \Filament\Notifications\Notification::make()
                ->title('Unable to generate payment link')
                ->body('Encryption failed.')
                ->danger()
                ->send();
            return;
        }

        $encryptedParam = base64_encode($encrypted);
        $paymentUrl = url('/paypal_reseller_invoice?iIn=' . $encryptedParam);

        $this->dispatch('copy-to-clipboard', text: $paymentUrl);

        \Filament\Notifications\Notification::make()
            ->title('Payment link copied to clipboard')
            ->body($paymentUrl)
            ->success()
            ->duration(10000)
            ->send();
    }

    public function openPaymentModal(): void
    {
        $invoiceNo = $this->invoice['reference_no'] ?? '';
        $this->paymentForm = [
            'company' => $this->invoice['customer'] ?? 'Unknown Company',
            'currency' => $this->invoice['currency'] ?? 'MYR',
            'amount' => $this->invoice['grand_total'] ?? 0,
            'bill_title' => 'Payment for Invoice ' . $invoiceNo,
            'payment_method' => 'Bank Transfer',
            'ref_no' => '',
            'remark' => '',
        ];

        $this->showPaymentModal = true;
    }

    public function closePaymentModal(): void
    {
        $this->showPaymentModal = false;
    }

    public function submitPayment(): void
    {
        $this->validate([
            'paymentForm.amount' => 'required|numeric|min:0.01',
            'paymentForm.payment_method' => 'required',
        ]);

        $invoiceNo = $this->invoice['reference_no'] ?? '';
        $salesInvoice = HrSalesInvoice::find($this->invoiceId);

        // Generate OR number
        $prefix = 'OR' . now()->format('ym');
        $lastOr = \App\Models\HrOfficialReceipt::where('or_no', 'like', $prefix . '%')
            ->orderBy('or_no', 'desc')
            ->value('or_no');
        $nextSeq = $lastOr ? ((int) substr($lastOr, strlen($prefix))) + 1 : 1;
        $orNo = $prefix . str_pad($nextSeq, 6, '0', STR_PAD_LEFT);

        // Create official receipt
        \App\Models\HrOfficialReceipt::create([
            'or_no' => $orNo,
            'receipt_date' => now()->toDateString(),
            'company_name' => $this->paymentForm['company'],
            'subscriber_name' => $this->invoice['pic_name'] ?? '',
            'description' => $this->paymentForm['bill_title'],
            'currency' => $this->paymentForm['currency'],
            'amount' => $this->paymentForm['amount'],
            'status' => 'paid',
            'created_by' => auth()->user()->name ?? 'System',
            'invoice_no' => $invoiceNo,
            'payment_method' => $this->paymentForm['payment_method'],
            'ref_no' => $this->paymentForm['ref_no'],
            'software_handover_id' => $this->softwareHandoverId,
            'handover_id' => $salesInvoice?->handover_id,
        ]);

        // Mark sales invoice as paid
        if ($salesInvoice) {
            $salesInvoice->update([
                'payment_status' => 'paid',
                'status' => 'paid',
                'payment_method' => $this->paymentForm['payment_method'],
            ]);
        }

        $this->showPaymentModal = false;
        $this->loadInvoice();

        \Filament\Notifications\Notification::make()
            ->title('Official Receipt Created')
            ->success()
            ->body("Receipt {$orNo} created for invoice {$invoiceNo}")
            ->send();
    }

    public function openCancelModal(): void
    {
        $this->cancelForm = [
            'doc_no' => $this->invoice['reference_no'] ?? '',
            'status' => 'cancelled',
            'remark' => '',
        ];
        $this->cancelRemarks = [];
        $this->showCancelModal = true;
    }

    public function closeCancelModal(): void
    {
        $this->showCancelModal = false;
    }

    public function submitCancelInvoice(): void
    {
        $this->validate([
            'cancelForm.status' => 'required',
            'cancelForm.remark' => 'required|min:3',
        ]);

        try {
            $salesInvoice = \App\Models\HrSalesInvoice::find($this->invoiceId);
            if ($salesInvoice) {
                $remark = strtoupper(trim($this->cancelForm['remark']));
                $salesInvoice->update([
                    'status' => 'cancelled',
                    'payment_status' => 'cancelled',
                    'cancel_remark' => $remark,
                ]);

                // Refresh invoice data
                $this->invoice['status'] = 'cancelled';
                $this->invoice['payment_status'] = 'cancelled';
                $this->invoice['cancel_remark'] = $remark;
            }

            $this->showCancelModal = false;
            session()->flash('success', 'Invoice cancelled successfully.');

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to cancel invoice: ' . $e->getMessage());
        }
    }

    public function reactivateInvoice(): void
    {
        try {
            $salesInvoice = \App\Models\HrSalesInvoice::find($this->invoiceId);
            if ($salesInvoice) {
                $salesInvoice->update([
                    'status' => 'pending',
                    'payment_status' => 'unpaid',
                    'cancel_remark' => null,
                ]);

                $this->invoice['status'] = 'pending';
                $this->invoice['payment_status'] = 'unpaid';
                $this->invoice['cancel_remark'] = null;
            }

            session()->flash('success', 'Invoice reactivated successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to reactivate invoice: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.hr-admin-dashboard.view-sales-invoice');
    }
}
