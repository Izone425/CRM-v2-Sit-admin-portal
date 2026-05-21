<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ResellerCommissionPaymentExport implements FromArray, WithHeadings, WithStyles, WithTitle, WithColumnWidths
{
    protected Collection $records;

    public function __construct(Collection $records)
    {
        $this->records = $records;
    }

    public function array(): array
    {
        $rows = [];
        $index = 1;

        foreach ($this->records as $record) {
            $ttStatusLabel = match ((int) ($record->tt_invoice_status ?? -1)) {
                0       => 'Paid',
                1       => 'Unpaid',
                default => '-',
            };

            $rows[] = [
                $index++,
                $record->f_created_time
                    ? \Carbon\Carbon::parse($record->f_created_time)->format('d M Y')
                    : '',
                $record->f_invoice_no ?? '',
                $record->tt_invoice_no ?? '',
                $record->autocount_inv_no ?? '',
                'Full Payment',
                strtoupper((string) ($record->reseller_name ?? '')),
                strtoupper((string) ($record->subscriber_name ?? '')),
                $record->f_currency ?? 'MYR',
                (float) ($record->f_total_amount ?? 0),
                $ttStatusLabel,
            ];
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'No',
            'Date',
            'AP Number',
            'TT Number',
            'Invoice',
            'Payment Status',
            'Reseller Name',
            'Subscriber Name',
            'Currency',
            'Amount',
            'TT Status',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 14,
            'C' => 18,
            'D' => 18,
            'E' => 18,
            'F' => 16,
            'G' => 35,
            'H' => 35,
            'I' => 10,
            'J' => 14,
            'K' => 12,
        ];
    }

    public function title(): string
    {
        return 'Commission Payment';
    }
}
