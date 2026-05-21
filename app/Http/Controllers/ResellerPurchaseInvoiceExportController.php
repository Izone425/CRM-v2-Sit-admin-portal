<?php

namespace App\Http\Controllers;

use App\Models\ResellerHandover;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Illuminate\Support\Facades\Log;

class ResellerPurchaseInvoiceExportController extends Controller
{
    public function export($handoverId)
    {
        try {
            // Get the reseller handover record with finance invoice
            $record = ResellerHandover::with('financeInvoice')->findOrFail($handoverId);

            // Get creditor code from reseller_v2 if exists
            $creditorCode = \App\Models\ResellerV2::where('company_name', $record->reseller_company_name)
                ->value('creditor_code') ?? '';

            // Create Excel spreadsheet
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Define headers
            $headers = [
                'Creditor',
                'DocNo',
                'SupplierInvoiceNo',
                'DocDate',
                'CreditorCode',
                'SupplierDONo',
                'Description',
                'CurrencyCode',
                'CurrencyRate',
                'AccNo',
                'DetailDescription',
                'FurtherDescription',
                'Qty',
                'UnitPrice',
                'TaxCode',
                'TariffCode',
                'Cancelled'
            ];

            // Add headers to row 1
            $sheet->fromArray([$headers], null, 'A1');

            // Calculate total quantity from handover record
            $totalQty = 1;
            $unitPrice = $record->financeInvoice?->reseller_commission_amount ?? 0;

            // Use today's date for DocDate
            $invoiceDate = now();

            // Build description with reseller company name and subscriber details
            $description = $record->subscriber_name . ' (' . $record->autocount_invoice_number . ')';

            // Prepare data row
            $dataRow = [
                $record->reseller_company_name ?? '',                           // Creditor
                $record->financeInvoice?->formatted_id ?? '',               // DocNo (FC ID)
                $record->timetec_proforma_invoice . ' (' . $record->autocount_invoice_number . ')' ?? '',                    // SupplierInvoiceNo
                $invoiceDate ? $invoiceDate->format('d/m/Y') : '',         // DocDate
                $creditorCode,                                               // CreditorCode
                '',                                                          // SupplierDONo (blank)
                $description,                                                // Description
                'MYR',                                                       // CurrencyCode
                '1',                                                         // CurrencyRate
                'TCL-P6501',                                                 // AccNo
                $description,                                                // DetailDescription
                '',                             // FurtherDescription (reseller remark)
                $totalQty,                                                   // Qty
                $unitPrice,                                                  // UnitPrice
                '',                                                          // TaxCode (blank)
                '',                                                          // TariffCode (blank)
                ''                                                           // Cancelled (blank)
            ];

            // Add data to row 2
            $sheet->fromArray([$dataRow], null, 'A2');

            // Apply header styling
            $lastCol = 'Q'; // Column Q is the 17th column (Cancelled)
            $sheet->getStyle('A1:' . $lastCol . '1')->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'd4d0c9'],
                ],
                'font' => [
                    'bold' => true,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ]);

            // Apply data row styling
            $sheet->getStyle('A2:' . $lastCol . '2')->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ]);

            // Auto-size columns
            foreach (range('A', $lastCol) as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Create temporary file
            $tempFile = tempnam(sys_get_temp_dir(), 'purchase_invoice_');
            $writer = new Xlsx($spreadsheet);
            $writer->save($tempFile);

            // Create filename
            $filename = 'Purchase_Invoice_' . ($record->financeInvoice?->formatted_id ?? $record->id) . '_' . date('Y-m-d') . '.xlsx';

            Log::info('Purchase Invoice exported for: ' . ($record->financeInvoice?->formatted_id ?? $record->id));

            // Return file as download
            return response()->download($tempFile, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ])->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('Purchase Invoice export error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return back()->with('error', 'Error exporting purchase invoice: ' . $e->getMessage());
        }
    }
}
