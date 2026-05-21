<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ResellerPricingAnalysisExport implements FromArray, WithStyles, WithTitle, WithColumnWidths, WithEvents
{
    protected array $clients;
    protected string $title;
    protected string $currency;

    public function __construct(array $clients, string $title, string $currency = 'MYR')
    {
        $this->clients = $clients;
        $this->title = $title;
        $this->currency = $currency;
    }

    public function array(): array
    {
        $modules = ['TA', 'TL', 'TC', 'TP'];

        // Row 1: group labels — "Ori Pricing" merged F1:I1, "Profit Pricing" merged K1:N1 via AfterSheet.
        $rows = [
            ['', '', '', '', '', 'Gross Pricing', '', '', '', '', 'Nett Pricing', '', '', ''],
            // Row 2: column headers
            [
                'No',
                'Company Name',
                'Expiry Date',
                'Days Until Expiry',
                '',
                'TA', 'TL', 'TC', 'TP',
                'Reseller Comm %',
                'TA', 'TL', 'TC', 'TP',
            ],
        ];

        $index = 1;
        foreach ($this->clients as $client) {
            // Company header row — only No + Company Name populated.
            $rows[] = [
                $index++,
                $client['company_name'] ?? '',
                '', '', '', '', '', '', '', '', '', '', '', '',
            ];

            // Invoice rows — no No, invoice number sits in the Company Name column (second tier).
            foreach ($client['invoices'] ?? [] as $invoice) {
                $row = [
                    '',
                    $invoice['invoice_no'] ?? '',
                    $invoice['expiry_date'] ?? null,
                    $invoice['days_until_expiry'] ?? null,
                    '',
                ];

                foreach ($modules as $mod) {
                    $row[] = $invoice['unit_prices'][$mod] ?? null;
                }

                $row[] = $invoice['commission_rate'] ?? null;

                foreach ($modules as $mod) {
                    $row[] = $invoice['after_commission'][$mod] ?? null;
                }

                $rows[] = $row;
            }
        }

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            2 => ['font' => ['bold' => true]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,   // No
            'B' => 35,  // Company Name
            'C' => 14,  // Expiry Date
            'D' => 18,  // Days Until Expiry
            'E' => 8,   // gap
            'F' => 8,   // TA
            'G' => 8,   // TL
            'H' => 8,   // TC
            'I' => 8,   // TP
            'J' => 18,  // Reseller Comm %
            'K' => 8,   // TA
            'L' => 8,   // TL
            'M' => 8,   // TC
            'N' => 8,   // TP
        ];
    }

    public function registerEvents(): array
    {
        $currencyPrefix = $this->currency === 'USD' ? 'USD' : 'RM';
        $numberFormat   = '"' . $currencyPrefix . '"#,##0.00';

        return [
            AfterSheet::class => function (AfterSheet $event) use ($numberFormat) {
                $sheet = $event->sheet->getDelegate();
                $sheet->mergeCells('F1:I1');
                $sheet->mergeCells('K1:N1');

                $lastRow = $sheet->getHighestRow();
                if ($lastRow >= 3) {
                    // Currency format on both pricing groups.
                    $sheet->getStyle("F3:I{$lastRow}")->getNumberFormat()->setFormatCode($numberFormat);
                    $sheet->getStyle("K3:N{$lastRow}")->getNumberFormat()->setFormatCode($numberFormat);
                    // Percentage suffix for the per-invoice commission column.
                    $sheet->getStyle("J3:J{$lastRow}")->getNumberFormat()->setFormatCode('0.00"%"');
                }
            },
        ];
    }

    public function title(): string
    {
        return substr($this->title, 0, 31);
    }
}
