<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ResellerPricingSummaryExport implements WithMultipleSheets
{
    /** @var array<string, array> reseller_name => $clients */
    protected array $sheetsData;
    protected string $currency;

    public function __construct(array $sheetsData, string $currency = 'MYR')
    {
        $this->sheetsData = $sheetsData;
        $this->currency = $currency;
    }

    public function sheets(): array
    {
        $sheets = [];
        foreach ($this->sheetsData as $resellerName => $clients) {
            $sheets[] = new ResellerPricingAnalysisExport($clients, $resellerName, $this->currency);
        }
        return $sheets;
    }
}
