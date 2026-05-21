<?php

namespace App\Filament\Pages;

use App\Models\Ticket;
use App\Models\TicketPriority;
use App\Models\TicketModule;
use App\Exports\TicketAnalysisExport;
use Filament\Pages\Page;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\TicketingUser;

class TicketAnalysis extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';
    protected static string $view = 'filament.pages.ticket-analysis';
    protected static ?string $navigationLabel = 'Ticket Analysis';
    protected static ?string $title = '';
    protected static ?string $slug = 'ticket-analysis';
    protected static ?int $navigationSort = 4;
    protected static bool $shouldRegisterNavigation = false;

    // Tab property
    public $activeTab = 'all'; // 'all', 'rnd', 'fe'

    // Rnd tab data
    public $rndTableData = [];
    public $rndP2TableData = [];
    public $p1PriorityId = null;
    public $p2PriorityId = null;

    // FE tab data
    public $feTableData = [];
    public $feP2TableData = [];

    // Filter properties
    public $filterMode = 'month'; // 'range' or 'month'
    public $startDate;
    public $endDate;
    public $selectedMonth;
    public $selectedYear;
    public $selectedProduct = 'v1';
    public $showAllTickets = false;

    // Trend chart filter
    public $trendStartDate;
    public $trendEndDate;

    // Summary stats
    public $totalTickets = 0;
    public $openTickets = 0;
    public $completedTickets = 0;
    public $avgResolutionDays = 0;

    // Available filter options
    public $availableYears = [];
    public $availableMonths = [];

    // Chart data
    public $priorityData = [];
    public $moduleData = [];
    public $priorityModuleData = []; // Priority with module breakdown for bar chart
    public $durationData = [];

    // Frontend tickets data
    public $frontendTotalTickets = 0;
    public $frontendUserData = [];

    // Selected frontend user chart data
    public $selectedFrontendUserId = null;
    public $selectedFrontendUserName = '';
    public $selectedFrontendUserModuleData = [];

    // Slide-over modal
    public $showSlideOver = false;
    public $ticketList = [];
    public $ticketsByPriority = [];
    public $slideOverTitle = 'Tickets';
    public $focusPriorityId = null;

    protected $listeners = [
        'ticket-status-updated' => '$refresh',
    ];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (!$user || !($user instanceof \App\Models\User)) {
            return false;
        }

        return $user->hasRouteAccess('filament.admin.pages.ticket-analysis');
    }

    public function mount()
    {
        // Default: current month/year
        $this->selectedMonth = Carbon::now()->month;
        $this->selectedYear = Carbon::now()->year;

        // Default date range: 1st of current month to today
        $this->endDate = Carbon::now()->format('Y-m-d');
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');

        // Default trend chart date range: last 3 months
        $this->trendEndDate = Carbon::now()->format('Y-m-d');
        $this->trendStartDate = Carbon::now()->subMonths(3)->format('Y-m-d');

        $this->loadAvailableYears();
        $this->loadAvailableMonths();

        // Reset to first available month if current month has no data
        if (!in_array($this->selectedMonth, array_column($this->availableMonths, 'value'))) {
            $this->selectedMonth = !empty($this->availableMonths) ? $this->availableMonths[0]['value'] : Carbon::now()->month;
        }

        $this->loadData();
    }

    public function updatedStartDate($value)
    {
        $this->startDate = $value;
        $this->loadData();
    }

    public function updatedEndDate($value)
    {
        $this->endDate = $value;
        $this->loadData();
    }

    public function updatedFilterMode($value)
    {
        $this->filterMode = $value;
        $this->loadData();
    }

    public function updatedSelectedMonth($value)
    {
        $this->selectedMonth = (int) $value;
        $this->loadData();
    }

    public function updatedSelectedYear($value)
    {
        $this->selectedYear = (int) $value;
        $this->loadAvailableMonths();

        // If current selected month has no data for this year, pick the latest available
        if (!in_array($this->selectedMonth, array_column($this->availableMonths, 'value'))) {
            $this->selectedMonth = !empty($this->availableMonths) ? $this->availableMonths[0]['value'] : Carbon::now()->month;
        }

        $this->loadData();
    }

    public function updatedSelectedProduct($value)
    {
        $this->selectedProduct = $value;
        $this->loadAvailableYears();
        $this->loadAvailableMonths();

        // Reset to latest available month if current selection has no data
        if (!in_array($this->selectedMonth, array_column($this->availableMonths, 'value'))) {
            $this->selectedMonth = !empty($this->availableMonths) ? $this->availableMonths[0]['value'] : Carbon::now()->month;
        }

        $this->loadData();
    }

    private function loadAvailableYears()
    {
        $productId = $this->selectedProduct === 'v2' ? 2 : 1;

        $this->availableYears = Ticket::where('product_id', $productId)
            ->selectRaw('YEAR(created_at) as year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year')
            ->toArray();

        // Always include current year
        if (!in_array(Carbon::now()->year, $this->availableYears)) {
            array_unshift($this->availableYears, Carbon::now()->year);
        }
    }

    private function loadAvailableMonths()
    {
        $productId = $this->selectedProduct === 'v2' ? 2 : 1;

        $months = Ticket::where('product_id', $productId)
            ->whereYear('created_at', $this->selectedYear)
            ->selectRaw('MONTH(created_at) as month')
            ->distinct()
            ->orderByDesc('month')
            ->pluck('month')
            ->toArray();

        $this->availableMonths = collect($months)->map(function ($m) {
            return [
                'value' => $m,
                'label' => Carbon::create()->month($m)->format('F'),
            ];
        })->values()->toArray();
    }

    // Generic filter method for wire:change
    public function applyFilters()
    {
        $this->loadData();
    }

    private function getBaseQuery()
    {
        $query = Ticket::query();

        // Product filter - V1 or V2 only (no merge)
        if ($this->selectedProduct === 'v2') {
            $query->where('product_id', 2);
        } else {
            $query->where('product_id', 1);
        }

        // Date filter (skip if showAllTickets is enabled)
        if (!$this->showAllTickets) {
            if ($this->filterMode === 'month') {
                $monthStart = Carbon::create($this->selectedYear, $this->selectedMonth, 1)->startOfMonth();
                $monthEnd = (clone $monthStart)->endOfMonth();
                $query->whereDate('created_at', '>=', $monthStart)
                      ->whereDate('created_at', '<=', $monthEnd);
            } else {
                if ($this->startDate) {
                    $query->whereDate('created_at', '>=', $this->startDate);
                }
                if ($this->endDate) {
                    $query->whereDate('created_at', '<=', $this->endDate);
                }
            }
        }

        return $query;
    }

    public function switchTab($tab): void
    {
        $this->activeTab = $tab;
        $this->loadTabData();
    }

    private function loadTabData(): void
    {
        if ($this->activeTab === 'rnd') {
            $this->fetchRndData();
        } elseif ($this->activeTab === 'fe') {
            $this->fetchFeData();
        }
    }

    public function loadData()
    {
        $this->fetchSummaryStats();
        $this->fetchPriorityData();
        $this->fetchModuleData();
        $this->fetchPriorityModuleData();
        $this->fetchDurationData();
        $this->fetchFrontendData();
        $this->loadTabData();
    }

    private function fetchSummaryStats()
    {
        $query = $this->getBaseQuery();

        $this->totalTickets = (clone $query)->count();

        $this->openTickets = (clone $query)
            ->whereNotIn('status', ['Closed', 'Resolved'])
            ->count();

        $this->completedTickets = (clone $query)
            ->whereIn('status', ['Closed', 'Resolved'])
            ->count();

        // Average resolution time in minutes - based on Status Log
        // Find when each ticket was first marked as "Closed" from logs
        $closedTicketIds = (clone $query)
            ->where('status', 'Closed')
            ->pluck('id');

        if ($closedTicketIds->isNotEmpty()) {
            // Get first "Closed" log entry for each ticket and calculate duration
            $avgMinutes = DB::connection('ticketingsystem_live')
                ->table('ticket_logs as tl')
                ->join('tickets as t', 't.id', '=', 'tl.ticket_id')
                ->whereIn('tl.ticket_id', $closedTicketIds)
                ->where('tl.field_name', 'status')
                ->where('tl.new_value', 'Closed')
                ->where('tl.old_value', '!=', 'Closed')
                ->whereNotNull('t.created_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, t.created_at, tl.created_at)) as avg_minutes')
                ->value('avg_minutes');

            $this->avgResolutionDays = $this->formatDuration($avgMinutes ?? 0);
        } else {
            $this->avgResolutionDays = '00:00:00';
        }
    }

    /**
     * Format duration from minutes to DD:HH:MM format
     */
    private function formatDuration($totalMinutes): string
    {
        if ($totalMinutes <= 0) {
            return '00:00:00';
        }

        $totalMinutes = round($totalMinutes);
        $days = floor($totalMinutes / (60 * 24));
        $hours = floor(($totalMinutes % (60 * 24)) / 60);
        $minutes = $totalMinutes % 60;

        return sprintf('%02d:%02d:%02d', $days, $hours, $minutes);
    }

    private function fetchPriorityData()
    {
        $query = $this->getBaseQuery();

        $priorityCounts = (clone $query)
            ->select('priority_id', DB::raw('COUNT(*) as count'))
            ->whereNotNull('priority_id')
            ->groupBy('priority_id')
            ->get();

        // Get priority names and colors
        $priorities = TicketPriority::whereIn('id', $priorityCounts->pluck('priority_id'))->get()->keyBy('id');

        $this->priorityData = $priorityCounts->map(function ($item) use ($priorities) {
            $priority = $priorities->get($item->priority_id);
            return [
                'id' => $item->priority_id,
                'name' => $priority ? $priority->name : 'Unknown',
                'count' => $item->count,
                'percentage' => $this->totalTickets > 0 ? round(($item->count / $this->totalTickets) * 100, 1) : 0,
            ];
        })->sortByDesc('count')->values()->toArray();
    }

    private function fetchModuleData()
    {
        $query = $this->getBaseQuery();

        // Get top 10 modules by count
        $moduleCounts = (clone $query)
            ->select('module_id', DB::raw('COUNT(*) as count'))
            ->whereNotNull('module_id')
            ->groupBy('module_id')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        $moduleIds = $moduleCounts->pluck('module_id')->toArray();

        // Get module names
        $modules = TicketModule::whereIn('id', $moduleIds)->get()->keyBy('id');

        // Get all priorities for color mapping
        $priorities = TicketPriority::all()->keyBy('id');

        // Get breakdown by module and priority
        $moduleBreakdown = (clone $query)
            ->select('module_id', 'priority_id', DB::raw('COUNT(*) as count'))
            ->whereIn('module_id', $moduleIds)
            ->whereNotNull('priority_id')
            ->groupBy('module_id', 'priority_id')
            ->get()
            ->groupBy('module_id');

        $maxCount = $moduleCounts->max('count') ?? 1;

        // Priority colors mapping
        $priorityColors = [
            'Software Bugs' => '#EF4444',
            'Back End Assistance' => '#F59E0B',
            'Critical Enhancement' => '#8B5CF6',
            'Non-Critical Enhancement' => '#10B981',
            'Paid Customization' => '#3B82F6',
        ];

        $this->moduleData = $moduleCounts->map(function ($item) use ($modules, $maxCount, $moduleBreakdown, $priorities, $priorityColors) {
            $module = $modules->get($item->module_id);
            $breakdown = $moduleBreakdown->get($item->module_id, collect());

            // Build priority breakdown for this module
            $priorityBreakdown = $breakdown->map(function ($b) use ($priorities, $priorityColors, $item) {
                $priority = $priorities->get($b->priority_id);
                $name = $priority ? $priority->name : 'Unknown';
                return [
                    'priority_id' => $b->priority_id,
                    'name' => $name,
                    'count' => $b->count,
                    'percentage' => $item->count > 0 ? round(($b->count / $item->count) * 100, 1) : 0,
                    'color' => $priorityColors[$name] ?? '#6B7280',
                ];
            })->sortByDesc('count')->values()->toArray();

            return [
                'id' => $item->module_id,
                'name' => $module ? $module->name : 'Unknown',
                'count' => $item->count,
                'percentage' => round(($item->count / $maxCount) * 100, 1),
                'breakdown' => $priorityBreakdown,
            ];
        })->values()->toArray();
    }

    private function fetchPriorityModuleData()
    {
        $query = $this->getBaseQuery();

        // Get all priorities with counts
        $priorityCounts = (clone $query)
            ->select('priority_id', DB::raw('COUNT(*) as count'))
            ->whereNotNull('priority_id')
            ->groupBy('priority_id')
            ->orderByDesc('count')
            ->get();

        $priorityIds = $priorityCounts->pluck('priority_id')->toArray();

        // Get priority names
        $priorities = TicketPriority::whereIn('id', $priorityIds)->get()->keyBy('id');

        // Get all modules for name mapping
        $modules = TicketModule::all()->keyBy('id');

        // Get breakdown by priority and module
        $priorityBreakdown = (clone $query)
            ->select('priority_id', 'module_id', DB::raw('COUNT(*) as count'))
            ->whereIn('priority_id', $priorityIds)
            ->whereNotNull('module_id')
            ->groupBy('priority_id', 'module_id')
            ->get()
            ->groupBy('priority_id');

        $maxCount = $priorityCounts->max('count') ?? 1;

        // Module colors mapping - must match the pie chart colors
        $moduleColors = [
            '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6',
            '#EC4899', '#6366F1', '#14B8A6', '#F97316', '#06B6D4'
        ];

        // Build module color map from moduleData (pie chart order) for consistency
        $moduleColorMap = [];
        foreach ($this->moduleData as $index => $mod) {
            $moduleColorMap[$mod['id']] = $moduleColors[$index % count($moduleColors)];
        }

        $this->priorityModuleData = $priorityCounts->map(function ($item) use ($priorities, $maxCount, $priorityBreakdown, $modules, $moduleColors, $moduleColorMap) {
            $priority = $priorities->get($item->priority_id);
            $breakdown = $priorityBreakdown->get($item->priority_id, collect());

            // Build module breakdown for this priority
            $moduleBreakdownData = $breakdown->map(function ($b) use ($modules, $moduleColors, $moduleColorMap, $item) {
                $module = $modules->get($b->module_id);
                // Use color from pie chart mapping, fallback to default
                $color = $moduleColorMap[$b->module_id] ?? '#6B7280';
                return [
                    'module_id' => $b->module_id,
                    'name' => $module ? $module->name : 'Unknown',
                    'count' => $b->count,
                    'percentage' => $item->count > 0 ? round(($b->count / $item->count) * 100, 1) : 0,
                    'color' => $color,
                ];
            })->sortByDesc('count')->values()->toArray();

            return [
                'id' => $item->priority_id,
                'name' => $priority ? $priority->name : 'Unknown',
                'count' => $item->count,
                'percentage' => round(($item->count / $maxCount) * 100, 1),
                'breakdown' => $moduleBreakdownData,
            ];
        })->values()->toArray();
    }

    private function fetchDurationData()
    {
        // Build query for closed tickets based on selected product
        $ticketQuery = Ticket::query();

        if ($this->selectedProduct === 'v2') {
            $ticketQuery->where('product_id', 2);
        } else {
            $ticketQuery->where('product_id', 1);
        }

        // Get closed ticket IDs
        $closedTicketIds = (clone $ticketQuery)
            ->where('status', 'Closed')
            ->pluck('id');

        if ($closedTicketIds->isEmpty()) {
            $this->durationData = [];
            return;
        }

        // Build query with trend date range filter
        $logQuery = DB::connection('ticketingsystem_live')
            ->table('ticket_logs as tl')
            ->join('tickets as t', 't.id', '=', 'tl.ticket_id')
            ->whereIn('tl.ticket_id', $closedTicketIds)
            ->where('tl.field_name', 'status')
            ->where('tl.new_value', 'Closed')
            ->where('tl.old_value', '!=', 'Closed')
            ->whereNotNull('t.created_at');

        // Apply trend date range filter
        if ($this->trendStartDate) {
            $logQuery->whereDate('tl.created_at', '>=', $this->trendStartDate);
        }
        if ($this->trendEndDate) {
            $logQuery->whereDate('tl.created_at', '<=', $this->trendEndDate);
        }

        // Get resolution data from Status Log - group by the date when ticket was closed
        $this->durationData = $logQuery
            ->selectRaw('DATE_FORMAT(tl.created_at, "%Y-%m-%d") as close_date')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, t.created_at, tl.created_at)) as avg_hours')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('close_date')
            ->orderBy('close_date')
            ->get()
            ->map(function ($item) {
                $avgDays = round($item->avg_hours / 24, 1);
                return [
                    'month' => Carbon::parse($item->close_date)->format('d M'),
                    'avg_days' => $avgDays,
                    'count' => $item->count,
                ];
            })
            ->toArray();
    }

    private function fetchRndData(): void
    {
        $this->p1PriorityId = TicketPriority::where('name', 'Software Bugs')->value('id');
        $this->p2PriorityId = TicketPriority::where('name', 'Back End Assistance')->value('id');

        if (!$this->p1PriorityId && !$this->p2PriorityId) {
            $this->rndTableData = [];
            $this->rndP2TableData = [];
            return;
        }

        $statuses = ['New', 'In Progress', 'Completed', 'Closed', 'Closed System Configuration', 'Closed with new CR'];

        // P1 table
        if ($this->p1PriorityId) {
            $p1Query = $this->getBaseQuery()->where('priority_id', $this->p1PriorityId);
            $this->rndTableData = $this->buildModuleStatusTable($p1Query, $statuses);
        } else {
            $this->rndTableData = [];
        }

        // P2 table
        if ($this->p2PriorityId) {
            $p2Query = $this->getBaseQuery()->where('priority_id', $this->p2PriorityId);
            $this->rndP2TableData = $this->buildModuleStatusTable($p2Query, $statuses);
        } else {
            $this->rndP2TableData = [];
        }
    }

    private function buildModuleStatusTable($query, array $statuses): array
    {
        $counts = (clone $query)
            ->select('module_id', 'status', DB::raw('COUNT(*) as count'))
            ->whereNotNull('module_id')
            ->groupBy('module_id', 'status')
            ->get();

        $moduleIds = $counts->pluck('module_id')->unique()->toArray();
        $modules = TicketModule::whereIn('id', $moduleIds)->pluck('name', 'id');

        $grouped = $counts->groupBy('module_id');
        $tableRows = [];

        foreach ($grouped as $moduleId => $rows) {
            $row = [
                'module_id' => $moduleId,
                'module_name' => $modules[$moduleId] ?? 'Unknown',
                'total' => 0,
            ];
            foreach ($statuses as $status) {
                $row[$status] = 0;
            }
            foreach ($rows as $r) {
                if (in_array($r->status, $statuses)) {
                    $row[$r->status] = $r->count;
                }
                $row['total'] += $r->count;
            }
            $tableRows[] = $row;
        }

        usort($tableRows, fn($a, $b) => $b['total'] <=> $a['total']);

        return $tableRows;
    }

    private function fetchFeData(): void
    {
        $statuses = ['New', 'In Progress', 'Completed', 'Closed', 'Closed System Configuration', 'Closed with new CR'];

        // Ensure priority IDs are set
        if (!$this->p1PriorityId) {
            $this->p1PriorityId = TicketPriority::where('name', 'Software Bugs')->value('id');
        }
        if (!$this->p2PriorityId) {
            $this->p2PriorityId = TicketPriority::where('name', 'Back End Assistance')->value('id');
        }

        // P1 (Software Bugs)
        if ($this->p1PriorityId) {
            $p1Query = $this->getBaseQuery()
                ->where('priority_id', $this->p1PriorityId)
                ->whereNotNull('module_id')
                ->whereNotNull('requestor_id');
            $this->feTableData = $this->buildRequestorStatusTable($p1Query, $statuses);
        } else {
            $this->feTableData = [];
        }

        // P2 (Back End Assistance)
        if ($this->p2PriorityId) {
            $p2Query = $this->getBaseQuery()
                ->where('priority_id', $this->p2PriorityId)
                ->whereNotNull('module_id')
                ->whereNotNull('requestor_id');
            $this->feP2TableData = $this->buildRequestorStatusTable($p2Query, $statuses);
        } else {
            $this->feP2TableData = [];
        }
    }

    private function buildRequestorStatusTable($query, array $statuses): array
    {
        $tickets = (clone $query)
            ->select('id', 'module_id', 'status', 'requestor_id')
            ->get();

        $requestorMap = [];
        foreach ($tickets as $ticket) {
            $rid = $ticket->requestor_id;
            if (!isset($requestorMap[$rid])) {
                $requestorMap[$rid] = [];
            }
            if (!isset($requestorMap[$rid][$ticket->module_id])) {
                $requestorMap[$rid][$ticket->module_id] = array_fill_keys($statuses, 0);
                $requestorMap[$rid][$ticket->module_id]['total'] = 0;
            }
            if (in_array($ticket->status, $statuses)) {
                $requestorMap[$rid][$ticket->module_id][$ticket->status]++;
            }
            $requestorMap[$rid][$ticket->module_id]['total']++;
        }

        $requestorNames = [];
        if (!empty($requestorMap)) {
            $requestorNames = TicketingUser::whereIn('id', array_keys($requestorMap))
                ->pluck('name', 'id')
                ->toArray();
        }

        $allModuleIds = collect($requestorMap)->flatMap(fn($modules) => array_keys($modules))->unique()->toArray();
        $moduleNames = [];
        if (!empty($allModuleIds)) {
            $moduleNames = TicketModule::whereIn('id', $allModuleIds)
                ->pluck('name', 'id')
                ->toArray();
        }

        $result = [];
        foreach ($requestorMap as $requestorId => $modules) {
            $requestorTotal = 0;
            $statusTotals = array_fill_keys($statuses, 0);
            $moduleRows = [];
            foreach ($modules as $moduleId => $statusCounts) {
                $requestorTotal += $statusCounts['total'];
                foreach ($statuses as $s) {
                    $statusTotals[$s] += $statusCounts[$s];
                }
                $moduleRows[] = array_merge([
                    'module_id' => $moduleId,
                    'module_name' => $moduleNames[$moduleId] ?? 'Unknown',
                ], $statusCounts);
            }
            usort($moduleRows, fn($a, $b) => $b['total'] <=> $a['total']);

            $result[] = [
                'requestor_id' => $requestorId,
                'requestor_name' => $requestorNames[$requestorId] ?? 'Unknown',
                'total' => $requestorTotal,
                'status_totals' => $statusTotals,
                'modules' => $moduleRows,
            ];
        }

        usort($result, fn($a, $b) => $b['total'] <=> $a['total']);

        return $result;
    }

    private function fetchFrontendData()
    {
        // Base query for frontend tickets (is_internal = false)
        $query = $this->getBaseQuery()->where('is_internal', false);

        // Total frontend tickets
        $this->frontendTotalTickets = (clone $query)->count();

        // By User (Requestor) - query from ticketingsystem_live database
        $userCounts = (clone $query)
            ->select('requestor_id', DB::raw('COUNT(*) as count'))
            ->whereNotNull('requestor_id')
            ->groupBy('requestor_id')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // Query users from the ticketingsystem_live connection (same as tickets)
        $users = DB::connection('ticketingsystem_live')
            ->table('users')
            ->whereIn('id', $userCounts->pluck('requestor_id'))
            ->get()
            ->keyBy('id');

        $this->frontendUserData = $userCounts->map(function ($item) use ($users) {
            $user = $users->get($item->requestor_id);
            return [
                'id' => $item->requestor_id,
                'name' => $user ? $user->name : 'Unknown',
                'count' => $item->count,
            ];
        })->values()->toArray();
    }

    // Frontend user chart methods
    public function selectFrontendUser($userId)
    {
        // Toggle off if clicking the same user
        if ($this->selectedFrontendUserId === $userId) {
            $this->clearFrontendUserChart();
            return;
        }

        $query = $this->getBaseQuery()->where('is_internal', false)->where('requestor_id', $userId);

        // Get user from ticketingsystem_live database
        $user = DB::connection('ticketingsystem_live')
            ->table('users')
            ->where('id', $userId)
            ->first();

        $this->selectedFrontendUserId = $userId;
        $this->selectedFrontendUserName = $user ? $user->name : 'Unknown';

        // Get module counts for this user
        $moduleCounts = (clone $query)
            ->select('module_id', DB::raw('COUNT(*) as count'))
            ->whereNotNull('module_id')
            ->groupBy('module_id')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        $moduleIds = $moduleCounts->pluck('module_id')->toArray();
        $modules = TicketModule::whereIn('id', $moduleIds)->get()->keyBy('id');

        // Get all priorities for color mapping
        $priorities = TicketPriority::all()->keyBy('id');

        // Get breakdown by module and priority for this user
        $moduleBreakdown = (clone $query)
            ->select('module_id', 'priority_id', DB::raw('COUNT(*) as count'))
            ->whereIn('module_id', $moduleIds)
            ->whereNotNull('priority_id')
            ->groupBy('module_id', 'priority_id')
            ->get()
            ->groupBy('module_id');

        $maxCount = $moduleCounts->max('count') ?? 1;

        // Priority colors mapping
        $priorityColors = [
            'Software Bugs' => '#EF4444',
            'Back End Assistance' => '#F59E0B',
            'Critical Enhancement' => '#8B5CF6',
            'Non-Critical Enhancement' => '#10B981',
            'Paid Customization' => '#3B82F6',
        ];

        $this->selectedFrontendUserModuleData = $moduleCounts->map(function ($item) use ($modules, $maxCount, $moduleBreakdown, $priorities, $priorityColors) {
            $module = $modules->get($item->module_id);
            $breakdown = $moduleBreakdown->get($item->module_id, collect());

            // Build priority breakdown for this module
            $priorityBreakdown = $breakdown->map(function ($b) use ($priorities, $priorityColors, $item) {
                $priority = $priorities->get($b->priority_id);
                $name = $priority ? $priority->name : 'Unknown';
                return [
                    'priority_id' => $b->priority_id,
                    'name' => $name,
                    'count' => $b->count,
                    'percentage' => $item->count > 0 ? round(($b->count / $item->count) * 100, 1) : 0,
                    'color' => $priorityColors[$name] ?? '#6B7280',
                ];
            })->sortByDesc('count')->values()->toArray();

            return [
                'id' => $item->module_id,
                'name' => $module ? $module->name : 'Unknown',
                'count' => $item->count,
                'percentage' => round(($item->count / $maxCount) * 100, 1),
                'breakdown' => $priorityBreakdown,
            ];
        })->values()->toArray();
    }

    public function clearFrontendUserChart()
    {
        $this->selectedFrontendUserId = null;
        $this->selectedFrontendUserName = '';
        $this->selectedFrontendUserModuleData = [];
    }

    public function openFrontendUserModuleSlideOver($moduleId)
    {
        if (!$this->selectedFrontendUserId) {
            return;
        }

        $query = $this->getBaseQuery()
            ->where('is_internal', false)
            ->where('requestor_id', $this->selectedFrontendUserId)
            ->where('module_id', $moduleId);

        $module = TicketModule::find($moduleId);

        // Get tickets with priority relationship
        $tickets = (clone $query)
            ->with('priority:id,name')
            ->select('id', 'ticket_id', 'title', 'company_name', 'status', 'created_date', 'priority_id')
            ->orderByDesc('created_at')
            ->get();

        // Store minimal ticket data
        $this->ticketList = $tickets->map(function ($ticket) {
            return [
                'id' => $ticket->id,
                'ticket_id' => $ticket->ticket_id,
                'title' => $ticket->title,
                'company_name' => $ticket->company_name,
                'status' => $ticket->status,
                'created_date' => $ticket->created_date ? $ticket->created_date->format('Y-m-d') : null,
            ];
        })->toArray();

        // Group tickets by priority
        $priorityColors = [
            'Software Bugs' => '#EF4444',
            'Back End Assistance' => '#F59E0B',
            'Critical Enhancement' => '#8B5CF6',
            'Non-Critical Enhancement' => '#10B981',
            'Paid Customization' => '#3B82F6',
        ];

        $grouped = $tickets->groupBy(function ($ticket) {
            return $ticket->priority ? $ticket->priority->id : 0;
        });

        $this->ticketsByPriority = $grouped->map(function ($ticketGroup, $priorityIdKey) use ($priorityColors) {
            $firstTicket = $ticketGroup->first();
            $priorityName = $firstTicket && $firstTicket->priority ? $firstTicket->priority->name : 'Unknown';
            return [
                'id' => $priorityIdKey,
                'name' => $priorityName,
                'color' => $priorityColors[$priorityName] ?? '#6B7280',
                'count' => $ticketGroup->count(),
                'tickets' => $ticketGroup->map(function ($ticket) {
                    return [
                        'id' => $ticket->id,
                        'ticket_id' => $ticket->ticket_id,
                        'title' => $ticket->title,
                        'company_name' => $ticket->company_name,
                        'status' => $ticket->status,
                        'created_date' => $ticket->created_date ? $ticket->created_date->format('Y-m-d') : null,
                    ];
                })->values()->toArray(),
            ];
        })->sortByDesc('count')->values()->toArray();

        $this->focusPriorityId = null;
        $this->slideOverTitle = $this->selectedFrontendUserName . ' - ' . ($module ? $module->name : 'Module');
        $this->showSlideOver = true;
    }

    public function openRndModuleSlideOver($moduleId, $priorityId = null)
    {
        $statusOrder = ['New', 'In Progress', 'Completed', 'Closed', 'Closed System Configuration', 'Closed with new CR'];
        $statusColors = [
            'New' => '#3B82F6',
            'In Progress' => '#F59E0B',
            'Completed' => '#06B6D4',
            'Closed' => '#10B981',
            'Closed System Configuration' => '#8B5CF6',
            'Closed with new CR' => '#6366F1',
        ];

        $module = TicketModule::find($moduleId);
        $priority = $priorityId ? TicketPriority::find($priorityId) : null;

        $query = $this->getBaseQuery()
            ->where('module_id', $moduleId);

        if ($priorityId) {
            $query->where('priority_id', $priorityId);
        } else {
            $p1p2Ids = TicketPriority::whereIn('name', ['Software Bugs', 'Back End Assistance'])
                ->pluck('id')
                ->toArray();
            $query->whereIn('priority_id', $p1p2Ids);
        }

        $tickets = (clone $query)
            ->select('id', 'ticket_id', 'title', 'company_name', 'status', 'created_date')
            ->orderByDesc('created_at')
            ->get();

        $this->ticketList = $tickets->map(function ($ticket) {
            return [
                'id' => $ticket->id,
                'ticket_id' => $ticket->ticket_id,
                'title' => $ticket->title,
                'company_name' => $ticket->company_name,
                'status' => $ticket->status,
                'created_date' => $ticket->created_date ? $ticket->created_date->format('Y-m-d') : null,
            ];
        })->toArray();

        // Group tickets by status in defined order
        $grouped = $tickets->groupBy('status');

        $this->ticketsByPriority = collect($statusOrder)
            ->filter(fn($status) => $grouped->has($status))
            ->map(function ($status) use ($grouped, $statusColors) {
                $ticketGroup = $grouped->get($status);
                return [
                    'id' => $status,
                    'name' => $status,
                    'color' => $statusColors[$status] ?? '#6B7280',
                    'count' => $ticketGroup->count(),
                    'tickets' => $ticketGroup->map(function ($ticket) {
                        return [
                            'id' => $ticket->id,
                            'ticket_id' => $ticket->ticket_id,
                            'title' => $ticket->title,
                            'company_name' => $ticket->company_name,
                            'status' => $ticket->status,
                            'created_date' => $ticket->created_date ? $ticket->created_date->format('Y-m-d') : null,
                        ];
                    })->values()->toArray(),
                ];
            })->values()->toArray();

        $this->focusPriorityId = null;
        $priorityLabel = $priority ? $priority->name : 'P1 & P2';
        $this->slideOverTitle = ($module ? $module->name : 'Module') . ' - ' . $priorityLabel;
        $this->showSlideOver = true;
    }

    public function openFeModuleSlideOver($moduleId, $requestorId, $priorityId = null)
    {
        $statusOrder = ['New', 'In Progress', 'Completed', 'Closed', 'Closed System Configuration', 'Closed with new CR'];
        $statusColors = [
            'New' => '#3B82F6',
            'In Progress' => '#F59E0B',
            'Completed' => '#06B6D4',
            'Closed' => '#10B981',
            'Closed System Configuration' => '#8B5CF6',
            'Closed with new CR' => '#6366F1',
        ];

        $module = TicketModule::find($moduleId);
        $requestor = TicketingUser::find($requestorId);

        $query = $this->getBaseQuery()
            ->where('module_id', $moduleId)
            ->where('requestor_id', $requestorId);

        if ($priorityId) {
            $query->where('priority_id', $priorityId);
        } else {
            $p1p2Ids = TicketPriority::whereIn('name', ['Software Bugs', 'Back End Assistance'])
                ->pluck('id')
                ->toArray();
            $query->whereIn('priority_id', $p1p2Ids);
        }

        $tickets = (clone $query)
            ->select('id', 'ticket_id', 'title', 'company_name', 'status', 'created_date')
            ->orderByDesc('created_at')
            ->get();

        $this->ticketList = $tickets->map(function ($ticket) {
            return [
                'id' => $ticket->id,
                'ticket_id' => $ticket->ticket_id,
                'title' => $ticket->title,
                'company_name' => $ticket->company_name,
                'status' => $ticket->status,
                'created_date' => $ticket->created_date ? $ticket->created_date->format('Y-m-d') : null,
            ];
        })->toArray();

        $grouped = $tickets->groupBy('status');

        $this->ticketsByPriority = collect($statusOrder)
            ->filter(fn($status) => $grouped->has($status))
            ->map(function ($status) use ($grouped, $statusColors) {
                $ticketGroup = $grouped->get($status);
                return [
                    'id' => $status,
                    'name' => $status,
                    'color' => $statusColors[$status] ?? '#6B7280',
                    'count' => $ticketGroup->count(),
                    'tickets' => $ticketGroup->map(function ($ticket) {
                        return [
                            'id' => $ticket->id,
                            'ticket_id' => $ticket->ticket_id,
                            'title' => $ticket->title,
                            'company_name' => $ticket->company_name,
                            'status' => $ticket->status,
                            'created_date' => $ticket->created_date ? $ticket->created_date->format('Y-m-d') : null,
                        ];
                    })->values()->toArray(),
                ];
            })->values()->toArray();

        $this->focusPriorityId = null;
        $this->slideOverTitle = ($requestor ? $requestor->name : 'Requestor') . ' - ' . ($module ? $module->name : 'Module');
        $this->showSlideOver = true;
    }

    public function openFeRequestorSlideOver($requestorId, $priorityId)
    {
        $statusOrder = ['New', 'In Progress'];
        $statusColors = [
            'New' => '#3B82F6',
            'In Progress' => '#F59E0B',
        ];

        $requestor = TicketingUser::find($requestorId);

        $tickets = $this->getBaseQuery()
            ->where('requestor_id', $requestorId)
            ->where('priority_id', $priorityId)
            ->whereIn('status', $statusOrder)
            ->select('id', 'ticket_id', 'title', 'company_name', 'status', 'created_date', 'module_id')
            ->orderByDesc('created_at')
            ->get();

        $this->ticketList = $tickets->map(function ($ticket) {
            return [
                'id' => $ticket->id,
                'ticket_id' => $ticket->ticket_id,
                'title' => $ticket->title,
                'company_name' => $ticket->company_name,
                'status' => $ticket->status,
                'created_date' => $ticket->created_date ? $ticket->created_date->format('Y-m-d') : null,
            ];
        })->toArray();

        $grouped = $tickets->groupBy('status');

        $this->ticketsByPriority = collect($statusOrder)
            ->filter(fn($status) => $grouped->has($status))
            ->map(function ($status) use ($grouped, $statusColors) {
                $ticketGroup = $grouped->get($status);
                $byModule = $ticketGroup->groupBy('module_id');
                return [
                    'id' => $status,
                    'name' => $status,
                    'color' => $statusColors[$status] ?? '#6B7280',
                    'count' => $ticketGroup->count(),
                    'subgroups' => $byModule->map(function ($moduleTickets, $moduleId) {
                        $module = TicketModule::find($moduleId);
                        return [
                            'name' => $module ? $module->name : 'Unknown',
                            'tickets' => $moduleTickets->map(function ($ticket) {
                                return [
                                    'id' => $ticket->id,
                                    'ticket_id' => $ticket->ticket_id,
                                    'title' => $ticket->title,
                                    'company_name' => $ticket->company_name,
                                    'status' => $ticket->status,
                                    'created_date' => $ticket->created_date ? $ticket->created_date->format('Y-m-d') : null,
                                ];
                            })->values()->toArray(),
                        ];
                    })->values()->toArray(),
                    'tickets' => $ticketGroup->map(function ($ticket) {
                        return [
                            'id' => $ticket->id,
                            'ticket_id' => $ticket->ticket_id,
                            'title' => $ticket->title,
                            'company_name' => $ticket->company_name,
                            'status' => $ticket->status,
                            'created_date' => $ticket->created_date ? $ticket->created_date->format('Y-m-d') : null,
                        ];
                    })->values()->toArray(),
                ];
            })->values()->toArray();

        $this->focusPriorityId = null;
        $this->slideOverTitle = ($requestor ? $requestor->name : 'Requestor');
        $this->showSlideOver = true;
    }

    // Slide-over methods
    public function openPrioritySlideOver($priorityId)
    {
        $query = $this->getBaseQuery();
        $priority = TicketPriority::find($priorityId);

        $tickets = (clone $query)
            ->where('priority_id', $priorityId)
            ->select('id', 'ticket_id', 'title', 'company_name', 'status', 'created_date')
            ->orderByDesc('created_date')
            ->get();

        $this->ticketList = $tickets->map(function ($ticket) {
            return [
                'id' => $ticket->id,
                'ticket_id' => $ticket->ticket_id,
                'title' => $ticket->title,
                'company_name' => $ticket->company_name,
                'status' => $ticket->status,
                'created_date' => $ticket->created_date ? $ticket->created_date->format('Y-m-d') : null,
            ];
        })->toArray();

        $this->ticketsByPriority = [];
        $this->focusPriorityId = null;
        $this->slideOverTitle = ($priority ? $priority->name : 'Priority') . ' Tickets';
        $this->showSlideOver = true;
    }

    public function openModuleSlideOver($moduleId, $priorityId = null)
    {
        $query = $this->getBaseQuery();
        $module = TicketModule::find($moduleId);

        // Get tickets with priority relationship - only select needed fields
        $tickets = (clone $query)
            ->where('module_id', $moduleId)
            ->with('priority:id,name')
            ->select('id', 'ticket_id', 'title', 'company_name', 'status', 'created_date', 'priority_id')
            ->orderByDesc('created_at')
            ->get();

        // Store minimal ticket data for flat list fallback
        $this->ticketList = $tickets->map(function ($ticket) {
            return [
                'id' => $ticket->id,
                'ticket_id' => $ticket->ticket_id,
                'title' => $ticket->title,
                'company_name' => $ticket->company_name,
                'status' => $ticket->status,
                'created_date' => $ticket->created_date ? $ticket->created_date->format('Y-m-d') : null,
            ];
        })->toArray();

        // Group tickets by priority
        $priorityColors = [
            'Software Bugs' => '#EF4444',
            'Back End Assistance' => '#F59E0B',
            'Critical Enhancement' => '#8B5CF6',
            'Non-Critical Enhancement' => '#10B981',
            'Paid Customization' => '#3B82F6',
        ];

        $grouped = $tickets->groupBy(function ($ticket) {
            return $ticket->priority ? $ticket->priority->id : 0;
        });

        $this->ticketsByPriority = $grouped->map(function ($ticketGroup, $priorityIdKey) use ($priorityColors) {
            $firstTicket = $ticketGroup->first();
            $priorityName = $firstTicket && $firstTicket->priority ? $firstTicket->priority->name : 'Unknown';
            return [
                'id' => $priorityIdKey,
                'name' => $priorityName,
                'color' => $priorityColors[$priorityName] ?? '#6B7280',
                'count' => $ticketGroup->count(),
                'tickets' => $ticketGroup->map(function ($ticket) {
                    return [
                        'id' => $ticket->id,
                        'ticket_id' => $ticket->ticket_id,
                        'title' => $ticket->title,
                        'company_name' => $ticket->company_name,
                        'status' => $ticket->status,
                        'created_date' => $ticket->created_date ? $ticket->created_date->format('Y-m-d') : null,
                    ];
                })->values()->toArray(),
            ];
        })->sortByDesc('count')->values()->toArray();

        // Set focus priority for auto-scroll/expand
        $this->focusPriorityId = $priorityId;

        $this->slideOverTitle = ($module ? $module->name : 'Module');
        $this->showSlideOver = true;
    }

    public function openPriorityBarSlideOver($priorityId, $moduleId = null)
    {
        $query = $this->getBaseQuery();
        $priority = TicketPriority::find($priorityId);

        // Build query for this priority
        $ticketQuery = (clone $query)->where('priority_id', $priorityId);

        // If module specified, filter by module too
        if ($moduleId) {
            $ticketQuery->where('module_id', $moduleId);
        }

        // Get tickets with module relationship
        $tickets = $ticketQuery
            ->with('module:id,name')
            ->select('id', 'ticket_id', 'title', 'company_name', 'status', 'created_date', 'module_id')
            ->orderByDesc('created_at')
            ->get();

        // Store minimal ticket data for flat list fallback
        $this->ticketList = $tickets->map(function ($ticket) {
            return [
                'id' => $ticket->id,
                'ticket_id' => $ticket->ticket_id,
                'title' => $ticket->title,
                'company_name' => $ticket->company_name,
                'status' => $ticket->status,
                'created_date' => $ticket->created_date ? $ticket->created_date->format('Y-m-d') : null,
            ];
        })->toArray();

        // Module colors mapping - must match pie chart
        $moduleColors = [
            '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6',
            '#EC4899', '#6366F1', '#14B8A6', '#F97316', '#06B6D4'
        ];

        // Build module color map from moduleData (pie chart order) for consistency
        $moduleColorMap = [];
        foreach ($this->moduleData as $index => $mod) {
            $moduleColorMap[$mod['id']] = $moduleColors[$index % count($moduleColors)];
        }

        // Group tickets by module
        $grouped = $tickets->groupBy(function ($ticket) {
            return $ticket->module ? $ticket->module->id : 0;
        });

        $this->ticketsByPriority = $grouped->map(function ($ticketGroup, $moduleIdKey) use ($moduleColorMap) {
            $firstTicket = $ticketGroup->first();
            $moduleName = $firstTicket && $firstTicket->module ? $firstTicket->module->name : 'Unknown';
            // Use color from pie chart mapping, fallback to gray
            $color = $moduleColorMap[$moduleIdKey] ?? '#6B7280';
            return [
                'id' => $moduleIdKey,
                'name' => $moduleName,
                'color' => $color,
                'count' => $ticketGroup->count(),
                'tickets' => $ticketGroup->map(function ($ticket) {
                    return [
                        'id' => $ticket->id,
                        'ticket_id' => $ticket->ticket_id,
                        'title' => $ticket->title,
                        'company_name' => $ticket->company_name,
                        'status' => $ticket->status,
                        'created_date' => $ticket->created_date ? $ticket->created_date->format('Y-m-d') : null,
                    ];
                })->values()->toArray(),
            ];
        })->sortByDesc('count')->values()->toArray();

        // Set focus for auto-scroll/expand
        $this->focusPriorityId = $moduleId;

        $this->slideOverTitle = ($priority ? $priority->name : 'Priority');
        $this->showSlideOver = true;
    }

    public function openModuleBarSlideOver($moduleId, $priorityId = null)
    {
        $query = $this->getBaseQuery();
        $module = TicketModule::find($moduleId);

        // Build query for this module
        $ticketQuery = (clone $query)->where('module_id', $moduleId);

        // If priority specified, filter by priority too
        if ($priorityId) {
            $ticketQuery->where('priority_id', $priorityId);
        }

        // Get tickets with priority relationship
        $tickets = $ticketQuery
            ->with('priority:id,name')
            ->select('id', 'ticket_id', 'title', 'company_name', 'status', 'created_date', 'priority_id')
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        // Store minimal ticket data for flat list fallback
        $this->ticketList = $tickets->map(function ($ticket) {
            return [
                'id' => $ticket->id,
                'ticket_id' => $ticket->ticket_id,
                'title' => $ticket->title,
                'company_name' => $ticket->company_name,
                'status' => $ticket->status,
                'created_date' => $ticket->created_date ? $ticket->created_date->format('Y-m-d') : null,
            ];
        })->toArray();

        // Priority colors mapping
        $priorityColors = [
            'Software Bugs' => '#EF4444',
            'Back End Assistance' => '#F59E0B',
            'Critical Enhancement' => '#8B5CF6',
            'Non-Critical Enhancement' => '#10B981',
            'Paid Customization' => '#3B82F6',
        ];

        // Group tickets by priority
        $grouped = $tickets->groupBy(function ($ticket) {
            return $ticket->priority ? $ticket->priority->id : 0;
        });

        $this->ticketsByPriority = $grouped->map(function ($ticketGroup, $priorityIdKey) use ($priorityColors) {
            $firstTicket = $ticketGroup->first();
            $priorityName = $firstTicket && $firstTicket->priority ? $firstTicket->priority->name : 'Unknown';
            $color = $priorityColors[$priorityName] ?? '#6B7280';
            return [
                'id' => $priorityIdKey,
                'name' => $priorityName,
                'color' => $color,
                'count' => $ticketGroup->count(),
                'tickets' => $ticketGroup->map(function ($ticket) {
                    return [
                        'id' => $ticket->id,
                        'ticket_id' => $ticket->ticket_id,
                        'title' => $ticket->title,
                        'company_name' => $ticket->company_name,
                        'status' => $ticket->status,
                        'created_date' => $ticket->created_date ? $ticket->created_date->format('Y-m-d') : null,
                    ];
                })->values()->toArray(),
            ];
        })->sortByDesc('count')->values()->toArray();

        // Set focus for auto-scroll/expand
        $this->focusPriorityId = $priorityId;

        $this->slideOverTitle = ($module ? $module->name : 'Module');
        $this->showSlideOver = true;
    }

    public function openStatusSlideOver($status)
    {
        $query = $this->getBaseQuery();

        if ($status === 'open') {
            $tickets = (clone $query)
                ->whereNotIn('status', ['Closed', 'Resolved'])
                ->select('id', 'ticket_id', 'title', 'company_name', 'status', 'created_date')
                ->orderByDesc('created_date')
                ->limit(100)
                ->get();
            $this->slideOverTitle = 'Open Tickets';
        } else {
            $tickets = (clone $query)
                ->whereIn('status', ['Closed', 'Resolved'])
                ->select('id', 'ticket_id', 'title', 'company_name', 'status', 'created_date')
                ->orderByDesc('created_date')
                ->limit(100)
                ->get();
            $this->slideOverTitle = 'Completed Tickets';
        }

        $this->ticketList = $tickets->map(function ($ticket) {
            return [
                'id' => $ticket->id,
                'ticket_id' => $ticket->ticket_id,
                'title' => $ticket->title,
                'company_name' => $ticket->company_name,
                'status' => $ticket->status,
                'created_date' => $ticket->created_date ? $ticket->created_date->format('Y-m-d') : null,
            ];
        })->toArray();

        $this->ticketsByPriority = [];
        $this->focusPriorityId = null;
        $this->showSlideOver = true;
    }

    public function closeSlideOver()
    {
        $this->showSlideOver = false;
        $this->ticketList = [];
        $this->ticketsByPriority = [];
        $this->focusPriorityId = null;
    }

    public function viewTicket($ticketId): void
    {
        $this->dispatch('openTicketModal', $ticketId);
    }

    public function exportToExcel()
    {
        if (empty($this->ticketsByPriority)) {
            return;
        }

        $filename = str_replace(' ', '_', $this->slideOverTitle) . '_' . now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download(
            new TicketAnalysisExport($this->ticketsByPriority, $this->slideOverTitle),
            $filename
        );
    }
}
