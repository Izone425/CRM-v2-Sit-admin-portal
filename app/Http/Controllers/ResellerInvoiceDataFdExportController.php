<?php
namespace App\Http\Controllers;

use App\Classes\Encryptor;
use App\Models\ResellerHandoverFd;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ResellerInvoiceDataFdExportController extends Controller
{
    public function exportRenewalSales($resellerHandoverFdId)
    {
        return $this->exportInvoiceData($resellerHandoverFdId, [108, 109, 110, 111], 'RENEWAL');
    }

    public function exportAddOnSales($resellerHandoverFdId)
    {
        return $this->exportInvoiceData($resellerHandoverFdId, [114, 115, 116, 117], 'ADDON(R)');
    }

    private function exportInvoiceData($resellerHandoverFdId, array $productIds, string $type)
    {
        try {
            Log::info("Starting FD {$type} Sales Invoice Data export for Reseller Handover FD ID: " . $resellerHandoverFdId);

            // Decrypt the reseller handover FD ID
            $decryptedHandoverId = Encryptor::decrypt($resellerHandoverFdId);
            Log::info('Decrypted reseller handover FD ID: ' . $decryptedHandoverId);

            // Get the reseller handover FD
            $resellerHandover = ResellerHandoverFd::findOrFail($decryptedHandoverId);

            Log::info('Reseller handover FD found: ' . $resellerHandover->id);

            // Get timetec_proforma_invoice
            $invoiceNo = $resellerHandover->timetec_proforma_invoice;

            if (empty($invoiceNo)) {
                Log::warning('No timetec_proforma_invoice found for reseller handover FD ID: ' . $decryptedHandoverId);
                return back()->with('error', 'No TimeTec proforma invoice found for this reseller handover.');
            }

            Log::info('TimeTec Proforma Invoice: ' . $invoiceNo);

            // Map product IDs to keywords
            $productKeywords = [
                108 => 'TA',    // Renewal TA
                109 => 'Leave', // Renewal Leave
                110 => 'Claim', // Renewal Claim
                111 => 'Payroll', // Renewal Payroll
                114 => 'TA',    // AddOn TA
                115 => 'Leave', // AddOn Leave
                116 => 'Claim', // AddOn Claim
                117 => 'Payroll', // AddOn Payroll
            ];

            // Get keywords for the provided product IDs
            $keywords = [];
            foreach ($productIds as $productId) {
                if (isset($productKeywords[$productId])) {
                    $keywords[] = $productKeywords[$productId];
                }
            }

            // Remove duplicates
            $keywords = array_unique($keywords);

            Log::info('Product IDs to filter: ' . implode(', ', $productIds));
            Log::info('Keywords to search in f_name: ' . implode(', ', $keywords));

            // First, let's see what products exist in the invoice
            $allInvoiceDetails = DB::connection('frontenddb')
                ->table('crm_invoice_details')
                ->where('f_invoice_no', $invoiceNo)
                ->get();

            Log::info('All products in invoice ' . $invoiceNo . ': ' . $allInvoiceDetails->pluck('f_name')->unique()->implode(', '));

            // Query crm_invoice_details from frontenddb - filter by keywords in f_name
            $invoiceDetails = DB::connection('frontenddb')
                ->table('crm_invoice_details')
                ->where('f_invoice_no', $invoiceNo)
                ->where(function($query) use ($keywords) {
                    foreach ($keywords as $keyword) {
                        $query->orWhere('f_name', 'LIKE', "%{$keyword}%");
                    }
                })
                ->get();

            // Get products for mapping to codes
            $products = \App\Models\Product::whereIn('id', $productIds)->get();

            Log::info("Total {$type} items collected from crm_invoice_details: " . $invoiceDetails->count());

            // Check if we have any items after filtering
            if ($invoiceDetails->isEmpty()) {
                Log::warning("No {$type} products found in crm_invoice_details");
                return back()->with('error', "No {$type} products found in the invoice details.");
            }

            // Create Excel file
            Log::info('Creating Excel spreadsheet...');
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle("{$type} Sales Invoice Data");

            // Get first invoice detail for common data
            $firstDetail = $invoiceDetails->first();

            // DebtorCode - Get from ResellerV2 based on company name
            $debtorCode = \App\Models\ResellerV2::where('company_name', $resellerHandover->reseller_company_name)
                ->value('debtor_code') ?? '';
            Log::info('DebtorCode: ' . $debtorCode);

            // DocNo - Use reseller handover fd_id
            $docNo = $resellerHandover->fd_id ?? '';
            Log::info('DocNo: ' . $docNo);

            // DocDate is today's date in j/n/Y format
            $docDate = date('j/n/Y');
            Log::info('DocDate: ' . $docDate);

            // SalesAgent
            $salesAgent = $this->mapSalesAgent($resellerHandover);
            Log::info('Sales Agent determined: ' . $salesAgent);

            // CurrencyCode from invoice detail with fallback
            $currencyCode = $firstDetail->f_currency ?? 'MYR';
            Log::info('Currency: ' . $currencyCode);

            // Currency rate based on currency
            $currencyRate = $currencyCode === 'USD' ? null : '1';

            // UDF fields
            $salesAdmin = $salesAgent;
            $billingType = $type === 'RENEWAL' ? 'Renewal' : 'Renewal Addon';
            $cancelled = '';
            $udfSupport = auth()->user()->autocount_name ?? '';

            // Create header row
            $headers = [
                'DocNo',
                'DocDate',
                'CompanyName',
                'DebtorCode',
                'SalesAgent',
                'CurrencyCode',
                'CurrencyRate',
                'UDF_IV_LicenseNumber',
                'UDF_IV_SalesAdmin',
                'UDF_IV_Support',
                'UDF_IV_BillingType',
                'Cancelled',
                'ItemCode',
                'Qty',
                'UnitPrice',
                'TaxCode',
                'TariffCode',
            ];

            // Apply header row
            $sheet->fromArray([$headers], null, 'A1');
            Log::info('Headers applied to spreadsheet');

            // Style the header row
            $headerStyle = [
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D3D3D3']],
            ];
            $sheet->getStyle('A1:Q1')->applyFromArray($headerStyle);
            Log::info('Header styling applied');

            $row = 2; // Start from row 2 for data

            // Process each item
            Log::info("Processing {$type} items...");

            $isFirstRow = true;
            foreach ($invoiceDetails as $detail) {
                // ItemCode - Get product code by matching keyword in f_name
                $itemCode = '';
                $tariffCode = '';

                foreach ($productKeywords as $productId => $keyword) {
                    if (in_array($productId, $productIds) && stripos($detail->f_name, $keyword) !== false) {
                        $product = $products->firstWhere('id', $productId);
                        if ($product && $product->code) {
                            $itemCode = $product->code;
                            $tariffCode = $product->tariff_code ?? '';
                            break;
                        }
                    }
                }

                // Qty from invoice detail
                $qty = $detail->f_quantity ?? 1;

                // UnitPrice from invoice detail - unit price * billing cycle
                $unitPrice = ($detail->f_unit_price ?? 0) * ($detail->f_billing_cycle ?? 1);

                // TaxCode - based on SST category from ResellerV2
                $taxCode = $this->getTaxCode($detail, $currencyCode, null, $resellerHandover);

                if ($isFirstRow) {
                    // First row: include all invoice header data + first item
                    $companyName = $resellerHandover->subscriber_name ?? '';

                    $rowData = [
                        'ERIN2600-0000', // DocNo
                        $docDate,
                        $companyName,
                        $debtorCode,
                        $salesAgent,
                        $currencyCode,
                        $currencyRate,
                        $invoiceNo, // UDF_IV_LicenseNumber
                        $salesAdmin,
                        $udfSupport,
                        $billingType,
                        $cancelled,
                        $itemCode,
                        $qty,
                        number_format($unitPrice, 2, '.', ''),
                        $taxCode,
                        $tariffCode,
                    ];

                    $isFirstRow = false;
                } else {
                    // Subsequent rows: only item data (columns M-Q)
                    $rowData = [
                        '', '', '', '', '', '', '', '', '', '', '', '', // Empty columns A-L
                        $itemCode,
                        $qty,
                        number_format($unitPrice, 2, '.', ''),
                        $taxCode,
                        $tariffCode,
                    ];
                }

                $sheet->fromArray([$rowData], null, 'A' . $row);
                $row++;
            }

            Log::info("Processed all {$type} items, final row: " . ($row - 1));

            // Apply yellow highlighting to specific cells in the 2nd row (first data row)
            if ($row > 2) {
                $highlightStyle = [
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFF00']],
                ];
                $sheet->getStyle('A2')->applyFromArray($highlightStyle); // DocNo
                $sheet->getStyle('D2')->applyFromArray($highlightStyle); // DebtorCode
                $sheet->getStyle('H2')->applyFromArray($highlightStyle); // UDF_IV_LicenseNumber
            }

            // Auto-size columns
            foreach (range('A', 'Q') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Save as Excel file
            Log::info('Saving Excel file...');
            $tempFile = tempnam(sys_get_temp_dir(), 'reseller_invoice_data_fd_export_');
            $writer = new Xlsx($spreadsheet);
            $writer->save($tempFile);
            Log::info('Excel file saved to: ' . $tempFile);

            // Create filename
            $resellerName = $resellerHandover->reseller_company_name ?? 'Reseller';
            $subscriberName = $resellerHandover->subscriber_name ?? 'Subscriber';
            $handoverId = $resellerHandover->fd_id ?? str_pad($resellerHandover->id, 3, '0', STR_PAD_LEFT);
            $filename = "{$type}_SALES_DATA_{$handoverId}_" .
                        str_replace([' ', '/', '\\', '&'], '_', $subscriberName) . '_' .
                        date('Y-m-d') . '.xlsx';

            Log::info("About to send FD {$type} Sales Invoice Data Excel file: " . $filename);

            // Return file as download
            return response()->download($tempFile, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ])->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error("FD {$type} Sales Invoice Data export error: " . $e->getMessage());
            Log::error('File: ' . $e->getFile() . ' Line: ' . $e->getLine());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return back()->with('error', "Error exporting {$type} sales invoice data: " . $e->getMessage());
        }
    }

    private function mapSalesAgent($resellerHandover)
    {
        $allowedSalesAgents = [
            'JONATHAN',
            'WIRSON',
            'MUIM',
            'YASMIN',
            'FARHANAH',
            'JOSHUA',
            'AZIZ',
            'BARI',
            'VINCE',
            'JAJA',
            'AFIFAH',
            'SHAHILAH',
            'SHEENA'
        ];

        $salespersonName = '';

        // Try to get salesperson from reseller name
        if ($resellerHandover->reseller_name) {
            $salespersonName = strtoupper(trim($resellerHandover->reseller_name));
            Log::info('Checking reseller handover FD reseller_name: ' . $salespersonName);
        }

        // Check if the salesperson name matches any allowed values (exact match)
        if (in_array($salespersonName, $allowedSalesAgents)) {
            Log::info('Found exact match: ' . $salespersonName);
            return $salespersonName;
        }

        // Try partial matching
        foreach ($allowedSalesAgents as $allowedAgent) {
            if (strpos($salespersonName, $allowedAgent) !== false) {
                Log::info('Found partial match: ' . $allowedAgent . ' in ' . $salespersonName);
                return $allowedAgent;
            }
        }

        Log::warning('Salesperson name "' . $salespersonName . '" not found in allowed list. Using empty string.');
        return '';
    }

    private function getTaxCode($detail, $currencyCode, $product, $resellerHandover = null)
    {
        // If currency is USD, always return NTS
        if ($currencyCode === 'USD') {
            Log::info('Currency is USD - Tax code: NTS');
            return 'NTS';
        }

        // For MYR currency, check SST category from ResellerV2
        if ($currencyCode === 'MYR' && $resellerHandover) {
            // Get SST category from ResellerV2 based on reseller company name
            $sstCategory = \App\Models\ResellerV2::where('company_name', $resellerHandover->reseller_company_name)
                ->value('sst_category');

            if ($sstCategory) {
                // Without SST = ESCB2B-8, With SST = SV-8
                if ($sstCategory === 'Without SST') {
                    Log::info('SST Category: Without SST - Tax code: ESCB2B-8');
                    return 'ESCB2B-8';
                } else {
                    Log::info('SST Category: ' . $sstCategory . ' - Tax code: SV-8');
                    return 'SV-8';
                }
            }

            // Fallback: check GST rate if SST category not found
            $gstRate = $detail->f_gst_rate ?? 0;

            if ($gstRate > 0) {
                Log::info('GST rate is ' . $gstRate . ' - Tax code: SV-8');
                return 'SV-8';
            }

            // Also check product taxable status if available
            if ($product && isset($product->taxable)) {
                $taxCode = $product->taxable ? 'SV-8' : 'ESCB2B-8';
                Log::info('Product taxable status: ' . ($product->taxable ? 'Yes' : 'No') . ' - Tax code: ' . $taxCode);
                return $taxCode;
            }

            Log::info('No SST category found and no GST rate - defaulting to ESCB2B-8');
            return 'ESCB2B-8';
        }

        // Default fallback for other currencies
        Log::info('Currency is ' . $currencyCode . ' - defaulting to NTS');
        return 'NTS';
    }
}
