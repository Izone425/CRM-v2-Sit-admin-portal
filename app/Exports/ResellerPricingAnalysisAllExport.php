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

class ResellerPricingAnalysisAllExport implements FromArray, WithStyles, WithTitle, WithColumnWidths, WithEvents
{
    /** @var array<string, array> reseller_name => clients[] */
    protected array $sheetsData;
    protected string $currency;

    public function __construct(array $sheetsData, string $currency = 'MYR')
    {
        $this->sheetsData = $sheetsData;
        $this->currency = $currency;
    }

    public function array(): array
    {
        $modules = ['TA', 'TL', 'TC', 'TP'];

        $rows = [
            // Row 1: group labels — merged G1:J1 / L1:O1 in AfterSheet.
            ['', '', '', '', '', '', 'Gross Pricing', '', '', '', '', 'Nett Pricing', '', '', ''],
            // Row 2: column headers
            [
                'No',
                'Reseller Name',
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
        foreach ($this->sheetsData as $resellerName => $clients) {
            foreach ($clients as $client) {
                // Company header row — only No + Reseller Name + Company Name populated.
                $rows[] = [
                    $index++,
                    $resellerName,
                    $client['company_name'] ?? '',
                    '', '', '', '', '', '', '', '', '', '', '', '',
                ];

                // Invoice rows — invoice number sits in the Company Name column (second tier).
                foreach ($client['invoices'] ?? [] as $invoice) {
                    $row = [
                        '',
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
            'B' => 28,  // Reseller Name
            'C' => 35,  // Company Name
            'D' => 14,  // Expiry Date
            'E' => 18,  // Days Until Expiry
            'F' => 8,   // gap
            'G' => 8,   // TA
            'H' => 8,   // TL
            'I' => 8,   // TC
            'J' => 8,   // TP
            'K' => 18,  // Reseller Comm %
            'L' => 8,   // TA
            'M' => 8,   // TL
            'N' => 8,   // TC
            'O' => 8,   // TP
        ];
    }

    public function registerEvents(): array
    {
        $currencyPrefix = $this->currency === 'USD' ? 'USD' : 'RM';
        $numberFormat   = '"' . $currencyPrefix . '"#,##0.00';

        return [
            AfterSheet::class => function (AfterSheet $event) use ($numberFormat) {
                $sheet = $event->sheet->getDelegate();
                $sheet->mergeCells('G1:J1');
                $sheet->mergeCells('L1:O1');

                $lastRow = $sheet->getHighestRow();
                if ($lastRow >= 3) {
                    $sheet->getStyle("G3:J{$lastRow}")->getNumberFormat()->setFormatCode($numberFormat);
                    $sheet->getStyle("L3:O{$lastRow}")->getNumberFormat()->setFormatCode($numberFormat);
                    $sheet->getStyle("K3:K{$lastRow}")->getNumberFormat()->setFormatCode('0.00"%"');
                }
            },
        ];
    }

    public function title(): string
    {
        return 'Pricing Summary';
    }
}
