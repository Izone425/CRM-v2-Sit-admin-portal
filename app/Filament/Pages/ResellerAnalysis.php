<?php

namespace App\Filament\Pages;

use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ResellerAnalysis extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = 'Analysis';
    protected static string $view = 'filament.pages.reseller-analysis';
    protected static ?string $navigationLabel = 'Reseller Analysis';
    protected static ?int $navigationSort = 3;
    protected static ?string $title = '';

    public $showDrawer = false;
    public $drawerTitle = '';
    public $drawerClients = [];
    public string $drawerCurrency = 'MYR';
    public $activeTab = 'MYR';

    // Cached data per currency
    public $myrData = [];
    public $usdData = [];

    // Summary aggregates per currency — category breakdown + HR module penetration
    // for the top-of-tab card row (mirrors Termination Analysis's cards 2–6).
    public $myrSummary = [];
    public $usdSummary = [];

    // Year-month groups currently expanded in the reseller list (shared between MYR + USD tabs).
    public array $expandedYearMonths = [];

    // Reseller rows currently expanded inline (per-tab — a reseller can appear in both currencies).
    public array $expandedResellersMyr = [];
    public array $expandedResellersUsd = [];

    // Generate Forecast Cost modal — mirrors Termination Analysis's centered modal.
    public bool $showForecastModal = false;
    public array $forecastData = [];

    // Date range selector — mirrors Termination Analysis / Renewal Process Data tabs.
    public string $filterMode = 'all';
    public string $selectedYear = '';
    public ?int $selectedMonth = null;
    public string $selectedMonthYear = '';
    public ?string $startDate = null;
    public ?string $endDate = null;
    public array $availableYears = [];

    // Reseller Commission tab — bucketed by max f_rate per reseller, per currency.
    public $commissionData = [];

    // Reseller Commission (PI) — TT-prefixed ac_invoice rows grouped by reseller, per currency.
    public $commissionPiData = [];

    // Gross Price (Above RM3 / Above USD1) — TT-prefixed PIs whose any module unit_price exceeds the currency threshold.
    public $grossPriceAboveData = [];

    // Nett Price (Below RM1 / Below USD0.5) — TT-prefixed PIs whose any module nett price (after commission) is below threshold.
    public $nettPriceBelowData = [];

    // Reseller IDs that should always appear in MYR instead of USD
    protected static array $myrOverrideResellerIds = [
        '0000001117',
    ];

    // Excluded products (same as AdminRenewalProcessDataMyr)
    protected static array $excludedProducts = [
        'TimeTec VMS Corporate (1 Floor License)',
        'TimeTec VMS SME (1 Location License)',
        'TimeTec Patrol (1 Checkpoint License)',
        'TimeTec Patrol (10 Checkpoint License)',
        'Other',
        'TimeTec Profile (10 User License)',
    ];

    public function mount(): void
    {
        $this->selectedYear = (string) now()->year;
        $this->selectedMonth = now()->month;
        $this->selectedMonthYear = now()->format('Y-m');
        $current = now()->year;
        $this->availableYears = range($current - 3, $current + 3);

        $this->myrData = $this->getResellerData('MYR');
        $this->usdData = $this->getResellerData('USD');
        $this->myrSummary = $this->getCurrencySummary('MYR');
        $this->usdSummary = $this->getCurrencySummary('USD');
        $this->expandedYearMonths = $this->collectAllGroupKeys();
        $this->commissionData = [
            'MYR' => $this->getResellerCommissionData('MYR'),
            'USD' => $this->getResellerCommissionData('USD'),
        ];
        $this->commissionPiData = [
            'MYR' => $this->getResellerCommissionPiData('MYR'),
            'USD' => $this->getResellerCommissionPiData('USD'),
        ];
        $this->grossPriceAboveData = [
            'MYR' => $this->getGrossPriceAboveThresholdData('MYR'),
            'USD' => $this->getGrossPriceAboveThresholdData('USD'),
        ];
        $this->nettPriceBelowData = [
            'MYR' => $this->getNettPriceBelowThresholdData('MYR'),
            'USD' => $this->getNettPriceBelowThresholdData('USD'),
        ];
    }

    /**
     * Find TT-prefixed PIs whose any module's NETT price (per-user × (1 - commission %))
     * is below the currency threshold (MYR < 1, USD < 0.5).
     */
    public function getNettPriceBelowThresholdData(string $currency = 'MYR'): array
    {
        $threshold = $currency === 'MYR' ? 1.0 : 0.5;
        $modulePatterns = [
            'TA' => 'TimeTec TA',
            'TL' => 'TimeTec Leave',
            'TC' => 'TimeTec Claim',
            'TP' => 'TimeTec Payroll',
        ];

        $detailRows = DB::connection('frontenddb')->table('crm_invoice_details')
            ->where('f_invoice_no', 'LIKE', 'TT%')
            ->where('f_currency', $currency)
            ->where('f_unit_price', '>', 0) // skip free/zero-price line items
            ->where(function ($q) use ($modulePatterns) {
                foreach ($modulePatterns as $pattern) {
                    $q->orWhere('f_name', 'LIKE', '%' . $pattern . '%');
                }
            })
            ->get(['f_id', 'f_invoice_no', 'f_company_id', 'f_name', 'f_unit_price']);

        if ($detailRows->isEmpty()) {
            return ['rows' => [], 'byReseller' => []];
        }

        // Restrict to PIs whose linked license is still active (f_expiry_date >= today).
        $today = Carbon::now()->format('Y-m-d');
        $candidateInvoiceNos = $detailRows->pluck('f_invoice_no')->filter()->unique()->values()->all();
        $activeInvoiceNos = empty($candidateInvoiceNos) ? [] : DB::connection('frontenddb')
            ->table('crm_expiring_license')
            ->whereIn('f_invoice_no', $candidateInvoiceNos)
            ->where('f_expiry_date', '>=', $today)
            ->distinct()
            ->pluck('f_invoice_no')
            ->toArray();
        $activeInvoiceNoSet = array_flip($activeInvoiceNos);
        $detailRows = $detailRows->filter(fn($r) => isset($activeInvoiceNoSet[$r->f_invoice_no]))->values();
        if ($detailRows->isEmpty()) {
            return ['rows' => [], 'byReseller' => []];
        }

        // Reseller lookup: company_id -> reseller_name + reseller_id + fallback f_rate.
        $companyIds = $detailRows->pluck('f_company_id')->filter()->unique()->values()->all();
        $resellerLinkRows = DB::connection('frontenddb')->table('crm_reseller_link')
            ->whereIn('f_id', $companyIds)
            ->whereNotNull('reseller_name')
            ->where('reseller_name', '!=', '')
            ->get(['f_id', 'reseller_name', 'reseller_id', 'f_rate']);
        $resellerByCompany = [];
        $fallbackRateByCompany = [];
        foreach ($resellerLinkRows as $r) {
            $resellerByCompany[$r->f_id] ??= ['name' => $r->reseller_name, 'reseller_id' => $r->reseller_id];
            $fallbackRateByCompany[$r->f_id] ??= (float) ($r->f_rate ?? 0);
        }

        // Subscriber lookup.
        $companyLookup = DB::connection('frontenddb')->table('crm_company_listing')
            ->whereIn('f_company_id', $companyIds)
            ->pluck('f_company_name', 'f_company_id')
            ->toArray();

        // Commission rate per invoice from ac_invoice.f_discount_dealer.
        $invoiceNos = $detailRows->pluck('f_invoice_no')->filter()->unique()->values()->all();
        $invoiceCommissionLookup = DB::connection('frontenddb')->table('ac_invoice')
            ->whereIn('f_invoice_no', $invoiceNos)
            ->pluck('f_discount_dealer', 'f_invoice_no')
            ->toArray();

        $invoiceMap = [];
        foreach ($detailRows as $row) {
            $reseller = $resellerByCompany[$row->f_company_id] ?? null;
            if (!$reseller) continue;

            $moduleKey = null;
            foreach ($modulePatterns as $mod => $pattern) {
                if (stripos($row->f_name, $pattern) !== false) { $moduleKey = $mod; break; }
            }
            if (!$moduleKey) continue;

            $perUserGross = $this->perUserPrice($row->f_name, (float) $row->f_unit_price);

            $rate = $invoiceCommissionLookup[$row->f_invoice_no] ?? null;
            if ($rate === null) {
                $rate = $fallbackRateByCompany[$row->f_company_id] ?? 0;
            }
            $nett = $perUserGross * (1 - ((float) $rate) / 100);

            if ($nett >= $threshold) continue; // keep only below

            $invoiceNo = $row->f_invoice_no;
            if (!isset($invoiceMap[$invoiceNo])) {
                $invoiceMap[$invoiceNo] = [
                    'invoice_no' => $invoiceNo,
                    'reseller_id' => $reseller['reseller_id'] ?? null,
                    'reseller_name' => strtoupper((string) $reseller['name']),
                    'company_id' => $row->f_company_id,
                    'subscriber_name' => strtoupper((string) ($companyLookup[$row->f_company_id] ?? '-')),
                    'currency' => $currency,
                    'commission_rate' => (float) $rate,
                    'prices' => ['TA' => null, 'TL' => null, 'TC' => null, 'TP' => null],
                    'min_price' => PHP_FLOAT_MAX,
                    'detail_fid' => $row->f_id,
                ];
            }

            // Keep the lowest nett seen per module on the same invoice.
            if ($invoiceMap[$invoiceNo]['prices'][$moduleKey] === null || $nett < $invoiceMap[$invoiceNo]['prices'][$moduleKey]) {
                $invoiceMap[$invoiceNo]['prices'][$moduleKey] = round($nett, 4);
            }
            if ($nett < $invoiceMap[$invoiceNo]['min_price']) {
                $invoiceMap[$invoiceNo]['min_price'] = round($nett, 4);
            }
        }

        // MYR-override exclusion for USD tab.
        $myrOverrideSet = array_flip(self::$myrOverrideResellerIds);
        if ($currency === 'USD' && !empty($myrOverrideSet)) {
            $invoiceMap = array_filter($invoiceMap, fn($r) => !isset($myrOverrideSet[$r['reseller_id']]));
        }

        // Build external_url.
        foreach ($invoiceMap as &$row) {
            $encrypted = openssl_encrypt($row['detail_fid'], 'AES-128-ECB', 'Epicamera@99');
            $row['external_url'] = $encrypted !== false
                ? 'https://www.timeteccloud.com/paypal_reseller_invoice?iIn=' . base64_encode($encrypted)
                : null;
            unset($row['detail_fid']);
        }
        unset($row);

        $rows = array_values($invoiceMap);
        usort($rows, fn($a, $b) => strcmp($a['reseller_name'], $b['reseller_name']) ?: ($a['min_price'] <=> $b['min_price']));

        $byReseller = [];
        foreach ($rows as $r) {
            $byReseller[$r['reseller_name']] = ($byReseller[$r['reseller_name']] ?? 0) + 1;
        }
        arsort($byReseller);

        return ['rows' => $rows, 'byReseller' => $byReseller, 'threshold' => $threshold];
    }

    /**
     * Find TT-prefixed PIs whose any module's gross unit_price exceeds the currency threshold
     * (MYR > 3, USD > 1). Each row carries the reseller_name + subscriber_name + per-module
     * unit prices (only modules that exceeded the threshold are populated). Grouped by reseller.
     */
    public function getGrossPriceAboveThresholdData(string $currency = 'MYR'): array
    {
        $threshold = $currency === 'MYR' ? 3.0 : 1.0;
        $modulePatterns = [
            'TA' => 'TimeTec TA',
            'TL' => 'TimeTec Leave',
            'TC' => 'TimeTec Claim',
            'TP' => 'TimeTec Payroll',
        ];

        // 1. Pull every TT-prefixed invoice-detail line at the matching currency where unit_price > threshold,
        //    restricted to the four billable modules.
        $query = DB::connection('frontenddb')->table('crm_invoice_details')
            ->where('f_invoice_no', 'LIKE', 'TT%')
            ->where('f_currency', $currency)
            ->where('f_unit_price', '>', $threshold)
            ->where(function ($q) use ($modulePatterns) {
                foreach ($modulePatterns as $pattern) {
                    $q->orWhere('f_name', 'LIKE', '%' . $pattern . '%');
                }
            });

        $detailRows = $query->get(['f_id', 'f_invoice_no', 'f_company_id', 'f_name', 'f_unit_price']);
        if ($detailRows->isEmpty()) {
            return ['rows' => [], 'byReseller' => []];
        }

        // Restrict to PIs whose linked license is still active (f_expiry_date >= today).
        $today = Carbon::now()->format('Y-m-d');
        $candidateInvoiceNos = $detailRows->pluck('f_invoice_no')->filter()->unique()->values()->all();
        $activeInvoiceNos = empty($candidateInvoiceNos) ? [] : DB::connection('frontenddb')
            ->table('crm_expiring_license')
            ->whereIn('f_invoice_no', $candidateInvoiceNos)
            ->where('f_expiry_date', '>=', $today)
            ->distinct()
            ->pluck('f_invoice_no')
            ->toArray();
        $activeInvoiceNoSet = array_flip($activeInvoiceNos);
        $detailRows = $detailRows->filter(fn($r) => isset($activeInvoiceNoSet[$r->f_invoice_no]))->values();
        if ($detailRows->isEmpty()) {
            return ['rows' => [], 'byReseller' => []];
        }

        // 2. Reseller lookup: company_id -> reseller_name (and reseller_id for currency override).
        $companyIds = $detailRows->pluck('f_company_id')->filter()->unique()->values()->all();
        $resellerLinkRows = DB::connection('frontenddb')->table('crm_reseller_link')
            ->whereIn('f_id', $companyIds)
            ->whereNotNull('reseller_name')
            ->where('reseller_name', '!=', '')
            ->get(['f_id', 'reseller_name', 'reseller_id']);
        $resellerByCompany = [];
        foreach ($resellerLinkRows as $r) {
            // Prefer first hit per company.
            $resellerByCompany[$r->f_id] ??= ['name' => $r->reseller_name, 'reseller_id' => $r->reseller_id];
        }

        // 3. Subscriber lookup.
        $companyLookup = DB::connection('frontenddb')->table('crm_company_listing')
            ->whereIn('f_company_id', $companyIds)
            ->pluck('f_company_name', 'f_company_id')
            ->toArray();

        // 4. Aggregate by invoice (so each PI shows up once with the per-module prices that triggered).
        $invoiceMap = [];
        foreach ($detailRows as $row) {
            $reseller = $resellerByCompany[$row->f_company_id] ?? null;
            if (!$reseller) continue; // No reseller mapping -> not relevant to this analysis.

            // Identify which of the four modules this line belongs to.
            $moduleKey = null;
            foreach ($modulePatterns as $mod => $pattern) {
                if (stripos($row->f_name, $pattern) !== false) { $moduleKey = $mod; break; }
            }
            if (!$moduleKey) continue;

            $invoiceNo = $row->f_invoice_no;
            if (!isset($invoiceMap[$invoiceNo])) {
                $invoiceMap[$invoiceNo] = [
                    'invoice_no' => $invoiceNo,
                    'reseller_id' => $reseller['reseller_id'] ?? null,
                    'reseller_name' => strtoupper((string) $reseller['name']),
                    'company_id' => $row->f_company_id,
                    'subscriber_name' => strtoupper((string) ($companyLookup[$row->f_company_id] ?? '-')),
                    'currency' => $currency,
                    'prices' => ['TA' => null, 'TL' => null, 'TC' => null, 'TP' => null],
                    'max_price' => 0.0,
                    'detail_fid' => $row->f_id,
                ];
            }

            // Convert bundle prices (e.g. "10 User License") to per-user prices.
            $price = $this->perUserPrice($row->f_name, (float) $row->f_unit_price);

            // Per-user price must still exceed the threshold (SQL filter was a superset).
            if ($price <= $threshold) continue;

            // Keep the highest per-user price seen per module on the same invoice.
            if ($invoiceMap[$invoiceNo]['prices'][$moduleKey] === null || $price > $invoiceMap[$invoiceNo]['prices'][$moduleKey]) {
                $invoiceMap[$invoiceNo]['prices'][$moduleKey] = $price;
            }
            if ($price > $invoiceMap[$invoiceNo]['max_price']) {
                $invoiceMap[$invoiceNo]['max_price'] = $price;
            }
        }

        // Drop invoices that ended up empty after per-user filtering.
        $invoiceMap = array_filter($invoiceMap, fn($r) => $r['max_price'] > 0);

        // 5. Apply MYR-override exclusion for USD tab (resellers in the override list belong to MYR).
        $myrOverrideSet = array_flip(self::$myrOverrideResellerIds);
        if ($currency === 'USD' && !empty($myrOverrideSet)) {
            $invoiceMap = array_filter($invoiceMap, fn($r) => !isset($myrOverrideSet[$r['reseller_id']]));
        }

        // 6. Build external_url for each invoice (uses the detail row f_id we already captured).
        foreach ($invoiceMap as &$row) {
            $encrypted = openssl_encrypt($row['detail_fid'], 'AES-128-ECB', 'Epicamera@99');
            $row['external_url'] = $encrypted !== false
                ? 'https://www.timeteccloud.com/paypal_reseller_invoice?iIn=' . base64_encode($encrypted)
                : null;
            unset($row['detail_fid']);
        }
        unset($row);

        // 7. Sort by reseller_name asc, then by max_price desc; build byReseller tally.
        $rows = array_values($invoiceMap);
        usort($rows, fn($a, $b) => strcmp($a['reseller_name'], $b['reseller_name']) ?: ($b['max_price'] <=> $a['max_price']));

        $byReseller = [];
        foreach ($rows as $r) {
            $byReseller[$r['reseller_name']] = ($byReseller[$r['reseller_name']] ?? 0) + 1;
        }
        arsort($byReseller);

        return ['rows' => $rows, 'byReseller' => $byReseller, 'threshold' => $threshold];
    }

    /**
     * Pull TT-prefixed ac_invoice rows joined with reseller name + subscriber name, filtered by currency.
     * Grouped by reseller for the sidebar tally.
     *
     * Shape:
     * [
     *   'rows' => [{ invoice_no, reseller_name, reseller_id, subscriber_name, company_id, total_amount, status, discount_dealer, currency }, ...],
     *   'byReseller' => [reseller_name => count],
     * ]
     */
    public function getResellerCommissionPiData(string $currency = 'MYR'): array
    {
        // Map reseller_id -> name from the link table.
        $resellerLookup = DB::connection('frontenddb')
            ->table('crm_reseller_link')
            ->whereNotNull('reseller_name')
            ->where('reseller_name', '!=', '')
            ->select('reseller_id', 'reseller_name')
            ->distinct()
            ->pluck('reseller_name', 'reseller_id')
            ->toArray();

        $myrOverrideResellerIdSet = array_flip(self::$myrOverrideResellerIds);

        // Filter dealer ids by currency. MYR-override resellers go to MYR regardless.
        $invoiceQuery = DB::connection('frontenddb')
            ->table('ac_invoice')
            ->where('f_invoice_no', 'LIKE', 'TT%')
            ->whereNotNull('f_dealer_id')
            ->where('f_dealer_id', '!=', '')
            ->where('f_dealer_id', '!=', '0000000000');

        if ($currency === 'MYR') {
            $invoiceQuery->where(function ($q) use ($myrOverrideResellerIdSet) {
                $q->where('f_currency', 'MYR');
                if (!empty($myrOverrideResellerIdSet)) {
                    $q->orWhereIn('f_dealer_id', array_keys($myrOverrideResellerIdSet));
                }
            });
        } else { // USD
            $invoiceQuery->where('f_currency', 'USD');
            if (!empty($myrOverrideResellerIdSet)) {
                $invoiceQuery->whereNotIn('f_dealer_id', array_keys($myrOverrideResellerIdSet));
            }
        }

        $invoices = $invoiceQuery
            ->orderByDesc('f_id')
            ->get([
                'f_invoice_no',
                'f_dealer_id',
                'f_company_id',
                'f_total_amount',
                'f_dealer_commission',
                'f_discount_dealer',
                'f_status',
                'f_currency',
                'f_created_time',
            ]);

        if ($invoices->isEmpty()) {
            return ['rows' => [], 'byReseller' => []];
        }

        // Restrict to TT PIs that still have at least one crm_expiring_license row with expiry >= today.
        $today = Carbon::now()->format('Y-m-d');
        $candidateInvoiceNos = $invoices->pluck('f_invoice_no')->filter()->unique()->values()->all();
        $activeInvoiceNos = empty($candidateInvoiceNos) ? [] : DB::connection('frontenddb')
            ->table('crm_expiring_license')
            ->whereIn('f_invoice_no', $candidateInvoiceNos)
            ->where('f_expiry_date', '>=', $today)
            ->distinct()
            ->pluck('f_invoice_no')
            ->toArray();
        $activeInvoiceNoSet = array_flip($activeInvoiceNos);
        $invoices = $invoices->filter(fn($inv) => isset($activeInvoiceNoSet[$inv->f_invoice_no]))->values();

        if ($invoices->isEmpty()) {
            return ['rows' => [], 'byReseller' => []];
        }

        // Subscriber name lookup in one batch.
        $companyIds = $invoices->pluck('f_company_id')->filter(fn($id) => $id && $id !== '0000000000')->unique()->values()->all();
        $companyLookup = empty($companyIds) ? [] : DB::connection('frontenddb')
            ->table('crm_company_listing')
            ->whereIn('f_company_id', $companyIds)
            ->pluck('f_company_name', 'f_company_id')
            ->toArray();

        // crm_invoice_details.f_id lookup so each PI can link to the external paypal page.
        $invoiceNos = $invoices->pluck('f_invoice_no')->filter()->unique()->values()->all();
        $invoiceDetailIdLookup = empty($invoiceNos) ? [] : DB::connection('frontenddb')
            ->table('crm_invoice_details')
            ->whereIn('f_invoice_no', $invoiceNos)
            ->select('f_invoice_no', 'f_id')
            ->get()
            ->keyBy('f_invoice_no')
            ->map(fn($r) => $r->f_id)
            ->toArray();

        $rows = [];
        $byReseller = [];

        foreach ($invoices as $inv) {
            $resellerName = $resellerLookup[$inv->f_dealer_id] ?? '(Unknown reseller)';
            $resellerName = strtoupper((string) $resellerName);
            $subscriberName = $companyLookup[$inv->f_company_id] ?? '-';
            $subscriberName = strtoupper((string) $subscriberName);

            $detailFid = $invoiceDetailIdLookup[$inv->f_invoice_no] ?? null;
            $externalUrl = null;
            if ($detailFid) {
                $encrypted = openssl_encrypt($detailFid, 'AES-128-ECB', 'Epicamera@99');
                if ($encrypted !== false) {
                    $externalUrl = 'https://www.timeteccloud.com/paypal_reseller_invoice?iIn=' . base64_encode($encrypted);
                }
            }

            $rows[] = [
                'invoice_no' => $inv->f_invoice_no,
                'reseller_id' => $inv->f_dealer_id,
                'reseller_name' => $resellerName,
                'company_id' => $inv->f_company_id,
                'subscriber_name' => $subscriberName,
                'currency' => $inv->f_currency,
                'total_amount' => (float) ($inv->f_total_amount ?? 0),
                'dealer_commission' => (float) ($inv->f_dealer_commission ?? 0),
                'discount_dealer' => $inv->f_discount_dealer !== null ? (float) $inv->f_discount_dealer : null,
                'status' => (int) ($inv->f_status ?? 0),
                'created_time' => $inv->f_created_time,
                'external_url' => $externalUrl,
            ];

            $byReseller[$resellerName] = ($byReseller[$resellerName] ?? 0) + 1;
        }

        // Sidebar: resellers ordered by PI count desc, then name asc.
        arsort($byReseller);

        return ['rows' => $rows, 'byReseller' => $byReseller];
    }

    /**
     * Group resellers (filtered to those with active licenses for this currency) into f_rate buckets.
     * Each reseller is bucketed by the MAX f_rate they hold across crm_reseller_link rows.
     *
     * @return array<string, array{label: string, count: int, resellers: array}>
     */
    public function getResellerCommissionData(string $currency = 'MYR'): array
    {
        // Resellers that show up in this currency's dashboard list.
        $dashboardData = $currency === 'MYR' ? $this->myrData : $this->usdData;
        $resellerNames = array_filter(array_map(fn($r) => $r['reseller_name'] ?? null, $dashboardData));
        if (empty($resellerNames)) {
            return $this->emptyCommissionBuckets();
        }

        // Pull max f_rate per reseller in one query.
        $rateRows = DB::connection('frontenddb')
            ->table('crm_reseller_link')
            ->whereIn('reseller_name', $resellerNames)
            ->selectRaw('reseller_name, MAX(CAST(f_rate AS DECIMAL(10,2))) as max_rate')
            ->groupBy('reseller_name')
            ->pluck('max_rate', 'reseller_name')
            ->toArray();

        $buckets = $this->emptyCommissionBuckets();
        foreach ($dashboardData as $row) {
            $name = $row['reseller_name'] ?? null;
            if (!$name) continue;
            $rate = (float) ($rateRows[$name] ?? 0);
            $key = $this->bucketKeyForRate($rate);
            $buckets[$key]['resellers'][] = [
                'reseller_name' => $name,
                'rate' => $rate,
                'total_end_users' => (int) ($row['total_end_users'] ?? 0),
            ];
        }

        foreach ($buckets as $key => $bucket) {
            $buckets[$key]['count'] = count($bucket['resellers']);
            usort($buckets[$key]['resellers'], fn($a, $b) => $b['rate'] <=> $a['rate'] ?: strcmp($a['reseller_name'], $b['reseller_name']));
        }

        return $buckets;
    }

    private function emptyCommissionBuckets(): array
    {
        return [
            'b40' => ['label' => '40% – 100%', 'count' => 0, 'resellers' => []],
            'b30' => ['label' => '30% – 39%',  'count' => 0, 'resellers' => []],
            'b0'  => ['label' => '0% – 29%',   'count' => 0, 'resellers' => []],
        ];
    }

    private function bucketKeyForRate(float $rate): string
    {
        return match (true) {
            $rate >= 40 => 'b40',
            $rate >= 30 => 'b30',
            default => 'b0',
        };
    }

    protected function getDateRange(): array
    {
        return match ($this->filterMode) {
            'year' => [
                Carbon::create((int) $this->selectedYear, 1, 1)->format('Y-m-d'),
                Carbon::create((int) $this->selectedYear, 12, 31)->format('Y-m-d'),
            ],
            'month' => [
                Carbon::create((int) $this->selectedYear, $this->selectedMonth ?? now()->month, 1)
                    ->startOfMonth()->format('Y-m-d'),
                Carbon::create((int) $this->selectedYear, $this->selectedMonth ?? now()->month, 1)
                    ->endOfMonth()->format('Y-m-d'),
            ],
            'range' => [
                $this->startDate ?: Carbon::now()->format('Y-m-d'),
                $this->endDate ?: Carbon::now()->addYears(10)->format('Y-m-d'),
            ],
            default => [
                Carbon::now()->format('Y-m-d'),
                Carbon::now()->addYears(10)->format('Y-m-d'),
            ],
        };
    }

    public function updatedFilterMode(): void
    {
        $this->reloadResellerData();
    }

    public function updatedSelectedYear(): void
    {
        $this->reloadResellerData();
    }

    public function updatedSelectedMonthYear(): void
    {
        if (! empty($this->selectedMonthYear)) {
            [$y, $m] = explode('-', $this->selectedMonthYear);
            $this->selectedYear = $y;
            $this->selectedMonth = (int) $m;
        }
        $this->reloadResellerData();
    }

    public function updatedStartDate(): void
    {
        $this->reloadResellerData();
    }

    public function updatedEndDate(): void
    {
        $this->reloadResellerData();
    }

    protected function reloadResellerData(): void
    {
        $this->myrData = $this->getResellerData('MYR');
        $this->usdData = $this->getResellerData('USD');
        $this->myrSummary = $this->getCurrencySummary('MYR');
        $this->usdSummary = $this->getCurrencySummary('USD');
        $this->expandedYearMonths = $this->collectAllGroupKeys();
        $this->expandedResellersMyr = [];
        $this->expandedResellersUsd = [];
    }

    /**
     * Convert a bundle's gross unit price to a per-unit price by dividing out the bundle size
     * found in the name. Matches any "(N <word> License)" pattern, e.g.:
     *   "TimeTec TA (10 User License)"           => unit_price / 10
     *   "TimeTec Payroll (10 Payroll License)"   => unit_price / 10
     *   "TimeTec Patrol (10 Checkpoint License)" => unit_price / 10
     * Names without such a tag are returned unchanged.
     */
    protected function perUserPrice(?string $name, float $unitPrice): float
    {
        if ($name && preg_match('/\((\d+)\s*[A-Za-z]+\s+License\)/i', $name, $m)) {
            $bundle = max(1, (int) $m[1]);
            return $bundle > 0 ? $unitPrice / $bundle : $unitPrice;
        }
        return $unitPrice;
    }

    protected function buildExclusionQuery($query)
    {
        foreach (self::$excludedProducts as $excludedProduct) {
            $query->where('f_name', 'NOT LIKE', '%' . $excludedProduct . '%');
        }
        return $query;
    }

    public function getResellerData(string $currency = 'MYR'): array
    {
        try {
            [$startDate, $endDate] = $this->getDateRange();

            // Get all resellers with their companies
            $resellers = DB::connection('frontenddb')
                ->table('crm_reseller_link')
                ->select('reseller_name', 'reseller_id', 'f_id')
                ->whereNotNull('reseller_name')
                ->where('reseller_name', '!=', '')
                ->get()
                ->groupBy('reseller_name');

            $allCompanyIds = $resellers->flatten()->pluck('f_id')->unique()->toArray();

            if (empty($allCompanyIds)) {
                return [];
            }

            // Get company IDs belonging to MYR-override resellers
            $myrOverrideCompanyIds = DB::connection('frontenddb')
                ->table('crm_reseller_link')
                ->whereIn('reseller_id', self::$myrOverrideResellerIds)
                ->pluck('f_id')
                ->toArray();

            // Batch fetch distinct company IDs that have licenses in the date range
            $licensesQuery = DB::connection('frontenddb')->table('crm_expiring_license')
                ->whereIn('f_company_id', $allCompanyIds)
                ->where('f_expiry_date', '>=', $startDate)
                ->where('f_expiry_date', '<=', $endDate);

            if ($currency === 'MYR' && !empty($myrOverrideCompanyIds)) {
                // MYR: include MYR currency OR override reseller companies (any currency)
                $licensesQuery->where(function ($q) use ($myrOverrideCompanyIds) {
                    $q->where('f_currency', 'MYR')
                      ->orWhereIn('f_company_id', $myrOverrideCompanyIds);
                });
            } elseif ($currency === 'USD' && !empty($myrOverrideCompanyIds)) {
                // USD: only USD currency AND exclude override reseller companies
                $licensesQuery->where('f_currency', 'USD')
                    ->whereNotIn('f_company_id', $myrOverrideCompanyIds);
            } else {
                $licensesQuery->where('f_currency', $currency);
            }

            $licensesQuery = $this->buildExclusionQuery($licensesQuery);
            // Per-company HC sum + earliest expiry within the date range. Keys serve as the
            // "has licenses" set; earliest_expiry powers the year-month grouping above the table.
            $companyData = $licensesQuery
                ->selectRaw('f_company_id, SUM(f_unit) as total_hc, MIN(f_expiry_date) as earliest_expiry')
                ->groupBy('f_company_id')
                ->get()
                ->mapWithKeys(fn ($r) => [$r->f_company_id => [
                    'total_hc' => (int) $r->total_hc,
                    'earliest_expiry' => $r->earliest_expiry,
                ]])
                ->toArray();

            // Batch fetch registered reseller IDs and codes from reseller_v2
            $resellerV2Data = \App\Models\ResellerV2::whereNotNull('reseller_id')
                ->get(['reseller_id', 'debtor_code', 'creditor_code'])
                ->keyBy('reseller_id');
            $registeredResellerIds = $resellerV2Data->keys()->flip()->toArray();

            // Build result per reseller - count is number of unique clients
            $result = [];
            foreach ($resellers as $resellerName => $companies) {
                $companyIds = $companies->pluck('f_id')->toArray();
                $resellerId = $companies->first()->reseller_id;
                $totalClients = 0;
                $totalHc = 0;
                $earliest = null;

                foreach ($companyIds as $companyId) {
                    if (!isset($companyData[$companyId])) continue;
                    $totalClients++;
                    $totalHc += $companyData[$companyId]['total_hc'];
                    $exp = $companyData[$companyId]['earliest_expiry'];
                    if ($exp && (!$earliest || $exp < $earliest)) {
                        $earliest = $exp;
                    }
                }

                if ($totalClients > 0) {
                    $rv2 = $resellerV2Data->get($resellerId);
                    $result[] = [
                        'reseller_name' => $resellerName,
                        'total_end_users' => $totalClients,
                        'total_hc' => $totalHc,
                        // Forecast Cost = HC × rate(1) × months(12); matches AdminRenewalProcessData*::generateForecastCost()
                        'total_forecast_cost' => $totalHc * 12,
                        'has_account' => isset($registeredResellerIds[$resellerId]),
                        'debtor_code' => $rv2->debtor_code ?? '',
                        'creditor_code' => $rv2->creditor_code ?? '',
                        'earliest_expiry' => $earliest,  // YYYY-MM-DD or null; drives the year-month grouping
                    ];
                }
            }

            usort($result, fn($a, $b) => $b['total_end_users'] <=> $a['total_end_users']);

            return $result;
        } catch (\Exception $e) {
            Log::error('Error fetching reseller data: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Summary aggregates for the cards row above the reseller table — mirrors
     * Termination Analysis's category breakdown + HR module penetration cards,
     * scoped to the resellers/clients in the given currency.
     *
     *   categories.{end_user,dealer,distributor} = unique resellers by their own
     *       f_company_type (sum equals count of resellers shown in $myrData/$usdData).
     *   modules.{TA,TL,TC,TP} = {headcount, companies} across the client portfolio,
     *       using f_unit for HC (same column the reseller table sums).
     */
    public function getCurrencySummary(string $currency): array
    {
        $empty = [
            'categories' => ['end_user' => 0, 'dealer' => 0, 'distributor' => 0],
            'modules' => array_fill_keys(['TA', 'TL', 'TC', 'TP'], ['headcount' => 0, 'companies' => 0]),
        ];

        try {
            [$startDate, $endDate] = $this->getDateRange();

            $resellers = DB::connection('frontenddb')
                ->table('crm_reseller_link as rl')
                ->leftJoin('crm_customer as r', 'rl.reseller_id', '=', 'r.company_id')
                ->select('rl.reseller_name', 'rl.reseller_id', 'rl.f_id', 'r.f_company_type as reseller_type')
                ->whereNotNull('rl.reseller_name')
                ->where('rl.reseller_name', '!=', '')
                ->get();

            $allCompanyIds = $resellers->pluck('f_id')->unique()->filter()->values()->toArray();
            if (empty($allCompanyIds)) {
                return $empty;
            }

            $myrOverrideCompanyIds = DB::connection('frontenddb')
                ->table('crm_reseller_link')
                ->whereIn('reseller_id', self::$myrOverrideResellerIds)
                ->pluck('f_id')
                ->toArray();

            $licQ = DB::connection('frontenddb')->table('crm_expiring_license')
                ->whereIn('f_company_id', $allCompanyIds)
                ->where('f_expiry_date', '>=', $startDate)
                ->where('f_expiry_date', '<=', $endDate);

            if ($currency === 'MYR' && !empty($myrOverrideCompanyIds)) {
                $licQ->where(function ($q) use ($myrOverrideCompanyIds) {
                    $q->where('f_currency', 'MYR')
                      ->orWhereIn('f_company_id', $myrOverrideCompanyIds);
                });
            } elseif ($currency === 'USD' && !empty($myrOverrideCompanyIds)) {
                $licQ->where('f_currency', 'USD')
                     ->whereNotIn('f_company_id', $myrOverrideCompanyIds);
            } else {
                $licQ->where('f_currency', $currency);
            }
            $licQ = $this->buildExclusionQuery($licQ);

            $clientCompanyIds = $licQ->distinct()->pluck('f_company_id')->toArray();
            if (empty($clientCompanyIds)) {
                return $empty;
            }
            $clientIdSet = array_flip($clientCompanyIds);

            // Card 2 — categorize by reseller's own type (group by name so the sum
            // matches count($myrData) which also groups by reseller_name).
            $resellerNameToType = [];
            foreach ($resellers as $row) {
                if (!isset($clientIdSet[$row->f_id])) continue;
                if (!isset($resellerNameToType[$row->reseller_name])) {
                    $resellerNameToType[$row->reseller_name] = strtoupper($row->reseller_type ?? '');
                }
            }
            $categories = ['end_user' => 0, 'dealer' => 0, 'distributor' => 0];
            foreach ($resellerNameToType as $type) {
                if ($type === 'DEALER') {
                    $categories['dealer']++;
                } elseif ($type === 'DISTRIBUTOR') {
                    $categories['distributor']++;
                } else {
                    $categories['end_user']++;
                }
            }

            // Cards 3–6 — HR module penetration across the client portfolio.
            $moduleKeywords = [
                'TA' => ['TimeTec TA', 'TimeTec Attendance'],
                'TL' => ['TimeTec Leave'],
                'TC' => ['TimeTec Claim'],
                'TP' => ['TimeTec Payroll'],
            ];
            $moduleLicenses = DB::connection('frontenddb')
                ->table('crm_expiring_license')
                ->whereIn('f_company_id', $clientCompanyIds)
                ->where('f_expiry_date', '>=', $startDate)
                ->where('f_expiry_date', '<=', $endDate)
                ->where('f_type', 'PAID')
                ->select('f_company_id', 'f_name', 'f_unit')
                ->get();

            $modules = [
                'TA' => ['headcount' => 0, 'companies' => []],
                'TL' => ['headcount' => 0, 'companies' => []],
                'TC' => ['headcount' => 0, 'companies' => []],
                'TP' => ['headcount' => 0, 'companies' => []],
            ];
            foreach ($moduleLicenses as $lic) {
                foreach ($moduleKeywords as $key => $keywords) {
                    foreach ($keywords as $kw) {
                        if (str_contains($lic->f_name ?? '', $kw)) {
                            $modules[$key]['headcount'] += (int) $lic->f_unit;
                            $modules[$key]['companies'][$lic->f_company_id] = true;
                            break 2;
                        }
                    }
                }
            }
            foreach ($modules as $key => $m) {
                $modules[$key]['companies'] = count($m['companies']);
            }

            return [
                'categories' => $categories,
                'modules' => $modules,
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching reseller currency summary: ' . $e->getMessage());
            return $empty;
        }
    }

    /**
     * Group a reseller-data array (from getResellerData) into year-month buckets keyed by the
     * Asia/Kuala_Lumpur calendar month of each reseller's earliest_expiry. Drives the Tier-1
     * collapsible header rows above the reseller table. Mirrors Termination Analysis's grouping.
     */
    public function groupByYearMonth(array $rows): array
    {
        $grouped = [];
        foreach ($rows as $row) {
            $exp = $row['earliest_expiry'] ?? null;
            if (!$exp) continue;
            $dt = \Carbon\Carbon::parse($exp)->setTimezone('Asia/Kuala_Lumpur');
            $key = $dt->format('Y-m');
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'label' => $dt->format('Y') . ' - ' . $dt->format('F'),
                    'year_month' => $key,
                    'count' => 0,
                    'total_hc' => 0,
                    'total_forecast_cost' => 0,
                    'resellers' => [],
                ];
            }
            $grouped[$key]['count']++;
            $grouped[$key]['total_hc'] += (int) ($row['total_hc'] ?? 0);
            $grouped[$key]['total_forecast_cost'] += (int) ($row['total_forecast_cost'] ?? 0);
            $grouped[$key]['resellers'][] = $row;
        }
        ksort($grouped);
        return $grouped;
    }

    /**
     * Union of year-month keys across MYR + USD groupings — used to default-expand every group
     * on first load and after any filter change.
     */
    protected function collectAllGroupKeys(): array
    {
        return array_values(array_unique(array_merge(
            array_keys($this->groupByYearMonth($this->myrData)),
            array_keys($this->groupByYearMonth($this->usdData)),
        )));
    }

    public function toggleYearMonth(string $yearMonth): void
    {
        if (in_array($yearMonth, $this->expandedYearMonths, true)) {
            $this->expandedYearMonths = array_values(array_diff($this->expandedYearMonths, [$yearMonth]));
        } else {
            $this->expandedYearMonths[] = $yearMonth;
        }
    }

    public function toggleResellerExpansion(string $resellerName, string $currency): void
    {
        $prop = $currency === 'USD' ? 'expandedResellersUsd' : 'expandedResellersMyr';
        if (in_array($resellerName, $this->$prop, true)) {
            $this->$prop = array_values(array_diff($this->$prop, [$resellerName]));
        } else {
            $this->$prop[] = $resellerName;
        }
    }

    /**
     * Build the forecast snapshot for the active currency tab and open the modal.
     * Pure aggregation over already-hydrated $myrData / $usdData + $myrSummary / $usdSummary —
     * no extra queries. Mirrors TerminationAnalysis::generateForecastCost() (RM 1 × 12 months).
     */
    public function generateForecastCost(string $currency): void
    {
        $data    = $currency === 'USD' ? $this->usdData    : $this->myrData;
        $summary = $currency === 'USD' ? $this->usdSummary : $this->myrSummary;

        $headcount = (int) array_sum(array_column($data, 'total_hc'));
        $rate      = 1;
        $months    = 12;

        $modules = [];
        foreach (['TA', 'TL', 'TC', 'TP'] as $key) {
            $modHc = (int) ($summary['modules'][$key]['headcount'] ?? 0);
            $modules[$key] = [
                'headcount' => $modHc,
                'cost'      => $modHc * $rate * $months,
            ];
        }

        [$startDate, $endDate] = $this->getDateRange();

        $this->forecastData = [
            'currency'  => $currency,
            'symbol'    => $currency === 'USD' ? 'USD' : 'RM',
            'headcount' => $headcount,
            'rate'      => $rate,
            'months'    => $months,
            'total'     => $headcount * $rate * $months,
            'modules'   => $modules,
            'date_from' => \Carbon\Carbon::parse($startDate)->format('d M Y'),
            'date_to'   => \Carbon\Carbon::parse($endDate)->format('d M Y'),
        ];
        $this->showForecastModal = true;
    }

    public function closeForecastModal(): void
    {
        $this->showForecastModal = false;
    }

    /**
     * Slim sibling of openResellerDrawer — same first 3 queries (reseller-link IDs,
     * MYR-override IDs, license sweep) but skips the per-client renewals lookup.
     * Returns just enough for the inline expansion row (name + HC + forecast cost).
     */
    public function getResellerClientsCompact(string $resellerName, string $currency): array
    {
        try {
            [$startDate, $endDate] = $this->getDateRange();

            $companyIds = DB::connection('frontenddb')
                ->table('crm_reseller_link')
                ->where('reseller_name', $resellerName)
                ->pluck('f_id')
                ->toArray();
            if (empty($companyIds)) return [];

            $myrOverrideCompanyIds = DB::connection('frontenddb')
                ->table('crm_reseller_link')
                ->whereIn('reseller_id', self::$myrOverrideResellerIds)
                ->pluck('f_id')
                ->toArray();

            $q = DB::connection('frontenddb')->table('crm_expiring_license')
                ->whereIn('f_company_id', $companyIds)
                ->where('f_expiry_date', '>=', $startDate)
                ->where('f_expiry_date', '<=', $endDate);

            if ($currency === 'MYR' && !empty($myrOverrideCompanyIds)) {
                $q->where(function ($qq) use ($myrOverrideCompanyIds) {
                    $qq->where('f_currency', 'MYR')->orWhereIn('f_company_id', $myrOverrideCompanyIds);
                });
            } elseif ($currency === 'USD' && !empty($myrOverrideCompanyIds)) {
                $q->where('f_currency', 'USD')->whereNotIn('f_company_id', $myrOverrideCompanyIds);
            } else {
                $q->where('f_currency', $currency);
            }
            $q = $this->buildExclusionQuery($q);

            $rows = $q->selectRaw('f_company_id, f_company_name, SUM(f_unit) as total_hc')
                      ->groupBy('f_company_id', 'f_company_name')
                      ->orderByDesc(DB::raw('SUM(f_unit)'))
                      ->get();

            return $rows->map(fn ($r) => [
                'company_name' => $r->f_company_name,
                'total_hc' => (int) $r->total_hc,
                'total_forecast_cost' => (int) $r->total_hc * 12,
            ])->toArray();
        } catch (\Exception $e) {
            Log::error('Error fetching compact reseller clients: ' . $e->getMessage());
            return [];
        }
    }

    public function openResellerDrawer(string $resellerName, string $currency): void
    {
        try {
            $this->drawerCurrency = $currency;
            [$startDate, $endDate] = $this->getDateRange();

            // Get companies for this reseller
            $companyIds = DB::connection('frontenddb')
                ->table('crm_reseller_link')
                ->where('reseller_name', $resellerName)
                ->pluck('f_id')
                ->toArray();

            if (empty($companyIds)) {
                $this->drawerClients = [];
                $this->drawerTitle = strtoupper($resellerName) ;
                $this->showDrawer = true;
                return;
            }

            // Get company IDs belonging to MYR-override resellers
            $myrOverrideCompanyIds = DB::connection('frontenddb')
                ->table('crm_reseller_link')
                ->whereIn('reseller_id', self::$myrOverrideResellerIds)
                ->pluck('f_id')
                ->toArray();

            // Batch fetch licenses grouped by company
            $licensesQuery = DB::connection('frontenddb')->table('crm_expiring_license')
                ->whereIn('f_company_id', $companyIds)
                ->where('f_expiry_date', '>=', $startDate)
                ->where('f_expiry_date', '<=', $endDate);

            if ($currency === 'MYR' && !empty($myrOverrideCompanyIds)) {
                $licensesQuery->where(function ($q) use ($myrOverrideCompanyIds) {
                    $q->where('f_currency', 'MYR')
                      ->orWhereIn('f_company_id', $myrOverrideCompanyIds);
                });
            } elseif ($currency === 'USD' && !empty($myrOverrideCompanyIds)) {
                $licensesQuery->where('f_currency', 'USD')
                    ->whereNotIn('f_company_id', $myrOverrideCompanyIds);
            } else {
                $licensesQuery->where('f_currency', $currency);
            }

            $licensesQuery = $this->buildExclusionQuery($licensesQuery);
            $companyData = $licensesQuery
                ->selectRaw('f_company_id, f_company_name, MIN(f_expiry_date) as earliest_expiry, SUM(f_unit) as total_hc')
                ->groupBy('f_company_id', 'f_company_name')
                ->get();

            if ($companyData->isEmpty()) {
                $this->drawerClients = [];
                $this->drawerTitle = strtoupper($resellerName);
                $this->showDrawer = true;
                return;
            }

            // Look up renewals fresh per company — the scope CASTs to UNSIGNED so it handles
            // any zero-padding mismatch between crm_expiring_license.f_company_id and renewals.f_company_id.
            $clients = [];

            foreach ($companyData as $company) {
                $renewal = \App\Models\Renewal::whereCompanyId($company->f_company_id)->first();

                $clients[] = [
                    'company_name' => strtoupper($company->f_company_name),
                    'f_company_id' => $company->f_company_id,
                    'status' => $renewal ? $renewal->renewal_progress : 'no_record',
                    'earliest_expiry' => $company->earliest_expiry,
                    'lead_id' => $renewal?->lead_id,
                    'total_hc' => (int) $company->total_hc,
                ];
            }

            // Sort by expiry asc, then by crm_reseller_link natural order — matches ResellerExpiredLicense.
            $linkOrder = array_flip($companyIds);
            usort($clients, function ($a, $b) use ($linkOrder) {
                $cmp = strcmp($a['earliest_expiry'], $b['earliest_expiry']);
                if ($cmp !== 0) {
                    return $cmp;
                }
                return ($linkOrder[$a['f_company_id']] ?? PHP_INT_MAX) <=> ($linkOrder[$b['f_company_id']] ?? PHP_INT_MAX);
            });

            $this->drawerClients = $clients;
            $this->drawerTitle = strtoupper($resellerName);
            $this->showDrawer = true;
        } catch (\Exception $e) {
            Log::error('Error opening reseller drawer: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            $this->drawerClients = [];
            $this->drawerTitle = 'Error loading data';
            $this->showDrawer = true;
        }
    }

    public function closeDrawer(): void
    {
        $this->showDrawer = false;
        $this->drawerClients = [];
    }

    public function exportToExcel(string $currency): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $data = $currency === 'MYR' ? $this->myrData : $this->usdData;
        $timestamp = now()->format('Y-m-d_H-i-s');

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\ResellerAnalysisExport($data, $currency),
            "reseller_analysis_{$currency}_{$timestamp}.xlsx"
        );
    }

    public function exportResellerPricingToExcel(string $resellerName, string $currency): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $clients = $this->buildPricingClientsForReseller($resellerName, $currency);

        $title     = strtoupper($resellerName);
        $timestamp = now()->format('Y-m-d_H-i-s');
        $safeName  = preg_replace('/[^a-zA-Z0-9_-]/', '_', $title);

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\ResellerPricingAnalysisExport($clients, $title, $currency),
            "reseller_pricing_{$safeName}_{$timestamp}.xlsx"
        );
    }

    public function exportPricingSummaryToExcel(string $currency): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        // One sheet per reseller, in a single workbook — same reseller order as the dashboard list
        // (myrData / usdData already sorted by total_end_users DESC in getResellerData).
        $dashboardData = $currency === 'MYR' ? $this->myrData : $this->usdData;

        $sheetsData = [];
        foreach ($dashboardData as $row) {
            $resellerName = $row['reseller_name'] ?? null;
            if (!$resellerName) {
                continue;
            }
            $clients = $this->buildPricingClientsForReseller($resellerName, $currency);
            if (!empty($clients)) {
                $sheetsData[$resellerName] = $clients;
            }
        }

        $timestamp = now()->format('Y-m-d_H-i-s');

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\ResellerPricingSummaryExport($sheetsData, $currency),
            "reseller_pricing_summary_{$currency}_{$timestamp}.xlsx"
        );
    }

    public function exportPricingSummaryAllToExcel(string $currency): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        // Single sheet combining all resellers — reseller name as column B.
        $dashboardData = $currency === 'MYR' ? $this->myrData : $this->usdData;

        $sheetsData = [];
        foreach ($dashboardData as $row) {
            $resellerName = $row['reseller_name'] ?? null;
            if (!$resellerName) {
                continue;
            }
            $clients = $this->buildPricingClientsForReseller($resellerName, $currency);
            if (!empty($clients)) {
                $sheetsData[$resellerName] = $clients;
            }
        }

        $timestamp = now()->format('Y-m-d_H-i-s');

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\ResellerPricingAnalysisAllExport($sheetsData, $currency),
            "reseller_pricing_summary_all_{$currency}_{$timestamp}.xlsx"
        );
    }

    private function buildPricingClientsForReseller(string $resellerName, string $currency): array
    {
        $today = \Carbon\Carbon::now()->startOfDay();

        $links = DB::connection('frontenddb')
            ->table('crm_reseller_link')
            ->where('reseller_name', $resellerName)
            ->get(['f_id', 'f_company_name', 'f_rate']);

        // Currency filter: if reseller is in MYR-override list, MYR tab includes everything for that reseller, USD tab excludes them.
        $isMyrOverride = DB::connection('frontenddb')
            ->table('crm_reseller_link')
            ->where('reseller_name', $resellerName)
            ->whereIn('reseller_id', self::$myrOverrideResellerIds)
            ->exists();

        if ($currency === 'USD' && $isMyrOverride) {
            $links = collect();
        }

        // Specific patterns to avoid false matches (e.g. "%TA%" would match "STARTER SUITE").
        $modulePatterns = [
            'TA' => 'TimeTec TA',
            'TL' => 'TimeTec Leave',
            'TC' => 'TimeTec Claim',
            'TP' => 'TimeTec Payroll',
        ];

        $clients = [];

        foreach ($links as $link) {
            // Source: crm_company_license — same table & filter as ResellerExpiredLicense (active + expiry >= today).
            $licenses = DB::connection('frontenddb')
                ->table('crm_company_license')
                ->where('f_company_id', $link->f_id)
                ->where('f_type', 'PAID')
                ->where('status', 'Active')
                ->whereDate('f_expiry_date', '>=', $today->format('Y-m-d'))
                ->where(function ($q) use ($modulePatterns) {
                    foreach ($modulePatterns as $pattern) {
                        $q->orWhere('f_name', 'like', '%' . $pattern . '%');
                    }
                })
                ->orderBy('f_expiry_date', 'asc')
                ->get(['f_invoice_no', 'f_name', 'f_expiry_date']);

            if ($licenses->isEmpty()) {
                continue;
            }

            // Pull unit prices for these invoices from crm_invoice_details (one query per company).
            $invoiceNumbers = $licenses->pluck('f_invoice_no')->filter()->unique()->values()->all();
            $invoiceDetailsRows = !empty($invoiceNumbers)
                ? DB::connection('frontenddb')
                    ->table('crm_invoice_details')
                    ->where('f_company_id', $link->f_id)
                    ->whereIn('f_invoice_no', $invoiceNumbers)
                    ->get(['f_invoice_no', 'f_name', 'f_unit_price'])
                : collect();

            // Per-invoice commission rate from ac_invoice.f_discount_dealer.
            $acInvoiceRows = !empty($invoiceNumbers)
                ? DB::connection('frontenddb')
                    ->table('ac_invoice')
                    ->where('f_company_id', $link->f_id)
                    ->whereIn('f_invoice_no', $invoiceNumbers)
                    ->get(['f_invoice_no', 'f_discount_dealer'])
                : collect();
            $invoiceCommissionLookup = $acInvoiceRows->pluck('f_discount_dealer', 'f_invoice_no')->all();

            // Group invoice details: [invoice_no][module] => unit_price
            $unitPriceLookup = [];
            foreach ($invoiceDetailsRows as $row) {
                foreach ($modulePatterns as $mod => $pattern) {
                    if (stripos($row->f_name, $pattern) !== false) {
                        $unitPriceLookup[$row->f_invoice_no][$mod] = (float) $row->f_unit_price;
                        break;
                    }
                }
            }

            // Group licenses by invoice number; a single invoice may cover multiple modules.
            $invoicesGrouped = [];
            foreach ($licenses as $license) {
                $invoiceNo = $license->f_invoice_no ?? '(no invoice)';
                if (!isset($invoicesGrouped[$invoiceNo])) {
                    $invoicesGrouped[$invoiceNo] = [
                        'invoice_no'  => $invoiceNo,
                        'expiry_date' => $license->f_expiry_date,
                        'modules'     => [],
                    ];
                }
                // Track earliest expiry per invoice (in case multiple licenses share an invoice).
                if (strcmp($license->f_expiry_date, $invoicesGrouped[$invoiceNo]['expiry_date']) < 0) {
                    $invoicesGrouped[$invoiceNo]['expiry_date'] = $license->f_expiry_date;
                }
                foreach ($modulePatterns as $mod => $pattern) {
                    if (stripos($license->f_name, $pattern) !== false) {
                        $invoicesGrouped[$invoiceNo]['modules'][$mod] = true;
                        break;
                    }
                }
            }

            $fallbackRate = (float) ($link->f_rate ?? 0);
            $invoiceRows  = [];

            foreach ($invoicesGrouped as $inv) {
                // Per-invoice commission rate (ac_invoice.f_discount_dealer); fall back to crm_reseller_link.f_rate.
                $invoiceCommission = $invoiceCommissionLookup[$inv['invoice_no']] ?? null;
                $commissionRate    = $invoiceCommission !== null ? (float) $invoiceCommission : $fallbackRate;

                $unitPrices      = ['TA' => null, 'TL' => null, 'TC' => null, 'TP' => null];
                $afterCommission = ['TA' => null, 'TL' => null, 'TC' => null, 'TP' => null];

                foreach (array_keys($inv['modules']) as $mod) {
                    $price = $unitPriceLookup[$inv['invoice_no']][$mod] ?? null;
                    if ($price !== null) {
                        $unitPrices[$mod]      = $price;
                        $afterCommission[$mod] = round($price * (1 - $commissionRate / 100), 2);
                    }
                }

                $invoiceRows[] = [
                    'invoice_no'        => $inv['invoice_no'],
                    'expiry_date'       => $inv['expiry_date'],
                    'days_until_expiry' => (int) $today->diffInDays(\Carbon\Carbon::parse($inv['expiry_date'])),
                    'commission_rate'   => $commissionRate,
                    'unit_prices'       => $unitPrices,
                    'after_commission'  => $afterCommission,
                ];
            }

            // Sort invoices within company by expiry asc (matches ResellerExpiredLicense).
            usort($invoiceRows, fn ($a, $b) => strcmp($a['expiry_date'], $b['expiry_date']));

            // Per-company days_until_expiry = the earliest license's days (matches ResellerExpiredLicense::getCompaniesProperty).
            $earliestLicenseExpiry = collect($licenses)
                ->sortBy('f_expiry_date')
                ->first()
                ->f_expiry_date;
            $companyDaysUntilExpiry = (int) $today->diffInDays(\Carbon\Carbon::parse($earliestLicenseExpiry));

            $clients[] = [
                'company_name'      => strtoupper($link->f_company_name),
                'earliest_expiry'   => $earliestLicenseExpiry,
                'days_until_expiry' => $companyDaysUntilExpiry,
                'commission_rate'   => $commissionRate,
                'invoices'          => $invoiceRows,
            ];
        }

        // Sort companies by days_until_expiry ASC — same default sort as ResellerExpiredLicense.
        usort($clients, fn ($a, $b) => $a['days_until_expiry'] <=> $b['days_until_expiry']);

        return $clients;
    }

    public function exportResellerToExcel(string $resellerName, string $currency): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        // Fetch client data for this reseller (same logic as openResellerDrawer)
        [$startDate, $endDate] = $this->getDateRange();

        $companyIds = DB::connection('frontenddb')
            ->table('crm_reseller_link')
            ->where('reseller_name', $resellerName)
            ->pluck('f_id')
            ->toArray();

        $clients = [];

        if (!empty($companyIds)) {
            // Get company IDs belonging to MYR-override resellers
            $myrOverrideCompanyIds = DB::connection('frontenddb')
                ->table('crm_reseller_link')
                ->whereIn('reseller_id', self::$myrOverrideResellerIds)
                ->pluck('f_id')
                ->toArray();

            $licensesQuery = DB::connection('frontenddb')->table('crm_expiring_license')
                ->whereIn('f_company_id', $companyIds)
                ->where('f_expiry_date', '>=', $startDate)
                ->where('f_expiry_date', '<=', $endDate);

            if ($currency === 'MYR' && !empty($myrOverrideCompanyIds)) {
                $licensesQuery->where(function ($q) use ($myrOverrideCompanyIds) {
                    $q->where('f_currency', 'MYR')
                      ->orWhereIn('f_company_id', $myrOverrideCompanyIds);
                });
            } elseif ($currency === 'USD' && !empty($myrOverrideCompanyIds)) {
                $licensesQuery->where('f_currency', 'USD')
                    ->whereNotIn('f_company_id', $myrOverrideCompanyIds);
            } else {
                $licensesQuery->where('f_currency', $currency);
            }

            $licensesQuery = $this->buildExclusionQuery($licensesQuery);
            $companyData = $licensesQuery
                ->selectRaw('f_company_id, f_company_name, MIN(f_expiry_date) as earliest_expiry, SUM(f_unit) as total_hc')
                ->groupBy('f_company_id', 'f_company_name')
                ->get();

            foreach ($companyData as $company) {
                $renewal = \App\Models\Renewal::whereCompanyId($company->f_company_id)->first();
                $clients[] = [
                    'company_name' => strtoupper($company->f_company_name),
                    'f_company_id' => $company->f_company_id,
                    'status' => $renewal ? $renewal->renewal_progress : 'no_record',
                    'earliest_expiry' => $company->earliest_expiry,
                    'lead_id' => $renewal?->lead_id,
                    'total_hc' => (int) $company->total_hc,
                ];
            }

            // Sort by expiry asc, then by crm_reseller_link natural order — matches ResellerExpiredLicense.
            $linkOrder = array_flip($companyIds);
            usort($clients, function ($a, $b) use ($linkOrder) {
                $cmp = strcmp($a['earliest_expiry'], $b['earliest_expiry']);
                if ($cmp !== 0) {
                    return $cmp;
                }
                return ($linkOrder[$a['f_company_id']] ?? PHP_INT_MAX) <=> ($linkOrder[$b['f_company_id']] ?? PHP_INT_MAX);
            });
        }

        $title = strtoupper($resellerName);
        $timestamp = now()->format('Y-m-d_H-i-s');
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $title);

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\ResellerAnalysisDetailExport($clients, $title, $currency),
            "reseller_detail_{$safeName}_{$timestamp}.xlsx"
        );
    }
}
