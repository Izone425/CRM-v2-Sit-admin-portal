<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ResellerAnalysisExport implements FromArray, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    protected array $data;
    protected string $currency;

    public function __construct(array $data, string $currency)
    {
        $this->data = $data;
        $this->currency = $currency;
    }

    public function array(): array
    {
        $rows = [];
        foreach ($this->data as $index => $reseller) {
            $rows[] = [
                $index + 1,
                ($reseller['has_account'] ?? false) ? 'Yes' : 'No',
                strtoupper($reseller['reseller_name']),
                $reseller['total_end_users'],
                $reseller['debtor_code'] ?? '',
                $reseller['creditor_code'] ?? '',
            ];
        }

        // Total row
        $rows[] = [
            '',
            '',
            'TOTAL',
            array_sum(array_column($this->data, 'total_end_users')),
            '',
            '',
        ];

        return $rows;
    }

    public function headings(): array
    {
        return ['#', 'Account', 'Reseller Name', 'Total Clients', 'Debtor Code', 'Creditor Code'];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = count($this->data) + 2; // +1 for header, +1 for total row

        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
            $lastRow => ['font' => ['bold' => true]],
            'A:F' => ['alignment' => ['horizontal' => 'left']],
        ];
    }

    public function title(): string
    {
        return 'Reseller Analysis (' . $this->currency . ')';
    }
}
