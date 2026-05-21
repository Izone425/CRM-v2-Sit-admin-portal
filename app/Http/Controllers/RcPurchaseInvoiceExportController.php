<?php

namespace App\Http\Controllers;

use App\Models\ResellerCommissionHandover;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class RcPurchaseInvoiceExportController extends Controller
{
    public function export($handoverId, \Illuminate\Http\Request $request)
    {
        try {
            $record = ResellerCommissionHandover::findOrFail($handoverId);

            // Get amount from crm_invoice_details via ap_invoice_no
            $invoiceDetail = DB::connection('frontenddb')
                ->table('crm_invoice_details')
                ->where('f_invoice_no', $record->ap_invoice_no)
                ->first(['f_total_amount', 'f_currency']);

            $unitPrice = $invoiceDetail->f_total_amount ?? $record->amount;
            $currencyCode = $invoiceDetail->f_currency ?? $record->currency ?? 'MYR';

            // Get creditor code from reseller_v2
            $creditorCode = \App\Models\ResellerV2::where('company_name', $record->reseller_name)
                ->value('creditor_code') ?? '';

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $headers = [
                'Creditor', 'DocNo', 'SupplierInvoiceNo', 'DocDate', 'CreditorCode',
                'SupplierDONo', 'Description', 'CurrencyCode', 'CurrencyRate',
                'AccNo', 'DetailDescription', 'FurtherDescription',
                'Qty', 'UnitPrice', 'TaxCode', 'TariffCode', 'Cancelled'
            ];

            $sheet->fromArray([$headers], null, 'A1');

            $description = $record->subscriber_name . ' (' . ($record->autocount_inv_no ?? '') . ')';

            $dataRow = [
                $record->reseller_name ?? '',                                                   // Creditor
                $record->fh_id ?? '',                                                            // DocNo
                ($record->tt_invoice_no ?? '') . ' (' . ($record->ap_invoice_no ?? '') . ')', // SupplierInvoiceNo
                $request->query('docdate') ? \Carbon\Carbon::parse($request->query('docdate'))->format('d/m/Y') : now()->format('d/m/Y'), // DocDate
                $creditorCode,                                                                   // CreditorCode
                '',                                                                              // SupplierDONo
                $description,                                                                    // Description
                $currencyCode,                                                                   // CurrencyCode
                $currencyCode === 'MYR' ? '1' : '',                                             // CurrencyRate
                'TCL-P6501',                                                                     // AccNo
                $description,                                                                    // DetailDescription
                '',                                                                              // FurtherDescription
                1,                                                                               // Qty
                $unitPrice,                                                                      // UnitPrice
                '',                                                                              // TaxCode
                '',                                                                              // TariffCode
                ''                                                                               // Cancelled
            ];

            $sheet->fromArray([$dataRow], null, 'A2');

            $lastCol = 'Q';
            $sheet->getStyle('A1:' . $lastCol . '1')->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'd4d0c9']],
                'font' => ['bold' => true],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
            ]);

            $sheet->getStyle('A2:' . $lastCol . '2')->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
            ]);

            foreach (['E2', 'H2', 'I2'] as $cell) {
                $sheet->getStyle($cell)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFF00']],
                ]);
            }

            foreach (range('A', $lastCol) as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $tempFile = tempnam(sys_get_temp_dir(), 'rc_purchase_invoice_');
            $writer = new Xlsx($spreadsheet);
            $writer->save($tempFile);

            $filename = 'Purchase_Invoice_' . $record->fh_id . '_' . date('Y-m-d') . '.xlsx';

            Log::info('RC Purchase Invoice exported for: ' . $record->fh_id);

            return response()->download($tempFile, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('RC Purchase Invoice export error: ' . $e->getMessage());
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
            $records = ResellerCommissionHandover::whereIn('id', $handoverIds)
                ->whereNotNull('ap_invoice_no')
                ->where('ap_invoice_no', '!=', '')
                ->get();

            if ($records->isEmpty()) {
                return back()->with('error', 'No valid records found for export.');
            }

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $headers = [
                'Creditor', 'DocNo', 'SupplierInvoiceNo', 'DocDate', 'CreditorCode',
                'SupplierDONo', 'Description', 'CurrencyCode', 'CurrencyRate',
                'AccNo', 'DetailDescription', 'FurtherDescription',
                'Qty', 'UnitPrice', 'TaxCode', 'TariffCode', 'Cancelled'
            ];

            $sheet->fromArray([$headers], null, 'A1');

            $rowNumber = 2;
            foreach ($records as $record) {
                $invoiceDetail = DB::connection('frontenddb')
                    ->table('crm_invoice_details')
                    ->where('f_invoice_no', $record->ap_invoice_no)
                    ->first(['f_total_amount', 'f_currency']);

                $unitPrice = $invoiceDetail->f_total_amount ?? $record->amount;
                $currencyCode = $invoiceDetail->f_currency ?? $record->currency ?? 'MYR';

                $creditorCode = \App\Models\ResellerV2::where('company_name', $record->reseller_name)
                    ->value('creditor_code') ?? '';

                $description = $record->subscriber_name . ' (' . ($record->autocount_inv_no ?? '') . ')';

                $dataRow = [
                    $record->reseller_name ?? '',
                    $record->fh_id ?? '',
                    ($record->tt_invoice_no ?? '') . ' (' . ($record->ap_invoice_no ?? '') . ')',
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
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFF00']],
                    ]);
                }

                $rowNumber++;
            }

            $lastCol = 'Q';
            $lastRow = $rowNumber - 1;

            $sheet->getStyle('A1:' . $lastCol . '1')->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'd4d0c9']],
                'font' => ['bold' => true],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
            ]);

            $sheet->getStyle('A2:' . $lastCol . $lastRow)->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
            ]);

            foreach (range('A', $lastCol) as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $tempFile = tempnam(sys_get_temp_dir(), 'rc_batch_purchase_invoice_');
            $writer = new Xlsx($spreadsheet);
            $writer->save($tempFile);

            $filename = 'Batch_Purchase_Invoice_RC_' . date('Y-m-d') . '.xlsx';

            Log::info('RC Batch Purchase Invoice exported for ' . $records->count() . ' records');

            return response()->download($tempFile, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('RC Batch Purchase Invoice export error: ' . $e->getMessage());
            return back()->with('error', 'Error exporting purchase invoice: ' . $e->getMessage());
        }
    }
}
