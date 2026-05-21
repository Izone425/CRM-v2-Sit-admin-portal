<?php

namespace App\Services;

use App\Models\SoftwareHandover;
use App\Models\User;
use App\Models\Quotation;
use App\Models\QuotationDetail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class HrdfAutoCountInvoiceService
{
    protected AutoCountInvoiceService $autoCountService;

    public function __construct(AutoCountInvoiceService $autoCountService)
    {
        $this->autoCountService = $autoCountService;
    }

    /**
     * Main method to handle HRDF AutoCount invoice creation for software handover
     */
    public function processHandoverInvoiceCreation(
        SoftwareHandover $handover,
        array $formData
    ): array {
        try {
            $result = [
                'success' => false,
                'debtor_code' => null,
                'invoice_numbers' => [],
                'error' => null,
                'steps' => []
            ];

            // Check if AutoCount integration is requested
            if (!($formData['create_autocount_invoice'] ?? false)) {
                return [
                    'success' => true,
                    'message' => 'AutoCount integration skipped',
                    'skipped' => true
                ];
            }

            // Get quotation groups and validate they haven't been processed
            $quotationGroups = $this->getQuotationGroups($handover);

            if (empty($quotationGroups)) {
                $result['error'] = 'No quotation details found for invoice creation';
                return $result;
            }

            // Check if any quotations already have AutoCount invoices generated
            $allQuotationIds = array_merge(...$quotationGroups);
            $alreadyProcessed = Quotation::whereIn('id', $allQuotationIds)
                ->where('autocount_generated_pi', true)
                ->pluck('pi_reference_no')
                ->toArray();

            if (!empty($alreadyProcessed)) {
                $result['error'] = 'The following quotations already have AutoCount invoices: ' . implode(', ', $alreadyProcessed);
                return $result;
            }

            // Use fixed debtor code
            $result['debtor_code'] = 'ARM-P0062';
            $result['steps'][] = "Using fixed debtor: ARM-P0062";

            $result['steps'][] = "Found " . count($quotationGroups) . " proforma invoice(s) to process";

            // Check if manual invoice numbers are provided
            if (!empty($formData['manual_invoice_numbers'])) {
                $manualNumbers = trim($formData['manual_invoice_numbers']);
                $preGeneratedInvoiceNumbers = array_map('trim', explode(',', $manualNumbers));
                $result['steps'][] = "Using manual invoice numbers: " . implode(', ', $preGeneratedInvoiceNumbers);

                // Validate count matches
                if (count($preGeneratedInvoiceNumbers) !== count($quotationGroups)) {
                    return [
                        'success' => false,
                        'error' => 'Number of manual invoice numbers (' . count($preGeneratedInvoiceNumbers) . ') does not match number of invoices to create (' . count($quotationGroups) . ')',
                        'steps' => $result['steps']
                    ];
                }
            } else {
                // Auto-generate invoice numbers if not provided
                $preGeneratedInvoiceNumbers = $this->generateMultipleInvoiceNumbers($handover, count($quotationGroups));
                $result['steps'][] = "Auto-generated invoice numbers: " . implode(', ', $preGeneratedInvoiceNumbers);
            }

            // Create separate invoice for each proforma invoice
            foreach ($quotationGroups as $index => $quotationIds) {
                $result['steps'][] = "Processing proforma invoice group " . ($index + 1) . " with quotations: " . implode(', ', $quotationIds);

                // Use pre-generated invoice number
                $invoiceNo = $preGeneratedInvoiceNumbers[$index];
                $result['invoice_numbers'][] = $invoiceNo;

                // Create invoice for this specific group
                $invoiceResult = $this->createInvoiceForQuotationGroup($handover, $result['debtor_code'], $quotationIds, $invoiceNo);

                if (!$invoiceResult['success']) {
                    $result['error'] = "Failed to create invoice " . ($index + 1) . ": " . $invoiceResult['error'];
                    $result['steps'][] = "Invoice " . ($index + 1) . " creation failed";

                    if (str_contains(strtolower($invoiceResult['error']), 'timeout') ||
                        str_contains(strtolower($invoiceResult['error']), 'connection')) {
                        $result['connectivity_issue'] = true;
                        $result['error'] = 'AutoCount API timeout. The handover was completed, but please create the invoices manually in AutoCount.';
                    }

                    return $result;
                }

                // Mark all quotations in this group as having AutoCount invoice generated
                foreach ($quotationIds as $quotationId) {
                    Quotation::where('id', $quotationId)->update([
                        'autocount_generated_pi' => true
                    ]);

                    Log::info('Marked quotation as having AutoCount invoice generated', [
                        'quotation_id' => $quotationId,
                        'invoice_no' => $invoiceNo,
                        'handover_id' => $handover->id
                    ]);
                }

                $result['steps'][] = "Invoice " . ($index + 1) . " created successfully: {$invoiceNo}";
                $result['steps'][] = "Marked quotations as processed: " . implode(', ', $quotationIds);
            }

            $result['success'] = true;
            $result['total_invoices'] = count($result['invoice_numbers']);
            $result['steps'][] = "Successfully created " . count($result['invoice_numbers']) . " invoices: " . implode(', ', $result['invoice_numbers']);

            // Update handover record with all invoice numbers
            $handover->update([
                'autocount_debtor_code' => $result['debtor_code'],
                'autocount_invoice_no' => json_encode($result['invoice_numbers'])
            ]);

            $result['steps'][] = 'Handover record updated with AutoCount details';

            Log::info('HRDF AutoCount integration completed successfully', [
                'handover_id' => $handover->id,
                'debtor_code' => $result['debtor_code'],
                'invoice_numbers' => $result['invoice_numbers'],
                'total_invoices' => count($result['invoice_numbers']),
                'processed_quotations' => $allQuotationIds,
                'steps' => $result['steps']
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('HRDF AutoCount integration failed', [
                'handover_id' => $handover->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'steps' => array_merge($result['steps'] ?? [], ['Exception occurred'])
            ];
        }
    }

    /**
     * Get HRDF quotation groups from proforma_invoice_hrdf
     */
    public function getQuotationGroups(SoftwareHandover $handover): array
    {
        $groups = [];

        if ($handover->proforma_invoice_hrdf) {
            $hrdfPis = is_string($handover->proforma_invoice_hrdf)
                ? json_decode($handover->proforma_invoice_hrdf, true)
                : $handover->proforma_invoice_hrdf;

            if (is_array($hrdfPis)) {
                // Filter out quotations that already have AutoCount invoices
                $validPis = Quotation::whereIn('id', $hrdfPis)
                    ->where('autocount_generated_pi', false)
                    ->pluck('id')
                    ->toArray();

                // Each quotation ID becomes its own invoice
                foreach ($validPis as $quotationId) {
                    $groups[] = [$quotationId];
                }

                Log::info('Generated quotation groups for HRDF invoices', [
                    'handover_id' => $handover->id,
                    'total_quotations' => count($hrdfPis),
                    'valid_quotations' => count($validPis),
                    'groups_created' => count($groups),
                    'groups' => $groups
                ]);
            }
        }

        return $groups;
    }

    /**
     * Create invoice for a specific HRDF quotation group
     */
    protected function createInvoiceForQuotationGroup(SoftwareHandover $handover, string $customerCode, array $quotationIds, string $invoiceNo): array
    {
        $customerName = $handover->company_name;
        if (!empty($quotationIds)) {
            $quotation = Quotation::with('subsidiary', 'lead.companyDetail')->find($quotationIds[0]);
            if ($quotation && $quotation->subsidiary && !empty($quotation->subsidiary->company_name)) {
                $customerName = $quotation->subsidiary->company_name;
            } elseif ($quotation && $quotation->lead && $quotation->lead->companyDetail && !empty($quotation->lead->companyDetail->company_name)) {
                $customerName = $quotation->lead->companyDetail->company_name;
            }
        }

        $invoiceData = [
            'company' => $this->determineCompanyByHandover($handover),
            'customer_code' => $customerCode,
            'document_no' => $invoiceNo,
            'document_date' => now()->format('Y-m-d'),
            'description' => 'Software Handover Invoice - ' . $customerName,
            'salesperson' => $this->getAutoCountSalesperson($handover),
            'round_method' => 0,
            'inclusive' => true,
            'details' => $this->getInvoiceDetailsFromQuotationIds($quotationIds),
            'uDFCustomerName' => $customerName,
            'uDFLicenseNumber' => $handover->tt_invoice_number ?? '',
        ];

        return $this->autoCountService->createInvoice($invoiceData);
    }

    /**
     * Get invoice line items from quotation IDs
     */
    protected function getInvoiceDetailsFromQuotationIds(array $quotationIds): array
    {
        if (empty($quotationIds)) {
            return [[
                'account' => $this->getDefaultAccountCode(),
                'itemCode' => 'TCL_ACCESS-NEW',
                'location' => 'HQ',
                'quantity' => 1,
                'uom' => 'UNIT',
                'unitPrice' => 1275,
                'amount' => 1275,
                'taxCode' => 'SV-8',
                'taxRate' => 8,
            ]];
        }

        $quotationDetails = QuotationDetail::whereIn('quotation_id', $quotationIds)
            ->with('product')
            ->get();

        $groupedDetails = [];

        foreach ($quotationDetails as $detail) {
            $product = $detail->product;
            $productCode = $product->code ?? 'ITEM-' . $product->id;
            $baseUnitPrice = (float) $detail->unit_price;
            $account = $this->getAccountFromProduct($product);

            $taxCode = '';
            $taxRate = 0;

            if ($product && $product->taxable) {
                $taxCode = 'SV-8';
                $taxRate = 8;
            }

            $taxInclusiveUnitPrice = $baseUnitPrice;
            if ($product && $product->taxable && $taxRate > 0) {
                $taxInclusiveUnitPrice = $baseUnitPrice * (1 + ($taxRate / 100));
            }

            $key = $productCode . '|' . $taxInclusiveUnitPrice . '|' . $account . '|' . $taxCode . '|' . $taxRate;

            if (isset($groupedDetails[$key])) {
                $groupedDetails[$key]['quantity'] += (float) $detail->quantity;
                $groupedDetails[$key]['amount'] += (float) $detail->total_after_tax;
            } else {
                $groupedDetails[$key] = [
                    'account' => $account,
                    'itemCode' => $productCode,
                    'location' => 'HQ',
                    'quantity' => (float) $detail->quantity,
                    'uom' => 'UNIT',
                    'unitPrice' => $taxInclusiveUnitPrice,
                    'amount' => (float) $detail->total_after_tax,
                    'taxCode' => $taxCode,
                    'taxRate' => $taxRate,
                ];
            }

            Log::info('HRDF account assignment for product', [
                'quotation_ids' => $quotationIds,
                'product_id' => $product->id ?? 'unknown',
                'product_code' => $productCode,
                'gl_posting' => $product->gl_posting ?? 'null',
                'assigned_account' => $account,
                'base_unit_price' => $baseUnitPrice,
                'tax_inclusive_unit_price' => $taxInclusiveUnitPrice,
                'quantity' => $detail->quantity,
                'amount' => $detail->total_after_tax,
                'taxable' => $product->taxable ?? false,
                'tax_code' => $taxCode,
                'tax_rate' => $taxRate,
                'combined_key' => $key
            ]);
        }

        return array_values($groupedDetails);
    }

    /**
     * Generate multiple HRDF invoice numbers (EHIN format)
     */
    protected function generateMultipleInvoiceNumbers(SoftwareHandover $handover, int $count): array
    {
        $year = date('y');
        $month = date('m');
        $yearMonth = $year . $month;

        $latestInvoice = \App\Models\CrmHrdfInvoice::where('invoice_no', 'LIKE', "EHIN{$yearMonth}-%")
            ->orderByRaw('CAST(SUBSTRING(invoice_no, -4) AS UNSIGNED) DESC')
            ->first();

        $startSequence = 1;
        if ($latestInvoice) {
            preg_match("/EHIN{$yearMonth}-(\d+)/", $latestInvoice->invoice_no, $matches);
            $startSequence = (isset($matches[1]) ? intval($matches[1]) : 0) + 1;
        }

        $invoiceNumbers = [];
        for ($i = 0; $i < $count; $i++) {
            $sequence = str_pad($startSequence + $i, 4, '0', STR_PAD_LEFT);
            $invoiceNumbers[] = "EHIN{$yearMonth}-{$sequence}";
        }

        Log::info('Generated multiple HRDF invoice numbers', [
            'handover_id' => $handover->id,
            'count' => $count,
            'start_sequence' => $startSequence,
            'invoice_numbers' => $invoiceNumbers
        ]);

        return $invoiceNumbers;
    }

    /**
     * Generate single HRDF invoice document number (EHIN format)
     */
    protected function generateInvoiceDocumentNumber(SoftwareHandover $handover, int $invoiceIndex = 0): string
    {
        $year = date('y');
        $month = date('m');
        $yearMonth = $year . $month;

        $latestInvoice = \App\Models\CrmHrdfInvoice::where('invoice_no', 'LIKE', "EHIN{$yearMonth}-%")
            ->orderByRaw('CAST(SUBSTRING(invoice_no, -4) AS UNSIGNED) DESC')
            ->first();

        $nextSequence = 1 + $invoiceIndex;
        if ($latestInvoice) {
            preg_match("/EHIN{$yearMonth}-(\d+)/", $latestInvoice->invoice_no, $matches);
            $nextSequence = (isset($matches[1]) ? intval($matches[1]) : 0) + 1 + $invoiceIndex;
        }

        $sequence = str_pad($nextSequence, 4, '0', STR_PAD_LEFT);

        return "EHIN{$yearMonth}-{$sequence}";
    }

    /**
     * Generate HRDF invoice preview data
     */
    public function generateInvoicePreview(SoftwareHandover $handover): array
    {
        $quotationGroups = $this->getQuotationGroups($handover);

        if (empty($quotationGroups)) {
            return [
                'invoices' => [],
                'total_invoices' => 0,
                'grand_total' => 0,
                'salesperson' => $this->getAutoCountSalesperson($handover),
                'company' => $handover->company_name,
                'message' => 'No quotation details found'
            ];
        }

        $invoices = [];
        $grandTotal = 0;

        foreach ($quotationGroups as $index => $quotationIds) {
            $details = QuotationDetail::whereIn('quotation_id', $quotationIds)
                ->with('product')
                ->get();

            $groupedItems = [];
            $invoiceTotal = 0;

            foreach ($details as $detail) {
                $productCode = $detail->product->code ?? 'Item-' . $detail->product_id;
                $unitPrice = (float) $detail->unit_price;
                $amount = (float) $detail->total_after_tax;
                $quantity = (float) $detail->quantity;

                $key = $productCode . '|' . $unitPrice;

                if (isset($groupedItems[$key])) {
                    $groupedItems[$key]['quantity'] += $quantity;
                    $groupedItems[$key]['amount'] += $amount;
                } else {
                    $groupedItems[$key] = [
                        'code' => $productCode,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'amount' => $amount
                    ];
                }

                $invoiceTotal += $amount;
            }

            $items = array_values($groupedItems);

            $invoices[] = [
                'invoice_no' => $this->generateInvoiceDocumentNumber($handover, $index),
                'items' => $items,
                'total' => $invoiceTotal,
                'quotation_ids' => $quotationIds
            ];

            $grandTotal += $invoiceTotal;
        }

        return [
            'invoices' => $invoices,
            'total_invoices' => count($invoices),
            'grand_total' => $grandTotal,
            'salesperson' => $this->getAutoCountSalesperson($handover),
            'company' => $handover->company_name
        ];
    }

    /**
     * Create new debtor from handover and form data
     */
    protected function createNewDebtor(SoftwareHandover $handover, array $formData): array
    {
        $debtorData = [
            'company' => $this->determineCompanyByHandover($handover),
            'control_account' => 'ARM-0112-01',
            'company_name' => $formData['debtor_company_name'],
            'addr1' => $formData['debtor_addr1'] ?? '',
            'addr2' => $formData['debtor_addr2'] ?? '',
            'addr3' => $formData['debtor_addr3'] ?? '',
            'post_code' => $formData['debtor_postcode'] ?? '',
            'contact_person' => $formData['debtor_contact_person'],
            'phone' => $formData['debtor_phone'] ?? '',
            'mobile' => $formData['debtor_mobile'] ?? '',
            'email' => $formData['debtor_email'] ?? '',
            'area_code' => $formData['debtor_area_code'] ?? 'MYS-SEL',
            'sales_agent' => $this->getAutoCountSalesperson($handover),
            'tax_entity_id' => 3,
        ];

        $result = $this->autoCountService->createDebtor($debtorData);

        if ($result['success']) {
            try {
                \App\Models\Debtor::create([
                    'debtor_code' => $result['debtor_code'],
                    'debtor_name' => $formData['debtor_company_name'],
                    'tax_entity_id' => 3,
                ]);

                Log::info('Debtor saved to local database', [
                    'debtor_code' => $result['debtor_code'],
                    'debtor_name' => $formData['debtor_company_name']
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to save debtor to local database', [
                    'debtor_code' => $result['debtor_code'],
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $result;
    }

    /**
     * Create invoice for handover
     */
    protected function createInvoiceForHandover(SoftwareHandover $handover, string $customerCode): array
    {
        $quotationIds = $this->getQuotationIds($handover);
        $customerName = $handover->company_name;
        if (!empty($quotationIds)) {
            $quotation = Quotation::with('subsidiary', 'lead.companyDetail')->find($quotationIds[0]);
            if ($quotation) {
                if ($quotation->subsidiary_id && $quotation->subsidiary) {
                    $customerName = $quotation->subsidiary->company_name;
                } elseif ($quotation->lead && $quotation->lead->companyDetail) {
                    $customerName = $quotation->lead->companyDetail->company_name;
                }
            }
        }

        $invoiceData = [
            'company' => $this->determineCompanyByHandover($handover),
            'customer_code' => $customerCode,
            'document_no' => $this->generateInvoiceDocumentNumber($handover),
            'document_date' => now()->format('Y-m-d'),
            'description' => 'Software Handover Invoice - ' . $handover->company_name,
            'salesperson' => $this->getAutoCountSalesperson($handover),
            'round_method' => 0,
            'inclusive' => true,
            'details' => $this->getInvoiceDetailsFromHandover($handover),
            'uDFCustomerName' => $customerName,
            'uDFLicenseNumber' => $handover->tt_invoice_number ?? '',
        ];

        return $this->autoCountService->createInvoice($invoiceData);
    }

    /**
     * Get AutoCount salesperson name from handover
     */
    protected function getAutoCountSalesperson(SoftwareHandover $handover): string
    {
        if ($handover->salesperson) {
            $user = User::where('name', $handover->salesperson)->first();
            if ($user && $user->autocount_name) {
                return $user->autocount_name;
            }
        }

        if ($handover->lead_id) {
            $lead = \App\Models\Lead::find($handover->lead_id);
            if ($lead && $lead->salesperson) {
                $user = User::find($lead->salesperson);
                if ($user && $user->autocount_name) {
                    return $user->autocount_name;
                }
            }
        }

        return 'ADMIN';
    }

    /**
     * Get invoice details from handover quotations
     */
    protected function getInvoiceDetailsFromHandover(SoftwareHandover $handover): array
    {
        $quotationIds = $this->getQuotationIds($handover);

        if (empty($quotationIds)) {
            return [[
                'account' => $this->getDefaultAccountCode(),
                'itemCode' => 'TCL_ACCESS-NEW',
                'location' => 'HQ',
                'quantity' => 1,
                'uom' => 'UNIT',
                'unitPrice' => 1275,
                'amount' => 1275,
                'taxCode' => 'SV-8',
                'taxRate' => 8,
            ]];
        }

        $quotationDetails = QuotationDetail::whereIn('quotation_id', $quotationIds)
            ->with('product')
            ->get();

        $groupedDetails = [];

        foreach ($quotationDetails as $detail) {
            $product = $detail->product;
            $productCode = $product->code ?? 'ITEM-' . $product->id;
            $baseUnitPrice = (float) $detail->unit_price;
            $account = $this->getAccountFromProduct($product);

            $taxCode = '';
            $taxRate = 0;

            if ($product && $product->taxable) {
                $taxCode = 'SV-8';
                $taxRate = 8;
            }

            $taxInclusiveUnitPrice = $baseUnitPrice;
            if ($product && $product->taxable && $taxRate > 0) {
                $taxInclusiveUnitPrice = $baseUnitPrice * (1 + ($taxRate / 100));
            }

            $key = $productCode . '|' . $taxInclusiveUnitPrice . '|' . $account . '|' . $taxCode . '|' . $taxRate;

            if (isset($groupedDetails[$key])) {
                $groupedDetails[$key]['quantity'] += (float) $detail->quantity;
                $groupedDetails[$key]['amount'] += (float) $detail->total_after_tax;
            } else {
                $groupedDetails[$key] = [
                    'account' => $account,
                    'itemCode' => $productCode,
                    'location' => 'HQ',
                    'quantity' => (float) $detail->quantity,
                    'uom' => 'UNIT',
                    'unitPrice' => $taxInclusiveUnitPrice,
                    'amount' => (float) $detail->total_after_tax,
                    'taxCode' => $taxCode,
                    'taxRate' => $taxRate,
                ];
            }
        }

        return array_values($groupedDetails);
    }

    /**
     * Get account code from product GL posting
     */
    protected function getAccountFromProduct($product): string
    {
        if ($product && $product->gl_posting) {
            $glPosting = trim($product->gl_posting);

            if (preg_match('/^\d{5}-\d{3}$/', $glPosting)) {
                return $glPosting;
            }

            Log::warning('Invalid GL posting format found', [
                'product_id' => $product->id,
                'product_code' => $product->code,
                'gl_posting' => $glPosting
            ]);
        }

        return $this->getDefaultAccountCode();
    }

    /**
     * Get default account code
     */
    protected function getDefaultAccountCode(): string
    {
        return '40000-000';
    }

    /**
     * Get valid account code for products (legacy fallback)
     */
    protected function getAccountCodeForProduct($product = null): string
    {
        if ($product && $product->gl_posting) {
            $account = $this->getAccountFromProduct($product);
            if ($account !== $this->getDefaultAccountCode()) {
                return $account;
            }
        }

        $accountMapping = [
            'TCL_ACCESS-NEW' => '40001-000',
            'TCL_ACCESS-RENEWAL' => '40001-000',
            'TCL_TA' => '40002-000',
            'TCL_LEAVE' => '40003-000',
            'TCL_CLAIM' => '40004-000',
            'TCL_PAYROLL' => '40005-000',
            'TCL_HIRE-NEW' => '40006-000',
            'TCL_HIRE-RENEWAL' => '40006-000',
            'TCL_APPRAISAL' => '40007-000',
            'TCL_POWER' => '40008-000',
            'TRAINING' => '40100-000',
            'HRDF_TRAINING' => '40100-000',
            'DEFAULT' => '40000-000',
        ];

        if ($product && $product->code) {
            $productCode = $product->code;

            if (isset($accountMapping[$productCode])) {
                return $accountMapping[$productCode];
            }

            foreach ($accountMapping as $code => $account) {
                if (str_contains($productCode, $code) || str_contains($code, $productCode)) {
                    return $account;
                }
            }
        }

        return $this->getDefaultAccountCode();
    }

    /**
     * Get HRDF quotation IDs from handover
     */
    protected function getQuotationIds(SoftwareHandover $handover): array
    {
        $quotationIds = [];

        if ($handover->proforma_invoice_hrdf) {
            $hrdfPis = is_string($handover->proforma_invoice_hrdf)
                ? json_decode($handover->proforma_invoice_hrdf, true)
                : $handover->proforma_invoice_hrdf;
            if (is_array($hrdfPis)) {
                $quotationIds = array_merge($quotationIds, $hrdfPis);
            }
        }

        return array_unique($quotationIds);
    }

    /**
     * Determine company by handover
     */
    protected function determineCompanyByHandover(SoftwareHandover $handover): string
    {
        return 'TIMETEC CLOUD Sandbox';
    }

    /**
     * Get existing debtors for dropdown
     */
    public function getExistingDebtors(SoftwareHandover $handover): array
    {
        try {
            return \App\Models\Debtor::select('debtor_code', 'debtor_name')
                ->orderBy('debtor_name')
                ->get()
                ->mapWithKeys(function ($debtor) {
                    $displayText = $debtor->debtor_code . ' - ' . $debtor->debtor_name;
                    return [$debtor->debtor_code => $displayText];
                })
                ->toArray();
        } catch (\Exception $e) {
            Log::warning('Failed to fetch debtors from database', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get existing debtors from local database
     */
    public function getExistingDebtorsFromAutoCount(): array
    {
        try {
            return \App\Models\Debtor::select('debtor_code', 'debtor_name')
                ->orderBy('debtor_name')
                ->get()
                ->mapWithKeys(function ($debtor) {
                    $displayText = $debtor->debtor_code . ' - ' . $debtor->debtor_name;
                    return [$debtor->debtor_code => $displayText];
                })
                ->toArray();
        } catch (\Exception $e) {
            Log::error('Failed to fetch debtors from database table', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
}
