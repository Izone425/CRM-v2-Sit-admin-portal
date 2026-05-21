<?php

namespace App\Http\Controllers;

use App\Models\ResellerHandoverFe;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class FePurchaseInvoiceExportController extends Controller
{
    public function export($handoverId)
    {
        try {
            $record = ResellerHandoverFe::findOrFail($handoverId);

            // Get amount and currency from crm_invoice_details where ap_document matches f_invoice_no
            $invoiceDetail = DB::connection('frontenddb')
                ->table('crm_invoice_details')
                ->where('f_invoice_no', $record->ap_document)
                ->first(['f_total_amount', 'f_currency']);

            $unitPrice = $invoiceDetail->f_total_amount ?? 0;
            $currencyCode = $invoiceDetail->f_currency ?? 'MYR';

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

            $totalQty = 1;
            $invoiceDate = now();

            // Build description
            $description = $record->subscriber_name . ' (' . ($record->ap_document ?? '') . ')';

            // Prepare data row
            $dataRow = [
                $record->reseller_company_name ?? '',                               // Creditor
                $record->fe_id ?? '',                                                // DocNo
                ($record->timetec_proforma_invoice ?? '') . ' (' . ($record->ap_document ?? '') . ')', // SupplierInvoiceNo
                $invoiceDate->format('d/m/Y'),                                      // DocDate
                $creditorCode,                                                       // CreditorCode
                '',                                                                  // SupplierDONo
                $description,                                                        // Description
                $currencyCode,                                                       // CurrencyCode
                $currencyCode === 'MYR' ? '1' : '',                                 // CurrencyRate
                'TCL-P6501',                                                         // AccNo
                $description,                                                        // DetailDescription
                '',                                                                  // FurtherDescription
                $totalQty,                                                           // Qty
                $unitPrice,                                                          // UnitPrice
                '',                                                                  // TaxCode
                '',                                                                  // TariffCode
                ''                                                                   // Cancelled
            ];

            // Add data to row 2
            $sheet->fromArray([$dataRow], null, 'A2');

            // Apply header styling
            $lastCol = 'Q';
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

            // Highlight CreditorCode (E), CurrencyCode (H), CurrencyRate (I) in yellow
            foreach (['E2', 'H2', 'I2'] as $cell) {
                $sheet->getStyle($cell)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FFFF00'],
                    ],
                ]);
            }

            // Auto-size columns
            foreach (range('A', $lastCol) as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Create temporary file
            $tempFile = tempnam(sys_get_temp_dir(), 'fe_purchase_invoice_');
            $writer = new Xlsx($spreadsheet);
            $writer->save($tempFile);

            // Create filename
            $filename = 'Purchase_Invoice_' . $record->fe_id . '_' . date('Y-m-d') . '.xlsx';

            Log::info('FE Purchase Invoice exported for: ' . $record->fe_id);

            return response()->download($tempFile, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ])->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('FE Purchase Invoice export error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return back()->with('error', 'Error exporting purchase invoice: ' . $e->getMessage());
        }
    }

    public function batchExport(\Illuminate\Http\Request $request)
    {
        try {
            $ids = $request->query('ids');
            if (empty($ids)) {
                return back()->with('error', 'No records selected for export.');
            }

            $handoverIds = explode(',', $ids);
            $records = ResellerHandoverFe::whereIn('id', $handoverIds)
                ->whereNotNull('ap_document')
                ->where('ap_document', '!=', '')
                ->get();

            if ($records->isEmpty()) {
                return back()->with('error', 'No valid records found for export.');
            }

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

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

            $sheet->fromArray([$headers], null, 'A1');

            $rowNumber = 2;
            foreach ($records as $record) {
                $invoiceDetail = DB::connection('frontenddb')
                    ->table('crm_invoice_details')
                    ->where('f_invoice_no', $record->ap_document)
                    ->first(['f_total_amount', 'f_currency']);

                $unitPrice = $invoiceDetail->f_total_amount ?? 0;
                $currencyCode = $invoiceDetail->f_currency ?? 'MYR';

                $creditorCode = \App\Models\ResellerV2::where('company_name', $record->reseller_company_name)
                    ->value('creditor_code') ?? '';

                $description = $record->subscriber_name . ' (' . ($record->ap_document ?? '') . ')';

                $dataRow = [
                    $record->reseller_company_name ?? '',
                    $record->fe_id ?? '',
                    ($record->timetec_proforma_invoice ?? '') . ' (' . ($record->ap_document ?? '') . ')',
                    $request->query('docdate') ? \Carbon\Carbon::parse($request->query('docdate'))->format('d/m/Y') : now()->format('d/m/Y'),
                    $creditorCode,
                    '',
                    $description,
                    $currencyCode,
                    $currencyCode === 'MYR' ? '1' : '',
                    'TCL-P6501',
                    $description,
                    '',
                    1,
                    $unitPrice,
                    '',
                    '',
                    ''
                ];

                $sheet->fromArray([$dataRow], null, 'A' . $rowNumber);

                foreach (['E' . $rowNumber, 'H' . $rowNumber, 'I' . $rowNumber] as $cell) {
                    $sheet->getStyle($cell)->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'FFFF00'],
                        ],
                    ]);
                }

                $rowNumber++;
            }

            $lastCol = 'Q';
            $lastRow = $rowNumber - 1;

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

            $sheet->getStyle('A2:' . $lastCol . $lastRow)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ]);

            foreach (range('A', $lastCol) as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $tempFile = tempnam(sys_get_temp_dir(), 'fe_batch_purchase_invoice_');
            $writer = new Xlsx($spreadsheet);
            $writer->save($tempFile);

            $filename = 'Batch_Purchase_Invoice_' . date('Y-m-d') . '.xlsx';

            Log::info('FE Batch Purchase Invoice exported for ' . $records->count() . ' records');

            return response()->download($tempFile, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ])->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('FE Batch Purchase Invoice export error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return back()->with('error', 'Error exporting batch purchase invoice: ' . $e->getMessage());
        }
    }
}
