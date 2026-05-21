<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RenewalProcessDataExport implements FromArray, WithHeadings, WithStyles, WithTitle
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
        foreach ($this->data as $index => $row) {
            $rows[] = [
                $index + 1,
                $row['company_name'],
                $row['expired_license'],
                $row['renewal_status'],
                $row['amount'],
                $row['next_follow_up_date'],
                $row['category'],
            ];
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            '#',
            'Company Name',
            'Expired License',
            'Renewal Status',
            'Amount',
            'Next Follow Up Date',
            'Category',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = count($this->data) + 1;

        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
            'A:G' => ['alignment' => ['horizontal' => 'left']],
        ];
    }

    public function title(): string
    {
        return 'Renewal Process Data (' . $this->currency . ')';
    }
}
