<?php
namespace App\Filament\Pages;

use App\Models\Lead;
use Filament\Pages\Page;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\DB;

class LeadAnalysis extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';
    protected static string $view = 'filament.pages.lead-analysis';
    protected static ?string $navigationLabel = 'Lead Analysis';
    protected static ?string $title = '';
    protected static ?int $navigationSort = 8;
    protected static ?string $navigationGroup = 'Analysis';

    public $selectedUser; // Selected Salesperson
    public $users; // List of Salespersons
    public $totalLeads = 0;
    public $activeLeads = 0;
    public $inactiveLeads = 0;
    public $selectedMonth;
    public string $filterMode = 'month'; // 'month' | 'range' | 'custom'
    public ?string $startDate = null;
    public ?string $endDate = null;
    public string $customType = 'quarter'; // 'quarter' | 'half' | 'year'
    public ?string $customValue = null; // e.g. 2026-Q1, 2026-H1, 2026

    public $activePercentage = 0;
    public $inactivePercentage = 0;
    public $companySizeData = [];

    public $totalActiveLeads = 0;
    public $stagesData = [];

    public $totalInactiveLeads;
    public $inactiveStatusData = [];

    public $totalTransferLeads;
    public $transferStatusData = [];

    public $totalFollowUpLeads;
    public $followUpStatusData = [];

    public Carbon $currentDate;

    //Slide Modal Variables
    public $showSlideOver = false;
    public $leadList = [];

    public $slideOverTitle = 'Leads';
    public $timetecHRCount;
    public $nonTimetecHRCount;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (!$user || !($user instanceof \App\Models\User)) {
            return false;
        }

        return $user->hasRouteAccess('filament.admin.pages.lead-analysis');
    }

    public function mount()
    {
        // Instead of just fetching all salespersons, get them with the is_timetec_hr attribute
        $this->users = User::where('role_id', 2)->get(); // Keep the original query for individual users

        // Count total TimeTec HR and Non-TimeTec HR salespersons for display in the filter
        $this->timetecHRCount = User::where('role_id', 2)->where('is_timetec_hr', true)->count();
        $this->nonTimetecHRCount = User::where('role_id', 2)->where(function($query) {
            $query->where('is_timetec_hr', false)->orWhereNull('is_timetec_hr');
        })->count();

        $this->currentDate = Carbon::now();

        $authUser = auth()->user();

        // 👇 Admins and managers can select a user or default to "All"
        if (in_array($authUser->role_id, [1, 3])) {
            $this->selectedUser = session('selectedUser', null); // null = all users
        }

        // 👇 User ID 12 can filter but defaults to own data
        elseif ($authUser->id === 12) {
            $this->selectedUser = session('selectedUser', $authUser->id);
        }

        // 👇 Salesperson will only see their own data
        elseif ($authUser->role_id === 2) {
            $this->selectedUser = $authUser->id;
        }

        $this->selectedMonth = session('selectedMonth', $this->currentDate->format('Y-m'));
        $this->filterMode = session('filterMode', 'month');
        $this->startDate = session('startDate', $this->currentDate->copy()->startOfMonth()->format('Y-m-d'));
        $this->endDate = session('endDate', $this->currentDate->copy()->format('Y-m-d'));
        $this->customType = session('customType', 'quarter');
        $this->customValue = session('customValue', $this->defaultCustomValue($this->customType));

        session([
            'selectedUser' => $this->selectedUser,
            'selectedMonth' => $this->selectedMonth,
            'filterMode' => $this->filterMode,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'customType' => $this->customType,
            'customValue' => $this->customValue,
        ]);

        $this->fetchLeads();
        $this->fetchActiveLeads();
        $this->fetchInactiveLeads();
        $this->fetchTransferLeads();
        $this->fetchFollowUpLeads();
    }

    #[On('selectedUserChanged')]
    public function updatedSelectedUser($userId)
    {
        $this->selectedUser = $userId; // Store selected user
        session(['selectedUser' => $userId]); // Store the selected user in session

        // Fetch data when user changes
        $this->fetchLeads();
        $this->fetchActiveLeads();
        $this->fetchInactiveLeads();
        $this->fetchTransferLeads();
        $this->fetchFollowUpLeads();
    }

    public function updatedSelectedMonth($month)
    {
        $this->selectedMonth = $month;
        session(['selectedMonth' => $month]);

        $this->refetchAll();
    }

    public function updatedFilterMode($mode)
    {
        $this->filterMode = $mode;
        session(['filterMode' => $mode]);
        $this->refetchAll();
    }

    public function updatedStartDate($value)
    {
        $this->startDate = $value;
        session(['startDate' => $value]);
        $this->refetchAll();
    }

    public function updatedEndDate($value)
    {
        $this->endDate = $value;
        session(['endDate' => $value]);
        $this->refetchAll();
    }

    public function updatedCustomType($type)
    {
        $this->customType = $type;
        $this->customValue = $this->defaultCustomValue($type);
        session(['customType' => $type, 'customValue' => $this->customValue]);
        $this->refetchAll();
    }

    public function updatedCustomValue($value)
    {
        $this->customValue = $value;
        session(['customValue' => $value]);
        $this->refetchAll();
    }

    private function defaultCustomValue(string $type): string
    {
        $now = Carbon::now();
        return match ($type) {
            'half'  => $now->year . '-H' . ($now->month <= 6 ? 1 : 2),
            'year'  => (string) $now->year,
            default => $now->year . '-Q' . ceil($now->month / 3),
        };
    }

    public function getCustomOptionsProperty(): array
    {
        $now = Carbon::now();
        $opts = [];
        if ($this->customType === 'quarter') {
            for ($y = $now->year; $y >= $now->year - 2; $y--) {
                for ($q = 4; $q >= 1; $q--) $opts["$y-Q$q"] = "Q$q $y";
            }
        } elseif ($this->customType === 'half') {
            for ($y = $now->year; $y >= $now->year - 2; $y--) {
                $opts["$y-H2"] = "H2 $y (Jul–Dec)";
                $opts["$y-H1"] = "H1 $y (Jan–Jun)";
            }
        } else {
            for ($y = $now->year; $y >= $now->year - 4; $y--) $opts[(string) $y] = (string) $y;
        }
        return $opts;
    }

    private function refetchAll(): void
    {
        $this->fetchLeads();
        $this->fetchActiveLeads();
        $this->fetchInactiveLeads();
        $this->fetchTransferLeads();
        $this->fetchFollowUpLeads();
    }

    public function getDateRangeBoundsProperty(): ?array
    {
        if ($this->filterMode === 'range') {
            if (empty($this->startDate) || empty($this->endDate)) return null;
            return [Carbon::parse($this->startDate)->startOfDay(), Carbon::parse($this->endDate)->endOfDay()];
        }
        if ($this->filterMode === 'custom') {
            if (empty($this->customValue)) return null;
            if ($this->customType === 'quarter' && preg_match('/^(\d{4})-Q([1-4])$/', $this->customValue, $m)) {
                $start = Carbon::create((int) $m[1], ((int) $m[2] - 1) * 3 + 1, 1)->startOfMonth();
                return [$start, $start->copy()->addMonths(2)->endOfMonth()];
            }
            if ($this->customType === 'half' && preg_match('/^(\d{4})-H([12])$/', $this->customValue, $m)) {
                $start = Carbon::create((int) $m[1], (int) $m[2] === 1 ? 1 : 7, 1)->startOfMonth();
                return [$start, $start->copy()->addMonths(5)->endOfMonth()];
            }
            if ($this->customType === 'year' && preg_match('/^\d{4}$/', $this->customValue)) {
                $y = (int) $this->customValue;
                return [Carbon::create($y, 1, 1)->startOfYear(), Carbon::create($y, 12, 31)->endOfYear()];
            }
            return null;
        }
        if (empty($this->selectedMonth)) return null;
        $d = Carbon::parse($this->selectedMonth);
        return [$d->copy()->startOfMonth(), $d->copy()->endOfMonth()];
    }

    private function applyBaseFilters($query)
    {
        $user = Auth::user();

        // Filter by selected user type (TimeTec HR or non-TimeTec HR)
        if ($this->selectedUser === 'timetec_hr') {
            $timetecUserIds = User::where('role_id', 2)
                ->where('is_timetec_hr', true)
                ->pluck('id')
                ->toArray();
            $query->whereIn('salesperson', $timetecUserIds);
        }
        elseif ($this->selectedUser === 'non_timetec_hr') {
            $nonTimetecUserIds = User::where('role_id', 2)
                ->where(function($query) {
                    $query->where('is_timetec_hr', false)
                        ->orWhereNull('is_timetec_hr');
                })
                ->pluck('id')
                ->toArray();
            $query->whereIn('salesperson', $nonTimetecUserIds);
        }
        // Individual salesperson selection (for admin/managers and user ID 12)
        elseif ((in_array($user->role_id, [1, 3]) || $user->id === 12) && $this->selectedUser) {
            $query->where('salesperson', $this->selectedUser);
        }

        // If Salesperson, show only their assigned leads (except user ID 12)
        if ($user->role_id == 2 && $user->id !== 12) {
            $query->where('salesperson', $user->id);
        }

        // Apply date filter (month / quarter / half / year)
        $bounds = $this->dateRangeBounds;
        if ($bounds) {
            [$start, $end] = $bounds;
            $query->whereBetween('created_at', [$start->format('Y-m-d'), $end->format('Y-m-d')]);
        }

        // Exclude existing customers and filter for valid company sizes
        $query->where(function($q) {
            $q->where('lead_code', '!=', 'Existing Customer')
            ->orWhereNull('lead_code');
        })->whereNotNull('company_size');

        return $query;
    }

    public function fetchLeads()
    {
        $user = Auth::user();
        $query = Lead::query();

        $this->applyBaseFilters($query);

        // Fetch filtered leads
        $leads = $query->get();

        // ✅ Store Active and Inactive Leads as Class Properties
        $this->totalLeads = $leads->count();
        $this->activeLeads = $leads->where('categories', 'Active')->count();
        $this->inactiveLeads = $leads->where('categories', 'Inactive')->count();

        // Calculate Active & Inactive Percentage
        $this->activePercentage = $this->totalLeads > 0 ? round(($this->activeLeads / $this->totalLeads) * 100, 2) : 0;
        $this->inactivePercentage = $this->totalLeads > 0 ? round(($this->inactiveLeads / $this->totalLeads) * 100, 2) : 0;

        // Fetch company size data
        $defaultCompanySizes = [
            'Small' => 0,
            'Medium' => 0,
            'Large' => 0,
            'Enterprise' => 0,
        ];

        $companySizeCounts = $leads
            ->whereNotNull('company_size_label')
            ->groupBy('company_size_label')
            ->map(fn($group) => $group->count())
            ->toArray();

        $this->companySizeData = array_merge($defaultCompanySizes, $companySizeCounts);
    }
    /**
     * Fetches active leads and their breakdown by stages
     */
    public function fetchActiveLeads()
    {
        $user = Auth::user();
        $query = Lead::where('categories', 'Active'); // Filter only Active leads

        $this->applyBaseFilters($query);

        // Count total active leads
        $this->totalActiveLeads = $query->count();

        // Define expected stages
        $stages = ['Transfer', 'Demo', 'Follow Up'];

        // Fetch leads grouped by their stage
        $stagesDataRaw = $query
            ->whereIn('stage', $stages)
            ->select('stage', DB::raw('COUNT(*) as total'))
            ->groupBy('stage')
            ->pluck('total', 'stage')
            ->toArray();

        // Ensure all stages exist in the correct order (fill missing ones with 0)
        $this->stagesData = array_merge(array_fill_keys($stages, 0), $stagesDataRaw);
    }

    public function fetchInactiveLeads()
    {
        $user = Auth::user();
        $query = Lead::where('categories', 'Inactive'); // Filter only Inactive leads

        $this->applyBaseFilters($query);

        // Count total inactive leads
        $this->totalInactiveLeads = $query->count();

        // Define expected statuses
        $inactiveStatuses = ['Closed', 'Lost', 'On Hold', 'No Response', 'Junk'];

        // Fetch leads grouped by their status
        $inactiveStatusCounts = $query
            ->whereIn('lead_status', $inactiveStatuses)
            ->select('lead_status', DB::raw('COUNT(*) as total'))
            ->groupBy('lead_status')
            ->pluck('total', 'lead_status')
            ->toArray();

        // Ensure all statuses exist in the result, even if 0
        $this->inactiveStatusData = array_merge(array_fill_keys($inactiveStatuses, 0), $inactiveStatusCounts);
    }

    public function fetchTransferLeads()
    {
        $user = Auth::user();
        $query = Lead::where('stage', 'Transfer'); // Filter only Transfer leads

        $this->applyBaseFilters($query);

        // Define expected statuses
        $transferStatuses = ['RFQ-Transfer', 'Pending Demo', 'Demo Cancelled'];

        // Count total leads in the "Transfer" stage (excluding specific statuses)
        $this->totalTransferLeads = $query
            ->whereNotIn('lead_status', ['Under Review', 'New']) // Exclude these statuses
            ->count();

        // Fetch leads grouped by their "lead_status"
        $transferStatusCounts = $query
            ->whereIn('lead_status', $transferStatuses)
            ->select('lead_status', DB::raw('COUNT(*) as total'))
            ->groupBy('lead_status')
            ->pluck('total', 'lead_status')
            ->toArray();

        // Ensure all statuses exist in the result, even if 0
        $this->transferStatusData = array_merge(array_fill_keys($transferStatuses, 0), $transferStatusCounts);
    }

    public function fetchFollowUpLeads()
    {
        $user = Auth::user();
        $query = Lead::where('stage', 'Follow Up'); // Filter only Follow Up leads

        $this->applyBaseFilters($query);

        // Define expected statuses
        // $followUpStatuses = ['RFQ-Follow Up', 'Hot', 'Warm', 'Cold'];
        $followUpStatuses = ['Hot', 'Warm', 'Cold'];

        // Count total leads in the "Follow Up" stage
        $this->totalFollowUpLeads = $query->count();

        // Fetch leads grouped by their "lead_status"
        $followUpStatusCounts = $query
            ->whereIn('lead_status', $followUpStatuses)
            ->select('lead_status', DB::raw('COUNT(*) as total'))
            ->groupBy('lead_status')
            ->pluck('total', 'lead_status')
            ->toArray();

        // Ensure all statuses exist in the result, even if 0
        $this->followUpStatusData = array_merge(array_fill_keys($followUpStatuses, 0), $followUpStatusCounts);
    }

    public function openActiveLeadSlideOver()
    {
        $user = Auth::user();

        $query = Lead::where('categories', 'Active');

        $this->applyBaseFilters($query);

        $this->slideOverTitle = 'Active Lead Names';

        $this->leadList = $query->with('companyDetail')->get(); // ✅ gets full lead records with relationship
        $this->showSlideOver = true;
    }

    public function openInactiveLeadSlideOver()
    {
        $user = Auth::user();

        $query = Lead::where('categories', 'Inactive');

        $this->applyBaseFilters($query);

        $this->leadList = $query->with('companyDetail')->get();
        $this->slideOverTitle = 'Inactive Lead Names';
        $this->showSlideOver = true;
    }

    public function openCompanySizeSlideOver($label)
    {
        $user = Auth::user();

        // Map label to actual company_size values
        $sizeMap = [
            'Small' => '1-24',
            'Medium' => '25-99',
            'Large' => '100-500',
            'Enterprise' => '501 and Above',
        ];

        $companySize = $sizeMap[$label] ?? null;

        if (!$companySize) {
            $this->leadList = collect(); // empty collection
            $this->slideOverTitle = 'Unknown Company Size';
            $this->showSlideOver = true;
            return;
        }

        $query = Lead::where('company_size', $companySize);

        $this->applyBaseFilters($query);

        $this->leadList = $query->with('companyDetail')->get();
        $this->slideOverTitle = ucfirst($label) . ' Company Leads';
        $this->showSlideOver = true;
    }

    public function openStageLeadSlideOver($stage)
    {
        $user = Auth::user();
        $query = Lead::where('categories', 'Active')->where('stage', $stage);

        $this->applyBaseFilters($query);

        $this->leadList = $query->with('companyDetail')->get();
        $this->slideOverTitle = ucfirst($stage) . ' Leads';
        $this->showSlideOver = true;
    }

    public function openInactiveStatusSlideOver($status)
    {
        $user = Auth::user();
        $query = Lead::where('categories', 'Inactive')->where('lead_status', $status);

        $this->applyBaseFilters($query);

        $this->leadList = $query->with('companyDetail')->get();
        $this->slideOverTitle = ucfirst($status) . ' Inactive Leads';
        $this->showSlideOver = true;
    }

    public function openTransferSlideOver($status)
    {
        $user = Auth::user();
        $query = Lead::where('stage', 'Transfer')->where('lead_status', $status);

        $this->applyBaseFilters($query);

        $this->leadList = $query->with('companyDetail')->get();
        $this->slideOverTitle = "Transfer - " . ucfirst($status) . " Leads";
        $this->showSlideOver = true;
    }

    public function openFollowUpSlideOver($status)
    {
        $user = Auth::user();
        $query = Lead::where('stage', 'Follow Up')->where('lead_status', $status);

        $this->applyBaseFilters($query);

        $this->leadList = $query->with('companyDetail')->get();
        $this->slideOverTitle = "Follow Up - " . ucfirst($status) . " Leads";
        $this->showSlideOver = true;
    }
}
