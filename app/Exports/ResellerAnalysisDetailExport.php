<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ResellerAnalysisDetailExport implements FromArray, WithHeadings, WithStyles, WithTitle
{
    protected array $clients;
    protected string $title;

    public function __construct(array $clients, string $title)
    {
        $this->clients = $clients;
        $this->title = $title;
    }

    public function array(): array
    {
        $statusLabels = [
            'new' => 'New',
            'pending_confirmation' => 'Pending Confirmation',
            'pending_payment' => 'Pending Payment',
            'completed_renewal' => 'Completed',
            'completed_reseller_portal' => 'Completed(Reseller Portal)',
            'terminated' => 'Terminated',
            'no_record' => 'No Record',
        ];

        $rows = [];
        foreach ($this->clients as $index => $client) {
            $rows[] = [
                $index + 1,
                $client['company_name'],
                $statusLabels[$client['status']] ?? ucfirst(str_replace('_', ' ', $client['status'])),
                $client['earliest_expiry'],
            ];
        }

        return $rows;
    }

    public function headings(): array
    {
        return ['#', 'Client Name', 'Status', 'Expiry Date'];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
            'A:D' => ['alignment' => ['horizontal' => 'left']],
        ];
    }

    public function title(): string
    {
        return substr($this->title, 0, 31); // Excel sheet name limit
    }
}
