<?php

namespace App\Services;

use App\Models\SoftwareHandover;
use App\Models\User;
use App\Models\Quotation;
use App\Models\QuotationDetail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProductAutoCountInvoiceService
{
    protected AutoCountInvoiceService $autoCountService;

    public function __construct(AutoCountInvoiceService $autoCountService)
    {
        $this->autoCountService = $autoCountService;
    }

    /**
     * Main method to handle product AutoCount invoice creation for software handover
     */
    public function processProductInvoiceCreation(
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

            // Check if product invoice creation is requested
            if (!($formData['create_product_invoice'] ?? false)) {
                return [
                    'success' => true,
                    'message' => 'Product invoice creation skipped',
                    'skipped' => true
                ];
            }

            // Get SOFTWARE ONLY quotation groups from proforma_invoice_product
            $quotationGroups = $this->getProductQuotationGroups($handover);

            if (empty($quotationGroups)) {
                $result['error'] = 'No software products found in proforma_invoice_product for invoice creation';
                return $result;
            }

            // Use selected debtor code
            $result['debtor_code'] = $formData['product_debtor_selection'];
            $result['steps'][] = "Using selected debtor: {$result['debtor_code']}";

            $result['steps'][] = "Found " . count($quotationGroups) . " software product invoice(s) to process";

            // Create separate invoice for each product group
            foreach ($quotationGroups as $index => $quotationIds) {
                $result['steps'][] = "Processing product invoice group " . ($index + 1) . "...";

                // Generate unique invoice number for each invoice
                $invoiceNo = $this->generateProductInvoiceDocumentNumber($handover, $index);
                $result['invoice_numbers'][] = $invoiceNo;

                // Create invoice for this specific group
                $invoiceResult = $this->createProductInvoiceForQuotationGroup($handover, $result['debtor_code'], $quotationIds, $invoiceNo);

                if (!$invoiceResult['success']) {
                    $result['error'] = "Failed to create product invoice " . ($index + 1) . ": " . $invoiceResult['error'];
                    $result['steps'][] = "Product invoice " . ($index + 1) . " creation failed";
                    return $result;
                }

                // Mark quotations as processed
                foreach ($quotationIds as $quotationId) {
                    Quotation::where('id', $quotationId)->update([
                        'autocount_generated_pi' => true
                    ]);
                }

                $result['steps'][] = "Product invoice " . ($index + 1) . " created successfully: {$invoiceNo}";
            }

            $result['success'] = true;
            $result['steps'][] = "All " . count($quotationGroups) . " product invoices created successfully";

            return $result;

        } catch (\Exception $e) {
            Log::error('Product AutoCount invoice integration failed', [
                'handover_id' => $handover->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get SOFTWARE ONLY quotation groups from proforma_invoice_product
     */
    protected function getProductQuotationGroups(SoftwareHandover $handover): array
    {
        $groups = [];

        if ($handover->proforma_invoice_product) {
            $productPis = is_string($handover->proforma_invoice_product)
                ? json_decode($handover->proforma_invoice_product, true)
                : $handover->proforma_invoice_product;

            if (is_array($productPis)) {
                // Filter quotations that contain ONLY SOFTWARE products
                foreach ($productPis as $quotationId) {
                    $hasSoftwareProducts = QuotationDetail::where('quotation_id', $quotationId)
                        ->whereHas('product', function($query) {
                            $query->where('solution', 'software');
                        })
                        ->exists();

                    if ($hasSoftwareProducts) {
                        $groups[] = [$quotationId];

                        Log::info('Including quotation for software product invoice', [
                            'quotation_id' => $quotationId,
                            'handover_id' => $handover->id,
                        ]);
                    } else {
                        Log::info('Skipping quotation - no software products found', [
                            'quotation_id' => $quotationId,
                            'handover_id' => $handover->id,
                        ]);
                    }
                }
            }
        }

        return $groups;
    }

    /**
     * Create product invoice for quotation group (SOFTWARE ONLY)
     */
    protected function createProductInvoiceForQuotationGroup(SoftwareHandover $handover, string $customerCode, array $quotationIds, string $invoiceNo): array
    {
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
            'document_no' => $invoiceNo,
            'document_date' => now()->format('Y-m-d'),
            'description' => 'Software Product Invoice - ' . $customerName,
            'salesperson' => $this->getAutoCountSalesperson($handover),
            'round_method' => 0,
            'inclusive' => true,
            'details' => $this->getSoftwareProductInvoiceDetails($quotationIds),
            'uDFCustomerName' => $customerName,
            'uDFLicenseNumber' => $handover->tt_invoice_number ?? '',
        ];

        return $this->autoCountService->createInvoice($invoiceData);
    }

    /**
     * Get SOFTWARE ONLY invoice details from quotation IDs
     */
    protected function getSoftwareProductInvoiceDetails(array $quotationIds): array
    {
        if (empty($quotationIds)) {
            return [[
                'account' => $this->getDefaultAccountCode(),
                'itemCode' => 'TCL_ACCESS-NEW',
                'location' => 'HQ',
                'quantity' => 1,
                'uom' => 'USER',
                'unitPrice' => 1275,
                'amount' => 1275,
                'taxCode' => 'SV-8',
                'taxRate' => 8,
            ]];
        }

        // Only get quotation details for SOFTWARE products
        $quotationDetails = QuotationDetail::whereIn('quotation_id', $quotationIds)
            ->whereHas('product', function($query) {
                $query->where('solution', 'software');
            })
            ->with('product')
            ->get();

        $groupedDetails = [];

        foreach ($quotationDetails as $detail) {
            $product = $detail->product;

            // Double-check that this is a software product
            if ($product->solution !== 'software') {
                Log::warning('Non-software product found in software invoice processing', [
                    'product_id' => $product->id,
                    'product_code' => $product->code,
                    'solution' => $product->solution,
                ]);
                continue;
            }

            $productCode = $product->code ?? 'ITEM-' . $product->id;
            $baseUnitPrice = (float) $detail->unit_price;
            $account = $this->getAccountFromProduct($product);

            // Tax information
            $taxCode = '';
            $taxRate = 0;
            if ($product && $product->taxable) {
                $taxCode = 'SV-8';
                $taxRate = 8;
            }

            // Calculate tax-inclusive unit price for AutoCount
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
                    'uom' => 'USER',
                    'unitPrice' => $taxInclusiveUnitPrice,
                    'amount' => (float) $detail->total_after_tax,
                    'taxCode' => $taxCode,
                    'taxRate' => $taxRate,
                ];
            }

            Log::info('Software product included in invoice', [
                'quotation_ids' => $quotationIds,
                'product_id' => $product->id,
                'product_code' => $productCode,
                'solution' => $product->solution,
                'amount' => $detail->total_after_tax,
                'uom' => 'USER',
            ]);
        }

        return array_values($groupedDetails);
    }

    /**
     * Generate product invoice document number (EPIN format)
     */
    protected function generateProductInvoiceDocumentNumber(SoftwareHandover $handover, int $invoiceIndex = 0): string
    {
        $year = date('y');
        $month = date('m');
        $yearMonth = $year . $month;

        $latestInvoice = \App\Models\CrmHrdfInvoice::where('invoice_no', 'LIKE', "EPIN{$yearMonth}-%")
            ->orderByRaw('CAST(SUBSTRING(invoice_no, -4) AS UNSIGNED) DESC')
            ->first();

        $nextSequence = 1 + $invoiceIndex;
        if ($latestInvoice) {
            preg_match("/EPIN{$yearMonth}-(\d+)/", $latestInvoice->invoice_no, $matches);
            $nextSequence = (isset($matches[1]) ? intval($matches[1]) : 0) + 1 + $invoiceIndex;
        }

        $sequence = str_pad($nextSequence, 4, '0', STR_PAD_LEFT);

        return "EPIN{$yearMonth}-{$sequence}";
    }

    /**
     * Generate product invoice preview (SOFTWARE ONLY)
     */
    public function generateProductInvoicePreview(SoftwareHandover $handover, string $selectedDebtor = null): array
    {
        $quotationGroups = $this->getProductQuotationGroups($handover);

        if (empty($quotationGroups)) {
            return [
                'invoices' => [],
                'total_invoices' => 0,
                'grand_total' => 0,
                'salesperson' => $this->getAutoCountSalesperson($handover),
                'company' => $handover->company_name,
                'message' => 'No software products found in proforma_invoice_product'
            ];
        }

        $invoices = [];
        $grandTotal = 0;

        foreach ($quotationGroups as $index => $quotationIds) {
            // Only get SOFTWARE products
            $details = QuotationDetail::whereIn('quotation_id', $quotationIds)
                ->whereHas('product', function($query) {
                    $query->where('solution', 'software');
                })
                ->with('product')
                ->get();

            $groupedItems = [];
            $invoiceTotal = 0;

            foreach ($details as $detail) {
                $product = $detail->product;

                // Skip if not software
                if ($product->solution !== 'software') {
                    continue;
                }

                $productCode = $product->code ?? 'Item-' . $detail->product_id;
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

            if (!empty($groupedItems)) {
                $items = array_values($groupedItems);

                $invoices[] = [
                    'invoice_no' => $this->generateProductInvoiceDocumentNumber($handover, $index),
                    'items' => $items,
                    'total' => $invoiceTotal,
                    'quotation_ids' => $quotationIds
                ];

                $grandTotal += $invoiceTotal;
            }
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
