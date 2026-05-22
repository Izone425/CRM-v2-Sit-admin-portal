<?php

namespace App\Filament\Pages;

use App\Models\TerminationAnalysisNote;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;

class TerminationAnalysis extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Termination Analysis';
    protected static ?string $title = '';
    protected static ?int $navigationSort = 6;
    protected static ?string $navigationGroup = 'HR License';

    public static $allowedProducts = [
        'TimeTec TA (1 User License)',
        'TimeTec TA (10 User License)',
        'TimeTec Leave (1 User License)',
        'TimeTec Leave (10 User License)',
        'TimeTec Claim (1 User License)',
        'TimeTec Claim (10 User License)',
        'TimeTec Payroll (1 Payroll License)',
        'TimeTec Payroll (10 Payroll License)',
    ];

    protected static string $view = 'filament.pages.termination-analysis';

    // Expandable state
    public array $expandedYearMonths = [];
    public array $expandedCompanies = [];

    // Data
    public array $groupedData = [];
    public array $summary = [];
    public array $availableYears = [];
    public array $availableMonths = [];

    // Filter properties
    public string $filterMode = 'month'; // 'month' or 'range'
    public string $selectedYear = '';
    public $selectedMonth;
    public string $startDate = '';
    public string $endDate = '';

    // Search
    public string $search = '';

    // Region filter: 'all', 'malaysia', 'overseas'
    public string $region = 'all';

    // Category filter: 'all', 'end_user', 'dealer', 'distributor'
    public string $categoryFilter = 'all';

    // Reason filter: 'all', 'completed', 'pending'
    public string $reasonFilter = 'all';

    // View mode: 'list' or 'chart'
    public string $viewMode = 'list';
    public array $chartData = [];
    public array $hiddenChartDatasets = [];

    // Sort: 'expiry_desc', 'expiry_asc', 'name_asc', 'name_desc'
    public string $sortBy = 'name_asc';

    // Modal state
    public bool $showReasonModal = false;
    public bool $showViewReasonModal = false;
    public string $modalCompanyId = '';
    public string $modalCompanyName = '';
    public string $modalReasonText = '';

    // Forecast cost modal state
    public bool $showForecastModal = false;
    public array $forecastData = [
        'headcount' => 0,
        'rate'      => 1,
        'months'    => 12,
        'total'     => 0,
        'modules'   => [
            'TA' => ['headcount' => 0, 'cost' => 0],
            'TL' => ['headcount' => 0, 'cost' => 0],
            'TC' => ['headcount' => 0, 'cost' => 0],
            'TP' => ['headcount' => 0, 'cost' => 0],
        ],
    ];

    public function mount(): void
    {
        $this->selectedYear = (string) now()->year;
        $this->selectedMonth = now()->month;
        $this->selectedMonthYear = now()->format('Y-m');
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');

        $this->loadAvailableYears();
        $this->loadAvailableMonths();
        $this->loadData();
    }

    public function updatedFilterMode(): void
    {
        $this->resetExpanded();
        $this->loadData();
    }

    public function updatedSelectedYear(): void
    {
        $this->loadAvailableMonths();
        $this->resetExpanded();
        $this->loadData();
    }

    public function updatedSelectedMonth(): void
    {
        $this->resetExpanded();
        $this->loadData();
    }

    public string $selectedMonthYear = '';

    public function updatedSelectedMonthYear(): void
    {
        if (!empty($this->selectedMonthYear)) {
            $parts = explode('-', $this->selectedMonthYear);
            $this->selectedYear = $parts[0];
            $this->selectedMonth = (int) $parts[1];
        }
        $this->resetExpanded();
        $this->loadData();
    }

    public function updatedStartDate(): void
    {
        $this->resetExpanded();
        $this->loadData();
    }

    public function updatedEndDate(): void
    {
        $this->resetExpanded();
        $this->loadData();
    }

    public function updatedRegion(): void
    {
        $this->resetExpanded();
        $this->loadData();
    }

    public function updatedSortBy(): void
    {
        $this->loadData();
    }

    public function updatedCategoryFilter(): void
    {
        $this->resetExpanded();
        $this->loadData();
    }

    public function updatedReasonFilter(): void
    {
        $this->resetExpanded();
        $this->loadData();
    }

    public function updatedSearch(): void
    {
        $this->resetExpanded();
        $this->loadData();
    }

    private function resetExpanded(): void
    {
        $this->expandedYearMonths = [];
        $this->expandedCompanies = [];
    }

    private function getDateRange(): array
    {
        if ($this->filterMode === 'all') {
            $minYear = !empty($this->availableYears) ? min($this->availableYears) : date('Y');
            $maxYear = !empty($this->availableYears) ? max($this->availableYears) : date('Y');
            return ["{$minYear}-01-01", "{$maxYear}-12-31"];
        }

        if ($this->filterMode === 'range') {
            return [
                $this->startDate ?: now()->startOfMonth()->format('Y-m-d'),
                $this->endDate ?: now()->format('Y-m-d'),
            ];
        }

        if ($this->filterMode === 'year') {
            $start = Carbon::create($this->selectedYear, 1, 1)->startOfYear();
            $end = $start->copy()->endOfYear();
            return [$start->format('Y-m-d'), $end->format('Y-m-d')];
        }

        // Month mode
        $start = Carbon::create($this->selectedYear, $this->selectedMonth, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();
        return [$start->format('Y-m-d'), $end->format('Y-m-d')];
    }

    public function loadData(): void
    {
        [$dateFrom, $dateTo] = $this->getDateRange();
        $searchTerm = trim($this->search);

        // Join with latest PAID license expiry date per company
        // Only include companies where ALL PAID licenses have expired (no active/future PAID licenses)
        $today = now()->format('Y-m-d');
        $allowedProducts = implode(',', array_map(fn ($p) => "'" . addslashes($p) . "'", self::$allowedProducts));
        $query = DB::connection('frontenddb')
            ->table('crm_customer as c')
            ->leftJoin('crm_company_listing as cl', 'c.company_id', '=', 'cl.f_company_id')
            ->leftJoin('crm_reseller_link as rl', 'c.company_id', '=', 'rl.f_id')
            ->leftJoin('crm_customer as reseller', 'rl.reseller_id', '=', 'reseller.company_id')
            ->leftJoin(DB::raw("(SELECT f_company_id, MAX(f_expiry_date) as latest_expiry FROM crm_expiring_license WHERE f_type = 'PAID' AND f_name IN ({$allowedProducts}) GROUP BY f_company_id) as lic"), 'c.company_id', '=', 'lic.f_company_id')
            ->where('c.f_status', 'I')
            ->whereNotNull('c.suspend_date')
            ->where(function ($q) {
                $q->whereNull('c.f_login')
                  ->orWhere('c.f_login', 'NOT LIKE', '%@epicamera.com');
            })
            ->where(function ($q) {
                $q->whereRaw('LOWER(c.f_company_name) NOT LIKE ?', ['ft %'])
                  ->whereRaw('LOWER(c.f_company_name) NOT LIKE ?', ['ft.%'])
                  ->whereRaw('LOWER(c.f_company_name) NOT LIKE ?', ['fttest%'])
                  ->whereRaw('LOWER(c.f_company_name) NOT LIKE ?', ['%testing account%'])
                  ->whereRaw('LOWER(c.f_company_name) NOT LIKE ?', ['test %'])
                  ->whereRaw('LOWER(c.f_company_name) NOT LIKE ?', ['demo %'])
                  ->whereRaw('LOWER(c.f_company_name) NOT LIKE ?', ['% demo'])
                  ->whereRaw('LOWER(c.f_company_name) NOT LIKE ?', ['%poc -%'])
                  ->whereRaw('LOWER(c.f_company_name) NOT LIKE ?', ['%old not used%']);
            })
            ->whereBetween(DB::raw("CONVERT_TZ(c.suspend_date, '+00:00', '+08:00')"), [$dateFrom, $dateTo])
            ->select('c.company_id', 'c.f_company_name', 'c.f_company_type', 'c.f_modified_time', 'c.f_login', 'c.f_fullname', 'c.suspend_date', 'cl.f_country', 'lic.latest_expiry', 'reseller.f_company_type as reseller_type', 'rl.reseller_name');

        // Region filter
        if ($this->region === 'malaysia') {
            $query->where('cl.f_country', 'Malaysia');
        } elseif ($this->region === 'overseas') {
            $query->where(function ($q) {
                $q->where('cl.f_country', '!=', 'Malaysia')
                  ->orWhereNull('cl.f_country');
            });
        }

        if (!empty($searchTerm)) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('c.f_company_name', 'like', "%{$searchTerm}%")
                  ->orWhere('c.company_id', 'like', "%{$searchTerm}%")
                  ->orWhere('c.f_login', 'like', "%{$searchTerm}%");
            });
        }

        $companies = $query->orderBy('c.f_company_name', 'asc')->get();

        // Batch fetch PAID licenses only per company + per-module breakdown
        // Exclude: companies with no licenses, companies with only TRIAL licenses
        $companyIds = $companies->pluck('company_id')->toArray();
        $headcountMap = [];
        $moduleStats = ['TA' => ['headcount' => 0, 'companies' => []], 'TL' => ['headcount' => 0, 'companies' => []], 'TC' => ['headcount' => 0, 'companies' => []], 'TP' => ['headcount' => 0, 'companies' => []]];
        $companiesWithPaidLicense = [];
        $allLicenses = collect();

        if (!empty($companyIds)) {
            // Fetch all PAID licenses with allowed products
            $rawLicenses = DB::connection('frontenddb')
                ->table('crm_expiring_license')
                ->whereIn('f_company_id', $companyIds)
                ->where('f_type', 'PAID')
                ->whereIn('f_name', self::$allowedProducts)
                ->select('f_company_id', 'f_name', 'f_total_user', 'f_invoice_no', 'f_expiry_date')
                ->get();

            // For each company: find the latest end_date, then collect every invoice
            // that has at least one license ending on that same date. Companies with
            // multiple aligned invoices (e.g. add-on user packs) will end up with
            // several invoice_nos here.
            $latestInvoiceMap = [];   // [company_id => [invoice_no, ...]]
            foreach ($rawLicenses->groupBy('f_company_id') as $cid => $licenses) {
                $withInvoice = $licenses->whereNotNull('f_invoice_no')
                    ->where('f_invoice_no', '!=', '');
                $latestDate = $withInvoice->max('f_expiry_date');
                if (!$latestDate) {
                    continue;
                }
                $latestInvoiceMap[$cid] = $withInvoice
                    ->where('f_expiry_date', $latestDate)
                    ->pluck('f_invoice_no')
                    ->unique()
                    ->values()
                    ->all();
            }

            // Keep all licenses belonging to any invoice in that company's latest-date set
            $allLicenses = $rawLicenses->filter(function ($lic) use ($latestInvoiceMap) {
                $invoices = $latestInvoiceMap[$lic->f_company_id] ?? [];
                return !empty($invoices) && in_array($lic->f_invoice_no, $invoices, true);
            });

            // Headcount = sum across all included invoices; module_count = distinct invoice count
            foreach ($allLicenses->groupBy('f_company_id') as $cid => $licenses) {
                $headcountMap[$cid] = [
                    'total_headcount' => $licenses->sum('f_total_user'),
                    'module_count' => $licenses->pluck('f_invoice_no')->unique()->count(),
                ];
                $companiesWithPaidLicense[$cid] = true;
            }

            // Build module stats
            $moduleKeywords = [
                'TA' => ['TimeTec TA', 'TimeTec Attendance'],
                'TL' => ['TimeTec Leave'],
                'TC' => ['TimeTec Claim'],
                'TP' => ['TimeTec Payroll'],
            ];

            foreach ($allLicenses as $lic) {
                foreach ($moduleKeywords as $key => $keywords) {
                    foreach ($keywords as $kw) {
                        if (str_contains($lic->f_name ?? '', $kw)) {
                            $moduleStats[$key]['headcount'] += (int) $lic->f_total_user;
                            $moduleStats[$key]['companies'][$lic->f_company_id] = true;
                            break 2;
                        }
                    }
                }
            }
        }

        // Load termination notes
        $notesMap = TerminationAnalysisNote::whereIn('company_id', $companies->pluck('company_id')->toArray())
            ->get()
            ->keyBy('company_id');

        // Excluded company IDs
        $excludedIds = $notesMap->filter(fn ($n) => $n->is_excluded)->keys()->toArray();

        // Apply category filter
        if ($this->categoryFilter !== 'all') {
            $companies = $companies->filter(function ($c) {
                $rType = strtoupper($c->reseller_type ?? '');
                return match ($this->categoryFilter) {
                    'dealer' => $rType === 'DEALER',
                    'distributor' => $rType === 'DISTRIBUTOR',
                    'end_user' => $rType !== 'DEALER' && $rType !== 'DISTRIBUTOR',
                    default => true,
                };
            });
        }

        // Apply reason filter
        if ($this->reasonFilter !== 'all') {
            $companies = $companies->filter(function ($c) use ($notesMap) {
                $note = $notesMap[$c->company_id] ?? null;
                $hasReason = $note && !empty($note->termination_reason);
                return $this->reasonFilter === 'completed' ? $hasReason : !$hasReason;
            });
        }

        // Category breakdown (End User / Dealer / Distributor) — exclude excluded companies
        $categoryStats = ['end_user' => 0, 'dealer' => 0, 'distributor' => 0];
        foreach ($companies as $company) {
            if (in_array($company->company_id, $excludedIds)) continue;
            $rType = strtoupper($company->reseller_type ?? '');
            if ($rType === 'DEALER') {
                $categoryStats['dealer']++;
            } elseif ($rType === 'DISTRIBUTOR') {
                $categoryStats['distributor']++;
            } else {
                $categoryStats['end_user']++;
            }
        }

        // Recalculate module stats based on filtered companies (excludes excluded + category/reason filtered)
        $filteredCompanyIds = $companies->pluck('company_id')->toArray();
        $moduleStats = ['TA' => ['headcount' => 0, 'companies' => []], 'TL' => ['headcount' => 0, 'companies' => []], 'TC' => ['headcount' => 0, 'companies' => []], 'TP' => ['headcount' => 0, 'companies' => []]];
        $moduleKeywordsForStats = [
            'TA' => ['TimeTec TA', 'TimeTec Attendance'],
            'TL' => ['TimeTec Leave'],
            'TC' => ['TimeTec Claim'],
            'TP' => ['TimeTec Payroll'],
        ];
        foreach ($allLicenses as $lic) {
            if (!in_array($lic->f_company_id, $filteredCompanyIds)) continue;
            if (in_array($lic->f_company_id, $excludedIds)) continue;
            foreach ($moduleKeywordsForStats as $key => $keywords) {
                foreach ($keywords as $kw) {
                    if (str_contains($lic->f_name ?? '', $kw)) {
                        $moduleStats[$key]['headcount'] += (int) $lic->f_total_user;
                        $moduleStats[$key]['companies'][$lic->f_company_id] = true;
                        break 2;
                    }
                }
            }
        }

        // Group by year-month
        $grouped = [];
        $totalHeadcount = 0;
        $totalTerminated = 0;
        foreach ($companies as $company) {
            $date = Carbon::parse($company->suspend_date)->setTimezone('Asia/Kuala_Lumpur');
            $yearMonth = $date->format('Y-m');
            $monthLabel = $date->format('Y') . ' - ' . $date->format('F');

            if (!isset($grouped[$yearMonth])) {
                $grouped[$yearMonth] = [
                    'label' => $monthLabel,
                    'year_month' => $yearMonth,
                    'companies' => [],
                    'count' => 0,
                    'total_headcount' => 0,
                ];
            }

            $companyHeadcount = $headcountMap[$company->company_id]['total_headcount'] ?? 0;
            $moduleCount = $headcountMap[$company->company_id]['module_count'] ?? 0;
            $note = $notesMap[$company->company_id] ?? null;
            $isExcluded = $note->is_excluded ?? false;

            $grouped[$yearMonth]['companies'][] = [
                'company_id' => $company->company_id,
                'company_name' => $company->f_company_name,
                'company_type' => $company->f_company_type,
                'terminated_date' => Carbon::parse($company->f_modified_time)->format('d/m/Y'),
                'suspend_date' => $date->format('d/m/Y H:i'),
                'license_expiry' => $date->format('d/m/Y'),
                'license_expiry_raw' => $date->format('Y-m-d'),
                'login' => $company->f_login,
                'fullname' => $company->f_fullname,
                'total_headcount' => $companyHeadcount,
                'module_count' => $moduleCount,
                'country' => $company->f_country ?? '-',
                'reseller_type' => $company->reseller_type ?? null,
                'reseller_name' => $company->reseller_name ?? null,
                'is_excluded' => $isExcluded,
                'exclude_reason' => $note->exclude_reason ?? null,
                'termination_reason' => $note->termination_reason ?? null,
            ];

            // Only count non-excluded for summary
            if (!$isExcluded) {
                $grouped[$yearMonth]['count']++;
                $grouped[$yearMonth]['total_headcount'] += $companyHeadcount;
                $totalHeadcount += $companyHeadcount;
                $totalTerminated++;
            }
        }

        krsort($grouped);

        // Sort companies within each month group
        foreach ($grouped as &$monthData) {
            usort($monthData['companies'], function ($a, $b) {
                return match ($this->sortBy) {
                    'name_asc' => strcasecmp($a['company_name'], $b['company_name']),
                    'name_desc' => strcasecmp($b['company_name'], $a['company_name']),
                    'expiry_asc' => strcmp($a['suspend_date'] ?? '', $b['suspend_date'] ?? ''),
                    'expiry_desc' => strcmp($b['suspend_date'] ?? '', $a['suspend_date'] ?? ''),
                    default => strcasecmp($a['company_name'], $b['company_name']),
                };
            });
        }
        unset($monthData);

        $this->groupedData = $grouped;

        // Default expand all year-months
        $this->expandedYearMonths = array_keys($grouped);

        $this->summary = [
            'total_terminated' => $totalTerminated,
            'total_headcount' => $totalHeadcount,
            'date_from' => Carbon::parse($dateFrom)->format('d M Y'),
            'date_to' => Carbon::parse($dateTo)->format('d M Y'),
            'modules' => [
                'TA' => ['headcount' => $moduleStats['TA']['headcount'], 'companies' => count($moduleStats['TA']['companies'])],
                'TL' => ['headcount' => $moduleStats['TL']['headcount'], 'companies' => count($moduleStats['TL']['companies'])],
                'TC' => ['headcount' => $moduleStats['TC']['headcount'], 'companies' => count($moduleStats['TC']['companies'])],
                'TP' => ['headcount' => $moduleStats['TP']['headcount'], 'companies' => count($moduleStats['TP']['companies'])],
            ],
            'categories' => $categoryStats,
        ];

        // Build chart data — granularity depends on date range span
        $moduleKeywords = [
            'TA' => ['TimeTec TA', 'TimeTec Attendance'],
            'TL' => ['TimeTec Leave'],
            'TC' => ['TimeTec Claim'],
            'TP' => ['TimeTec Payroll'],
        ];

        $chartStart = Carbon::parse($dateFrom);
        $chartEnd = Carbon::parse($dateTo);
        $daysDiff = $chartStart->diffInDays($chartEnd);

        // Determine granularity: daily (<= 60 days), monthly (61-730 days ~2 years), yearly (> 730 days)
        $granularity = 'daily';
        if ($daysDiff > 730) {
            $granularity = 'yearly';
        } elseif ($daysDiff > 60) {
            $granularity = 'monthly';
        }

        // Build slots
        $slots = [];
        if ($granularity === 'daily') {
            $cursor = $chartStart->copy();
            while ($cursor->lte($chartEnd)) {
                $slots[] = $cursor->format('Y-m-d');
                $cursor->addDay();
            }
        } elseif ($granularity === 'monthly') {
            $cursor = $chartStart->copy()->startOfMonth();
            while ($cursor->lte($chartEnd)) {
                $slots[] = $cursor->format('Y-m');
                $cursor->addMonth();
            }
        } else {
            $cursor = $chartStart->copy()->startOfYear();
            while ($cursor->lte($chartEnd)) {
                $slots[] = $cursor->format('Y');
                $cursor->addYear();
            }
        }

        // Build a lookup: group companies by their slot key (exclude excluded companies)
        $slotCompanies = [];
        foreach ($companies as $company) {
            if (in_array($company->company_id, $excludedIds)) continue;
            $expiryDate = Carbon::parse($company->suspend_date)->setTimezone('Asia/Kuala_Lumpur');
            $key = match ($granularity) {
                'daily' => $expiryDate->format('Y-m-d'),
                'monthly' => $expiryDate->format('Y-m'),
                'yearly' => $expiryDate->format('Y'),
            };
            $slotCompanies[$key][] = $company->company_id;
        }

        $chartPoints = [];
        foreach ($slots as $slot) {
            $slotIds = $slotCompanies[$slot] ?? [];
            $slotModules = ['TA' => 0, 'TL' => 0, 'TC' => 0, 'TP' => 0];
            $slotHeadcount = 0;

            if (!empty($slotIds)) {
                $slotLicenses = $allLicenses->whereIn('f_company_id', $slotIds);
                foreach ($slotLicenses as $lic) {
                    $slotHeadcount += (int) $lic->f_total_user;
                    foreach ($moduleKeywords as $key => $keywords) {
                        foreach ($keywords as $kw) {
                            if (str_contains($lic->f_name ?? '', $kw)) {
                                $slotModules[$key] += (int) $lic->f_total_user;
                                break 2;
                            }
                        }
                    }
                }
            }

            $label = match ($granularity) {
                'daily' => Carbon::parse($slot)->format('d M'),
                'monthly' => Carbon::parse($slot . '-01')->format('M Y'),
                'yearly' => $slot,
            };

            $chartPoints[] = [
                'label' => $label,
                'companies' => count($slotIds),
                'headcount' => $slotHeadcount,
                'ta' => $slotModules['TA'],
                'tl' => $slotModules['TL'],
                'tc' => $slotModules['TC'],
                'tp' => $slotModules['TP'],
            ];
        }

        $this->chartData = $chartPoints;
    }

    private function loadAvailableYears(): void
    {
        $today = now()->format('Y-m-d');
        $allowedProducts = implode(',', array_map(fn ($p) => "'" . addslashes($p) . "'", self::$allowedProducts));
        $years = DB::connection('frontenddb')
            ->table('crm_customer as c')
            ->leftJoin(DB::raw("(SELECT f_company_id, MAX(f_expiry_date) as latest_expiry FROM crm_expiring_license WHERE f_type = 'PAID' AND f_name IN ({$allowedProducts}) GROUP BY f_company_id) as lic"), 'c.company_id', '=', 'lic.f_company_id')
            ->where('c.f_status', 'I')
            ->whereNotNull('c.suspend_date')
            ->where(function ($q) {
                $q->whereNull('c.f_login')
                  ->orWhere('c.f_login', 'NOT LIKE', '%@epicamera.com');
            })
            ->where(function ($q) {
                $q->whereRaw('LOWER(c.f_company_name) NOT LIKE ?', ['ft %'])
                  ->whereRaw('LOWER(c.f_company_name) NOT LIKE ?', ['ft.%'])
                  ->whereRaw('LOWER(c.f_company_name) NOT LIKE ?', ['fttest%'])
                  ->whereRaw('LOWER(c.f_company_name) NOT LIKE ?', ['%testing account%'])
                  ->whereRaw('LOWER(c.f_company_name) NOT LIKE ?', ['test %'])
                  ->whereRaw('LOWER(c.f_company_name) NOT LIKE ?', ['demo %'])
                  ->whereRaw('LOWER(c.f_company_name) NOT LIKE ?', ['% demo'])
                  ->whereRaw('LOWER(c.f_company_name) NOT LIKE ?', ['%poc -%'])
                  ->whereRaw('LOWER(c.f_company_name) NOT LIKE ?', ['%old not used%']);
            })
            ->selectRaw('DISTINCT YEAR(CONVERT_TZ(c.suspend_date, \'+00:00\', \'+08:00\')) as year')
            ->orderByDesc('year')
            ->pluck('year')
            ->map(fn ($y) => (string) $y)
            ->toArray();

        // Ensure current year is included
        $currentYear = (string) date('Y');
        if (!in_array($currentYear, $years)) {
            array_unshift($years, $currentYear);
        }

        $this->availableYears = $years;
    }

    private function loadAvailableMonths(): void
    {
        $this->availableMonths = [];
        for ($i = 1; $i <= 12; $i++) {
            $this->availableMonths[] = [
                'value' => $i,
                'label' => Carbon::create(null, $i, 1)->format('F'),
            ];
        }
    }

    public function toggleChartDataset(int $index): void
    {
        if (in_array($index, $this->hiddenChartDatasets)) {
            $this->hiddenChartDatasets = array_values(array_diff($this->hiddenChartDatasets, [$index]));
        } else {
            $this->hiddenChartDatasets[] = $index;
        }
    }

    public function toggleYearMonth(string $yearMonth): void
    {
        if (in_array($yearMonth, $this->expandedYearMonths)) {
            $this->expandedYearMonths = array_values(array_diff($this->expandedYearMonths, [$yearMonth]));
        } else {
            $this->expandedYearMonths[] = $yearMonth;
        }
    }

    public function toggleCompany(string $companyId): void
    {
        if (in_array($companyId, $this->expandedCompanies)) {
            $this->expandedCompanies = array_values(array_diff($this->expandedCompanies, [$companyId]));
        } else {
            $this->expandedCompanies[] = $companyId;
        }
    }

    public function getCompanyLicenses(string $companyId): array
    {
        // Find the company's latest expiry date
        $latestDate = DB::connection('frontenddb')
            ->table('crm_expiring_license')
            ->where('f_company_id', $companyId)
            ->where('f_type', 'PAID')
            ->whereIn('f_name', self::$allowedProducts)
            ->whereNotNull('f_invoice_no')
            ->where('f_invoice_no', '!=', '')
            ->max('f_expiry_date');

        if (!$latestDate) {
            return [];
        }

        // Every invoice with at least one license ending on that date
        $latestInvoices = DB::connection('frontenddb')
            ->table('crm_expiring_license')
            ->where('f_company_id', $companyId)
            ->where('f_type', 'PAID')
            ->whereIn('f_name', self::$allowedProducts)
            ->where('f_expiry_date', $latestDate)
            ->whereNotNull('f_invoice_no')
            ->where('f_invoice_no', '!=', '')
            ->distinct()
            ->pluck('f_invoice_no')
            ->all();

        if (empty($latestInvoices)) {
            return [];
        }

        // All license rows for those invoices, ordered for stable display
        $licenses = DB::connection('frontenddb')
            ->table('crm_expiring_license')
            ->where('f_company_id', $companyId)
            ->where('f_type', 'PAID')
            ->whereIn('f_invoice_no', $latestInvoices)
            ->whereIn('f_name', self::$allowedProducts)
            ->select('f_invoice_no', 'f_name', 'f_start_date', 'f_expiry_date')
            ->orderBy('f_invoice_no')
            ->orderBy('f_expiry_date', 'desc')
            ->get();

        // Batch fetch invoice URLs
        $invoiceNos = $licenses->pluck('f_invoice_no')->filter()->unique()->values()->toArray();
        $invoiceIdMap = [];
        if (!empty($invoiceNos)) {
            $invoiceDetails = DB::connection('frontenddb')
                ->table('crm_invoice_details')
                ->whereIn('f_invoice_no', $invoiceNos)
                ->get(['f_id', 'f_invoice_no']);

            $aesKey = 'Epicamera@99';
            foreach ($invoiceDetails as $detail) {
                if ($detail->f_id && !isset($invoiceIdMap[$detail->f_invoice_no])) {
                    try {
                        $encrypted = openssl_encrypt($detail->f_id, 'AES-128-ECB', $aesKey);
                        $invoiceIdMap[$detail->f_invoice_no] = 'https://www.timeteccloud.com/paypal_reseller_invoice?iIn=' . base64_encode($encrypted);
                    } catch (\Exception $e) {
                        // Skip if encryption fails
                    }
                }
            }
        }

        return $licenses->map(fn ($l) => [
            'invoice_no' => $l->f_invoice_no ?? '-',
            'invoice_url' => $invoiceIdMap[$l->f_invoice_no] ?? null,
            'name' => $l->f_name,
            'start_date' => $l->f_start_date ? Carbon::parse($l->f_start_date)->format('j F Y') : '-',
            'expiry_date' => $l->f_expiry_date ? Carbon::parse($l->f_expiry_date)->format('j F Y') : '-',
        ])->toArray();
    }

    public function exportExcel()
    {
        $rows = [];

        // Build yearly summary from current groupedData
        $yearlyData = [];
        foreach ($this->groupedData as $yearMonth => $monthData) {
            $year = substr($yearMonth, 0, 4);
            if (!isset($yearlyData[$year])) {
                $yearlyData[$year] = [
                    'total' => 0,
                    'malaysia' => 0,
                    'overseas' => 0,
                    'end_user' => 0,
                    'dealer' => 0,
                    'distributor' => 0,
                ];
            }

            foreach ($monthData['companies'] as $company) {
                if ($company['is_excluded'] ?? false) continue;

                $yearlyData[$year]['total']++;

                $country = $company['country'] ?? '-';
                if ($country === 'Malaysia') {
                    $yearlyData[$year]['malaysia']++;
                } else {
                    $yearlyData[$year]['overseas']++;
                }

                $rType = strtoupper($company['reseller_type'] ?? '');
                if ($rType === 'DEALER') {
                    $yearlyData[$year]['dealer']++;
                } elseif ($rType === 'DISTRIBUTOR') {
                    $yearlyData[$year]['distributor']++;
                } else {
                    $yearlyData[$year]['end_user']++;
                }
            }
        }

        krsort($yearlyData);

        // Header
        $rows[] = ['Year', 'Total Terminated', 'Malaysia', 'Overseas', 'Total Terminated', 'End User', 'Dealer', 'Distributor'];

        foreach ($yearlyData as $year => $data) {
            $rows[] = [
                $year,
                $data['total'],
                $data['malaysia'],
                $data['overseas'],
                $data['total'],
                $data['end_user'],
                $data['dealer'],
                $data['distributor'],
            ];
        }

        // Totals row
        $totals = ['Total', 0, 0, 0, 0, 0, 0, 0];
        foreach ($yearlyData as $data) {
            $totals[1] += $data['total'];
            $totals[2] += $data['malaysia'];
            $totals[3] += $data['overseas'];
            $totals[4] += $data['total'];
            $totals[5] += $data['end_user'];
            $totals[6] += $data['dealer'];
            $totals[7] += $data['distributor'];
        }
        $rows[] = $totals;

        $fileName = 'termination_analysis_' . now()->format('Ymd_His') . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(new class($rows) implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithHeadings, \Maatwebsite\Excel\Concerns\WithStyles {
            private array $rows;

            public function __construct(array $rows)
            {
                $this->rows = $rows;
            }

            public function array(): array
            {
                return array_slice($this->rows, 1); // exclude header since WithHeadings adds it
            }

            public function headings(): array
            {
                return $this->rows[0];
            }

            public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet)
            {
                return [
                    1 => ['font' => ['bold' => true]],
                ];
            }
        }, $fileName);
    }

    public function refreshData(): void
    {
        Cache::forget('termination_available_years');
        $this->loadAvailableYears();
        $this->loadData();

        \Filament\Notifications\Notification::make()
            ->title('Data refreshed')
            ->success()
            ->send();
    }

    public function generateForecastCost(): void
    {
        $headcount = (int) ($this->summary['total_headcount'] ?? 0);
        $rate      = 1;
        $months    = 12;

        $modules = [];
        foreach (['TA', 'TL', 'TC', 'TP'] as $key) {
            $modHc = (int) ($this->summary['modules'][$key]['headcount'] ?? 0);
            $modules[$key] = [
                'headcount' => $modHc,
                'cost'      => $modHc * $rate * $months,
            ];
        }

        $this->forecastData = [
            'headcount' => $headcount,
            'rate'      => $rate,
            'months'    => $months,
            'total'     => $headcount * $rate * $months,
            'modules'   => $modules,
        ];
        $this->showForecastModal = true;
    }

    public function closeForecastModal(): void
    {
        $this->showForecastModal = false;
    }

    public function openReasonModal(string $companyId): void
    {
        $this->modalCompanyId = $companyId;
        $note = TerminationAnalysisNote::where('company_id', $companyId)->first();
        $this->modalReasonText = $note->termination_reason ?? '';

        // Find company name from grouped data
        $this->modalCompanyName = '';
        foreach ($this->groupedData as $monthData) {
            foreach ($monthData['companies'] as $company) {
                if ($company['company_id'] === $companyId) {
                    $this->modalCompanyName = $company['company_name'];
                    break 2;
                }
            }
        }

        $this->showReasonModal = true;
        $this->showViewReasonModal = false;
    }

    public function openViewReasonModal(string $companyId): void
    {
        $this->modalCompanyId = $companyId;
        $note = TerminationAnalysisNote::where('company_id', $companyId)->first();
        $this->modalReasonText = $note->termination_reason ?? '';

        $this->modalCompanyName = '';
        foreach ($this->groupedData as $monthData) {
            foreach ($monthData['companies'] as $company) {
                if ($company['company_id'] === $companyId) {
                    $this->modalCompanyName = $company['company_name'];
                    break 2;
                }
            }
        }

        $this->showViewReasonModal = true;
        $this->showReasonModal = false;
    }

    public function submitTerminationReason(): void
    {
        if (!empty(trim($this->modalReasonText))) {
            $this->saveTerminationReason($this->modalCompanyId, $this->modalReasonText);
        }
        $this->closeModals();
    }

    public function closeModals(): void
    {
        $this->showReasonModal = false;
        $this->showViewReasonModal = false;
        $this->modalCompanyId = '';
        $this->modalCompanyName = '';
        $this->modalReasonText = '';
    }

    public function excludeCompany(string $companyId): void
    {
        TerminationAnalysisNote::updateOrCreate(
            ['company_id' => $companyId],
            [
                'is_excluded' => true,
                'updated_by' => auth()->id(),
            ]
        );

        $this->loadData();

        \Filament\Notifications\Notification::make()
            ->title('Company excluded from analysis')
            ->success()
            ->send();
    }

    public function includeCompany(string $companyId): void
    {
        TerminationAnalysisNote::updateOrCreate(
            ['company_id' => $companyId],
            [
                'is_excluded' => false,
                'exclude_reason' => null,
                'updated_by' => auth()->id(),
            ]
        );

        $this->loadData();

        \Filament\Notifications\Notification::make()
            ->title('Company included back in analysis')
            ->success()
            ->send();
    }

    public function saveTerminationReason(string $companyId, string $reason): void
    {
        TerminationAnalysisNote::updateOrCreate(
            ['company_id' => $companyId],
            [
                'termination_reason' => strtoupper($reason),
                'updated_by' => auth()->id(),
            ]
        );

        $this->loadData();

        \Filament\Notifications\Notification::make()
            ->title('Termination reason saved')
            ->success()
            ->send();
    }
}
