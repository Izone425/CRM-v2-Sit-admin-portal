<?php

namespace App\Http\Controllers;

use App\Models\FinanceInvoice;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Illuminate\Support\Facades\Log;

class FinanceInvoicePurchaseExportController extends Controller
{
    private function getHeaders(): array
    {
        return [
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
    }

    private function buildDataRow(FinanceInvoice $record, $invoiceDate): array
    {
        $creditorCode = \App\Models\ResellerV2::where('company_name', $record->reseller_name)
            ->value('creditor_code') ?? '';

        $description = $record->subscriber_name . ' (' . $record->autocount_invoice_number . ')';
        $currency = $record->currency ?? 'MYR';
        $currencyRate = $record->currency_rate ?? '1';
        $unitPrice = $record->reseller_commission_amount ?? 0;

        return [
            $record->reseller_name ?? '',                                   // Creditor
            $record->formatted_id ?? '',                                // DocNo (FC ID)
            $record->timetec_invoice_number . ' (' . $record->autocount_invoice_number . ')' ?? '',                      // SupplierInvoiceNo
            $invoiceDate->format('d/m/Y'),                              // DocDate
            $creditorCode,                                              // CreditorCode
            '',                                                         // SupplierDONo (blank)
            $description,                                               // Description
            $currency,                                                  // CurrencyCode
            $currencyRate,                                              // CurrencyRate
            'TCL-P6501',                                                // AccNo
            $description,                                               // DetailDescription
            '',                                                         // FurtherDescription
            1,                                                          // Qty
            $unitPrice,                                                 // UnitPrice
            '',                                                         // TaxCode (blank)
            '',                                                         // TariffCode (blank)
            ''                                                          // Cancelled (blank)
        ];
    }

    private function applyHeaderStyle($sheet, string $lastCol): void
    {
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
    }

    private function applyDataRowStyle($sheet, string $lastCol, int $row): void
    {
        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        // Highlight CreditorCode (E), CurrencyCode (H), CurrencyRate (I)
        $sheet->getStyle("E{$row}")->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFFF00'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        $sheet->getStyle("H{$row}:I{$row}")->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFFF00'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);
    }

    public function export($financeInvoiceId)
    {
        try {
            $record = FinanceInvoice::with('resellerHandover')->findOrFail($financeInvoiceId);

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $lastCol = 'Q';

            $sheet->fromArray([$this->getHeaders()], null, 'A1');
            $sheet->fromArray([$this->buildDataRow($record, now())], null, 'A2');

            $this->applyHeaderStyle($sheet, $lastCol);
            $this->applyDataRowStyle($sheet, $lastCol, 2);

            foreach (range('A', $lastCol) as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $tempFile = tempnam(sys_get_temp_dir(), 'finance_purchase_invoice_');
            $writer = new Xlsx($spreadsheet);
            $writer->save($tempFile);

            $filename = 'Purchase_Invoice_' . ($record->formatted_id ?? $record->id) . '_' . date('Y-m-d') . '.xlsx';

            Log::info('Finance Purchase Invoice exported for: ' . ($record->formatted_id ?? $record->id));

            return response()->download($tempFile, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ])->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('Finance Purchase Invoice export error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return back()->with('error', 'Error exporting finance purchase invoice: ' . $e->getMessage());
        }
    }

    public function exportBatch(Request $request)
    {
        try {
            $ids = explode(',', $request->query('ids', ''));
            $ids = array_filter($ids);

            if (empty($ids)) {
                return back()->with('error', 'No records selected for export.');
            }

            $records = FinanceInvoice::with('resellerHandover')
                ->whereIn('id', $ids)
                ->get();

            if ($records->isEmpty()) {
                return back()->with('error', 'No records found for export.');
            }

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $lastCol = 'Q';

            $sheet->fromArray([$this->getHeaders()], null, 'A1');

            $invoiceDate = now();
            $rowNumber = 2;

            foreach ($records as $record) {
                $sheet->fromArray([$this->buildDataRow($record, $invoiceDate)], null, 'A' . $rowNumber);
                $this->applyDataRowStyle($sheet, $lastCol, $rowNumber);
                $rowNumber++;
            }

            $this->applyHeaderStyle($sheet, $lastCol);

            foreach (range('A', $lastCol) as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $tempFile = tempnam(sys_get_temp_dir(), 'finance_purchase_invoice_batch_');
            $writer = new Xlsx($spreadsheet);
            $writer->save($tempFile);

            $filename = 'Purchase_Invoice_Batch_' . date('Y-m-d') . '.xlsx';

            Log::info('Finance Purchase Invoice batch exported', ['count' => $records->count(), 'ids' => $ids]);

            return response()->download($tempFile, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('Finance Purchase Invoice batch export error: ' . $e->getMessage());
            return back()->with('error', 'Error exporting purchase invoices: ' . $e->getMessage());
        }
    }
}
