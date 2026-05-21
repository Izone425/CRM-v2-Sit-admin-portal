<?php
namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\User;
use App\Models\Appointment;
use App\Models\Lead;
use App\Models\PublicHoliday;
use App\Models\UserLeave;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Illuminate\Support\Facades\Auth;

class MarketingAnalysis extends Page
{
    use InteractsWithPageTable;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static string $view = 'filament.pages.marketing-analysis';
    protected static ?string $navigationLabel = 'Marketing Analysis';
    protected static ?string $title = '';
    protected static ?int $navigationSort = 8;
    protected static ?string $navigationGroup = 'Analysis';

    public $users;
    public $selectedUser;
    // public $selectedMonth;
    public $selectedLeadCode;
    public $leadCodes;

    public $totalAppointments = 0;
    public $typeData = [];

    public $totalNewAppointments = 0;
    public $newDemoCompanySizeData = [];

    public $totalWebinarAppointments = 0;
    public $webinarDemoCompanySizeData = [];

    public $totalNewAppointmentsByLeadStatus = 0;
    public $newDemoLeadStatusData = [];

    public $totalWebinarAppointmentsByLeadStatus = 0;
    public $webinarDemoLeadStatusData = [];
    public $companySizeData = [];
    public $demoCompanySizeData = [];
    public $demoTypeData = [];
    public $demoRateBySize = [];
    public $webinarDemoAverages = [];
    public $selectedLeadOwner;
    public $leadOwners;
    public $companySizeDistribution = [];
    public $days;
    public Carbon $currentDate;
    public $startDate;
    public $endDate;

    public $utmCampaign;
    public $utmAdgroup;
    public $utmTerm;
    public $utmMatchtype;
    public $referrername;
    public $device;
    public $utmCreative;
    public $showUtmFilters = false;

    public $categoryData = [];
    public $totalLeadsByCategory = 0;

    public $stageData = [];
    public $totalLeadsByStage = 0;

    public $leadStatusData = [];
    public $totalLeadStatus = 0;

    public $closeWonAmount = 0;
    public $closedDealsCount = 0;
    public $monthlyDealAmounts = [];

    public $closedWonBySource = [];

    public $appointmentTypeBySource = [];

    public $noResponseByCallAttempt = [];

    //Slide Modal Variables
    public $showSlideOver = false;
    public $slideOverTitle = '';
    public $slideOverList = [];
    public $slideOverAmounts = []; // [lead_id => close-won amount] when relevant

    public $excludeLeadCodes = [];
    public $isExcludingLeadCodes = false;
    public $includePG = false;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (!$user || !($user instanceof \App\Models\User)) {
            return false;
        }

        return $user->hasRouteAccess('filament.admin.pages.marketing-analysis');
    }

    public function mount()
    {
        $authUser = auth()->user();
        $this->currentDate = Carbon::now();
        $this->startDate = session('startDate', $this->currentDate->copy()->startOfMonth()->toDateString());
        $this->endDate = session('endDate', $this->currentDate->toDateString());

        // Fetch only Salespersons (role_id = 2)
        $this->users = User::where('role_id', 2)->get();
        $this->leadOwners = User::where('role_id', 1)->get();

        // Fetch unique lead codes for dropdown options
        $this->leadCodes = Lead::select('lead_code')->distinct()->pluck('lead_code')->toArray();

        // Set default selected user based on role
        if ($authUser->role_id == 1) {
            $this->selectedUser = session('selectedUser', null);
        } elseif ($authUser->role_id == 2) {
            $this->selectedUser = $authUser->id; // Salesperson can only see their data
        }

        // Set default selected month
        // $this->selectedMonth = session('selectedMonth', $this->currentDate->format('Y-m'));

        // Set default selected lead code
        $this->selectedLeadCode = session('selectedLeadCode', null);

        $this->excludeLeadCodes = [
            'Fendi Leads',
            'Wirson leads',
            'TimeTec HR / SalesPerson / Leads',
            'Reseller Leads',
            'Reseller lead',
            'SalesPerson Leads',
            'Refer & Earn (Sales)',
            'Jonathan Leads',
            'Existing Customer',
            'Existing Customer (Migration)',
            'FingerTec Leads',
            'Apollo',
            'Cold Call',
            'Referral (Mike)',
        ];

        $this->isExcludingLeadCodes = session('isExcludingLeadCodes', false);
        $this->includePG = session('includePG', false);

        session(['isExcludingLeadCodes' => $this->isExcludingLeadCodes]);
        session(['includePG' => $this->includePG]);

        // Store in session
        session(['selectedUser' => $this->selectedUser, 'selectedLeadCode' => $this->selectedLeadCode]);

        $this->selectedLeadOwner = session('selectedLeadOwner', null);
        session(['selectedLeadOwner' => $this->selectedLeadOwner]);

        // Fetch initial appointment data
        $this->refreshDashboardData();
    }

    public function toggleExcludeLeadCodes()
    {
        $this->isExcludingLeadCodes = !$this->isExcludingLeadCodes;
        session(['isExcludingLeadCodes' => $this->isExcludingLeadCodes]);
        $this->refreshDashboardData();
    }

    public function togglePGFilter()
    {
        $this->includePG = !$this->includePG;
        session(['includePG' => $this->includePG]);
        $this->refreshDashboardData();
    }

    private function applyLeadCodeExclusions($query)
    {
        if ($this->isExcludingLeadCodes && !empty($this->excludeLeadCodes)) {
            $query->whereNotIn('lead_code', $this->excludeLeadCodes);
        }

        if (!$this->includePG) {
            $query->where('lead_code', '!=', 'Google AdWords (PG)');
        }

        return $query;
    }

    private function applyBaseFilters($query)
    {
        // Apply UTM filters if any
        $utmLeadIds = $this->getLeadIdsFromUtmFilters();
        $utmFilterApplied = $this->utmCampaign || $this->utmAdgroup || $this->utmTerm ||
                            $this->utmMatchtype || $this->referrername || $this->device || $this->utmCreative;

        if ($utmFilterApplied && !empty($utmLeadIds)) {
            $query->whereIn('id', $utmLeadIds);
        }

        // Apply lead code exclusions
        if ($this->isExcludingLeadCodes && !empty($this->excludeLeadCodes)) {
            $query->whereNotIn('lead_code', $this->excludeLeadCodes);
        }

        if (!$this->includePG) {
            $query->where('lead_code', '!=', 'Google AdWords (PG)');
        }

        // Exclude existing customer and null company_size
        $query->where(function($q) {
            $q->whereNotIn('lead_code', ['Existing Customer', 'Existing Customer (Migration)'])
            ->orWhereNull('lead_code');
        })->whereNotNull('company_size');

        // Apply lead owner filter
        if (!empty($this->selectedLeadOwner)) {
            $ownerName = User::where('id', $this->selectedLeadOwner)->value('name');
            $query->where('lead_owner', $ownerName);
        }

        // Apply role-based filtering
        $user = Auth::user();
        if (in_array($user->role_id, [1, 3]) && $this->selectedUser) {
            $query->where('salesperson', $this->selectedUser);
        }

        if ($user->role_id == 2) {
            $query->where('salesperson', $user->id);
        }

        // Apply date range filter
        if (!empty($this->startDate) && !empty($this->endDate)) {
            $query->whereBetween('created_at', [
                Carbon::parse($this->startDate)->startOfDay(),
                Carbon::parse($this->endDate)->endOfDay(),
            ]);
        }

        // Apply lead code filter
        if (!empty($this->selectedLeadCode)) {
            if ($this->selectedLeadCode === 'Null') {
                $query->whereNull('lead_code');
            } else {
                $query->where('lead_code', $this->selectedLeadCode);
            }
        }

        return $query;
    }

    private function applyBaseFiltersByClosingDate($query)
    {
        // Apply UTM filters if any
        $utmLeadIds = $this->getLeadIdsFromUtmFilters();
        $utmFilterApplied = $this->utmCampaign || $this->utmAdgroup || $this->utmTerm ||
                            $this->utmMatchtype || $this->referrername || $this->device || $this->utmCreative;

        if ($utmFilterApplied && !empty($utmLeadIds)) {
            $query->whereIn('id', $utmLeadIds);
        }

        // Apply lead code exclusions
        if ($this->isExcludingLeadCodes && !empty($this->excludeLeadCodes)) {
            $query->whereNotIn('lead_code', $this->excludeLeadCodes);
        }

        if (!$this->includePG) {
            $query->where('lead_code', '!=', 'Google AdWords (PG)');
        }

        // Exclude existing customer and null company_size
        $query->where(function($q) {
            $q->whereNotIn('lead_code', ['Existing Customer', 'Existing Customer (Migration)'])
            ->orWhereNull('lead_code');
        })->whereNotNull('company_size');

        // Apply lead owner filter
        if (!empty($this->selectedLeadOwner)) {
            $ownerName = User::where('id', $this->selectedLeadOwner)->value('name');
            $query->where('lead_owner', $ownerName);
        }

        // Apply role-based filtering
        $user = Auth::user();
        if (in_array($user->role_id, [1, 3]) && $this->selectedUser) {
            $query->where('salesperson', $this->selectedUser);
        }

        if ($user->role_id == 2) {
            $query->where('salesperson', $user->id);
        }

        // Apply date range filter
        if (!empty($this->startDate) && !empty($this->endDate)) {
            $query->whereBetween('closing_date', [
                Carbon::parse($this->startDate)->startOfDay(),
                Carbon::parse($this->endDate)->endOfDay(),
            ]);
        }

        // Apply lead code filter
        if (!empty($this->selectedLeadCode)) {
            if ($this->selectedLeadCode === 'Null') {
                $query->whereNull('lead_code');
            } else {
                $query->where('lead_code', $this->selectedLeadCode);
            }
        }

        return $query;
    }

    public function updatedSelectedUser($userId)
    {
        $this->selectedUser = $userId;
        session(['selectedUser' => $userId]);
        $this->refreshDashboardData();
    }

    public function updatedStartDate($value)
    {
        $this->startDate = $value;
        session(['startDate' => $value]);
        $this->refreshDashboardData();
    }

    public function updatedEndDate($value)
    {
        $this->endDate = $value;
        session(['endDate' => $value]);
        $this->refreshDashboardData();
    }

    public function updatedSelectedLeadCode($leadCode)
    {
        $this->selectedLeadCode = $leadCode;
        session(['selectedLeadCode' => $leadCode]);
        $this->refreshDashboardData();
    }

    public function updatedSelectedLeadOwner($value)
    {
        session(['selectedLeadOwner' => $value]);
        $this->refreshDashboardData();
    }

    public function refreshDashboardData()
    {
        $this->fetchLeads();
        $this->fetchLeadsDemo();
        $this->getLeadTypeCounts();
        $this->fetchLeadsDemoType();
        $this->calculateFilteredDemoRateByCompanySize();
        $this->fetchCloseWonAmount();
        $this->fetchMonthlyDealAmounts();
        $this->fetchLeadStatusSummary();
        $this->calculateWebinarDemoAverages();
        $this->fetchClosedWonBySource();
        $this->fetchAppointmentTypeBySource();
        $this->noResponseByCallAttempt = $this->fetchNoResponseByCallAttempts();
    }

    public function updated($propertyName)
    {
        if (in_array($propertyName, [
            'utmCampaign',
            'utmAdgroup',
            'utmTerm',
            'utmMatchtype',
            'referrername',
            'device',
            'utmCreative',
        ])) {
            $this->refreshDashboardData();
        }
    }

    public function toggleUtmFilters()
    {
        $this->showUtmFilters = !$this->showUtmFilters;
    }

    public function getLeadIdsFromUtmFilters()
    {
        $query = \App\Models\UtmDetail::query();

        if (!empty($this->utmCampaign)) {
            $query->where('utm_campaign', '=', $this->utmCampaign);
        }
        if (!empty($this->utmAdgroup)) {
            $query->where('utm_adgroup', '=', $this->utmAdgroup);
        }
        if (!empty($this->utmTerm)) {
            $query->where('utm_term', '=', $this->utmTerm);
        }
        if (!empty($this->utmMatchtype)) {
            $query->where('utm_matchtype', 'like', '%' . $this->utmMatchtype . '%');
        }
        if (!empty($this->referrername)) {
            $query->where('referrername', 'like', '%' . $this->referrername . '%');
        }
        if (!empty($this->device)) {
            $query->where('device', 'like', '%' . $this->device . '%');
        }
        if (!empty($this->utmCreative)) {
            $query->where('utm_creative', '=', $this->utmCreative);
        }
        return $query->pluck('lead_id')->toArray();
    }

    public function fetchLeadStatusSummary()
    {
        $user = Auth::user();

        $utmLeadIds = $this->getLeadIdsFromUtmFilters();
        $utmFilterApplied = $this->utmCampaign || $this->utmAdgroup || $this->utmTerm || $this->utmMatchtype || $this->referrername || $this->device || $this->utmCreative;

        $activeStatuses = [
            'None','New','RFQ-Transfer','Pending Demo','Under Review','Demo Cancelled',
            'Demo-Assigned','Hot','Warm','Cold'
        ];

        $otherStatuses = ['Closed', 'No Response', 'Junk', 'On Hold', 'Lost'];

        $allStatuses = array_merge($activeStatuses, $otherStatuses);

        $query = Lead::query();

        if ($this->isExcludingLeadCodes && !empty($this->excludeLeadCodes)) {
            $query->whereNotIn('lead_code', $this->excludeLeadCodes);
        }

        if ($utmFilterApplied && !empty($utmLeadIds)) {
            $query->whereIn('id', $utmLeadIds);
        }

        if (!empty($this->selectedLeadOwner)) {
            $ownerName = User::where('id', $this->selectedLeadOwner)->value('name');
            $query->where('lead_owner', $ownerName);
        }

        if (in_array($user->role_id, [1, 3]) && $this->selectedUser) {
            $query->where('salesperson', $this->selectedUser);
        }

        if ($user->role_id === 2) {
            $query->where('salesperson', $user->id);
        }

        if (!empty($this->startDate) && !empty($this->endDate)) {
            $query->whereBetween('created_at', [
                Carbon::parse($this->startDate)->startOfDay(),
                Carbon::parse($this->endDate)->endOfDay(),
            ]);
        }

        if (!empty($this->selectedLeadCode)) {
            if ($this->selectedLeadCode === 'Null') {
                $query->whereNull('lead_code');
            } else {
                $query->where('lead_code', $this->selectedLeadCode);
            }
        }

        // $query->where(function ($q) use ($activeStatuses, $otherStatuses) {
        //     $q->where(function ($sub) use ($activeStatuses) {
        //         $sub->where(function ($inner) {
        //             $inner->where('categories', 'Active')
        //                   ->orWhere('categories', 'New');
        //         })->whereIn('lead_status', $activeStatuses);
        //     })->orWhereIn('lead_status', $otherStatuses);
        // });

        // ✅ Total Count
        $this->totalLeadStatus = (clone $query)->count();

        // ✅ Status-wise count
        $statusCounts = $query
            ->select('lead_status', DB::raw('COUNT(*) as total'))
            ->groupBy('lead_status')
            ->pluck('total', 'lead_status')
            ->toArray();

        // ✅ Fill missing ones with 0
        $this->leadStatusData = collect(array_merge(array_fill_keys($allStatuses, 0), $statusCounts))
            ->sortDesc()
            ->toArray();
    }

    public function getLeadTypeCounts()
    {
        $user = Auth::user();
        $query = Lead::query();

        // UTM filter
        $utmLeadIds = $this->getLeadIdsFromUtmFilters();
        $utmFilterApplied = $this->utmCampaign || $this->utmAdgroup || $this->utmTerm || $this->utmMatchtype || $this->referrername || $this->device || $this->utmCreative;

        if ($this->isExcludingLeadCodes && !empty($this->excludeLeadCodes)) {
            $query->whereNotIn('lead_code', $this->excludeLeadCodes);
        }

        if ($utmFilterApplied && !empty($utmLeadIds)) {
            $query->whereIn('id', $utmLeadIds);
        }

        if (in_array($user->role_id, [1, 3]) && $this->selectedUser) {
            $query->where('salesperson', $this->selectedUser);
        }

        if ($user->role_id == 2) {
            $query->where('salesperson', $user->id);
        }

        if (!empty($this->selectedLeadOwner)) {
            $ownerName = User::where('id', $this->selectedLeadOwner)->value('name');
            $query->where('lead_owner', $ownerName);
        }

        if (!empty($this->startDate) && !empty($this->endDate)) {
            $query->whereBetween('created_at', [
                Carbon::parse($this->startDate)->startOfDay(),
                Carbon::parse($this->endDate)->endOfDay(),
            ]);
        }

        if (!empty($this->selectedLeadCode)) {
            if ($this->selectedLeadCode === 'Null') {
                $query->whereNull('lead_code');
            } else {
                $query->where('lead_code', $this->selectedLeadCode);
            }
        }

        // Get all leads for counting
        $leads = $query->get();

        // Create a dynamic count for ALL lead codes
        $result = [];

        // Group leads by lead_code and count them directly into the result array
        foreach ($leads as $lead) {
            // Use string keys and handle null values
            $code = (string)($lead->lead_code ?? 'Null');

            if (!isset($result[$code])) {
                $result[$code] = 0;
            }
            $result[$code]++;
        }

        // Get distinct lead codes for the dropdown
        $this->leadCodes = Lead::query()
            ->select('lead_code')
            ->distinct()
            ->pluck('lead_code')
            ->map(function ($value) {
                return (string)($value ?? 'Null'); // Ensure string and replace null with 'Null'
            })
            ->sortBy(function($code) {
                return $code; // Sort by string value
            })
            ->values()
            ->toArray();

        return $result;
    }

    public function fetchLeads()
    {
        $user = Auth::user();
        $query = Lead::query();

        $this->applyBaseFilters($query);

        $query->where(function ($q) {
            $q->whereNotIn('lead_status', ['Junk', 'On Hold', 'Lost'])
            ->orWhere(function ($sub) {
                $sub->where('lead_status', 'Lost')
                    ->whereNotNull('demo_appointment');
            });
        });

        // Fetch filtered leads
        $leads = $query->get();

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

        $this->companySizeDistribution = array_merge($defaultCompanySizes, $companySizeCounts);
    }

    public function fetchLeadsDemo()
    {
        $user = Auth::user();
        $query = Lead::query();

        $this->applyBaseFilters($query);

        // ✅ Custom status filtering
        $query->where(function ($q) {
            $q->whereIn('lead_status', [
                'Closed',
                'Demo-Assigned',
                'RFQ-Follow Up',
                'Hot',
                'Warm',
                'Cold',
            ])
            ->orWhere(function ($sub) {
                $sub->whereIn('lead_status', ['Lost', 'No Response'])
                    ->whereNotNull('demo_appointment');
            });
        });

        // Get filtered leads
        $leads = $query->get();

        // Group by company size
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

        $this->demoCompanySizeData = array_merge($defaultCompanySizes, $companySizeCounts);
    }

    public function fetchLeadsDemoType()
    {
        $user = Auth::user();
        $query = Lead::query();

        $this->applyBaseFilters($query);

        // ✅ Lead status filter
        $query->where(function ($q) {
            $q->whereIn('lead_status', [
                'Closed',
                'Demo-Assigned',
                'RFQ-Follow Up',
                'Hot',
                'Warm',
                'Cold',
            ])
            ->orWhere(function ($sub) {
                $sub->whereIn('lead_status', ['Lost', 'No Response'])
                    ->whereNotNull('demo_appointment');
            });
        });

        // Load leads with demo appointments
        $leads = $query->with('demoAppointment')->get();

        $newDemoCount = 0;
        $webinarKeys = [];

        foreach ($leads as $lead) {
            $appointments = $lead->demoAppointment ?? collect();

            foreach ($appointments as $demo) {
                if ($demo->status === 'Cancelled') {
                    continue;
                }

                if ($demo->type === 'NEW DEMO') {
                    $newDemoCount++;
                } elseif ($demo->type === 'WEBINAR DEMO') {
                    $key = $demo->date . '|' . $demo->start_time . '|' . $demo->end_time . '|' . $demo->salesperson;
                    $webinarKeys[$key] = true;
                }
            }
        }

        $this->demoTypeData = [
            'New Demo' => $newDemoCount,
            'Webinar Demo' => count($webinarKeys),
        ];
    }

    public function calculateFilteredDemoRateByCompanySize()
    {
        $user = Auth::user();
        $query = Lead::query();

        $this->applyBaseFilters($query);

        // ✅ Exclude junk/on hold/lost unless has demo
        $query->where(function ($q) {
            $q->whereNotIn('lead_status', ['Junk', 'On Hold', 'Lost'])
            ->orWhere(function ($sub) {
                $sub->where('lead_status', 'Lost')
                    ->whereNotNull('demo_appointment');
            });
        });

        // Fetch filtered leads
        $leads = $query->get();

        $defaultCompanySizes = [
            'Small' => 0,
            'Medium' => 0,
            'Large' => 0,
            'Enterprise' => 0,
        ];

        // 🟡 Total Leads by Company Size
        $companySizeCounts = $leads
            ->whereNotNull('company_size_label')
            ->groupBy('company_size_label')
            ->map(fn($group) => $group->count())
            ->toArray();

        $this->companySizeData = array_merge($defaultCompanySizes, $companySizeCounts);

        // 🔵 Demo Leads by Company Size
        $demoLeads = $leads->filter(function ($lead) {
            return in_array($lead->lead_status, [
                'Closed', 'Demo-Assigned', 'RFQ-Follow Up', 'Hot', 'Warm', 'Cold',
            ]) || (
                in_array($lead->lead_status, ['Lost', 'No Response']) &&
                $lead->demo_appointment !== null
            );
        });

        $demoSizeCounts = $demoLeads
            ->whereNotNull('company_size_label')
            ->groupBy('company_size_label')
            ->map(fn($group) => $group->count())
            ->toArray();

        $this->demoCompanySizeData = array_merge($defaultCompanySizes, $demoSizeCounts);

        // 🔢 Calculate Demo Rate
        $sizes = ['Small', 'Medium', 'Large', 'Enterprise'];
        $demoRates = [];

        foreach ($sizes as $size) {
            $total = $this->companySizeData[$size] ?? 0;
            $demo = $this->demoCompanySizeData[$size] ?? 0;

            $demoRates[$size] = $total > 0
                ? round(($demo / $total) * 100, 2)
                : 0;
        }

        $this->demoRateBySize = $demoRates;
    }

    public function calculateWebinarDemoAverages()
    {
        $this->webinarDemoAverages = [];

        $user = Auth::user();
        $query = Lead::query();

        $this->applyBaseFilters($query);

        // ✅ Lead status filter
        $query->where(function ($q) {
            $q->whereIn('lead_status', [
                'Closed',
                'Demo-Assigned',
                'RFQ-Follow Up',
                'Hot',
                'Warm',
                'Cold',
            ])
            ->orWhere(function ($sub) {
                $sub->whereIn('lead_status', ['Lost', 'No Response'])
                    ->whereNotNull('demo_appointment');
            });
        });

        // ✅ Filter by lead created_at instead of demo date
        if (!empty($this->startDate) && !empty($this->endDate)) {
            $query->whereBetween('created_at', [
                Carbon::parse($this->startDate)->startOfDay(),
                Carbon::parse($this->endDate)->endOfDay(),
            ]);
        }

        if (!empty($this->selectedLeadCode)) {
            if ($this->selectedLeadCode === 'Null') {
                $query->whereNull('lead_code');
            } else {
                $query->where('lead_code', $this->selectedLeadCode);
            }
        }

        // Load leads with demo appointments
        $leads = $query->with('demoAppointment')->get();

        $webinarData = [];

        foreach ($leads as $lead) {
            $appointments = $lead->demoAppointment ?? collect();

            foreach ($appointments as $demo) {
                // ✅ Still skip cancelled demos and only include WEBINAR DEMO
                if ($demo->type !== 'WEBINAR DEMO' || $demo->status === 'Cancelled') {
                    continue;
                }

                $salespersonId = $demo->salesperson;
                $key = $demo->date . '|' . $demo->start_time . '|' . $demo->end_time . '|' . $salespersonId . '|' . $lead->id;

                if (!isset($webinarData[$key])) {
                    $webinarData[$key] = [
                        'salesperson_id' => $salespersonId,
                        'lead_count' => 0,
                    ];
                }

                $webinarData[$key]['lead_count'] = 1;
            }
        }

        // Final summary
        $uniqueSessions = [];

        foreach ($webinarData as $key => $data) {
            $salespersonId = $data['salesperson_id'];

            // Extract session key (remove leadId)
            [$date, $startTime, $endTime, $salesperson] = explode('|', $key);
            $sessionKey = $date . '|' . $startTime . '|' . $endTime . '|' . $salesperson;

            if (!isset($uniqueSessions[$salespersonId])) {
                $uniqueSessions[$salespersonId] = [];
            }

            if (!in_array($sessionKey, $uniqueSessions[$salespersonId])) {
                $uniqueSessions[$salespersonId][] = $sessionKey;

                if (!isset($this->webinarDemoAverages[$salespersonId])) {
                    $this->webinarDemoAverages[$salespersonId] = [
                        'webinar_count' => 0,
                        'total_leads' => 0,
                    ];
                }

                $this->webinarDemoAverages[$salespersonId]['webinar_count'] += 1;
            }

            $this->webinarDemoAverages[$salespersonId]['total_leads'] += $data['lead_count'];
        }

        // Convert to name-indexed output
        foreach ($this->webinarDemoAverages as $salespersonId => $summary) {
            $average = $summary['webinar_count'] > 0
                ? round($summary['total_leads'] / $summary['webinar_count'], 2)
                : 0;

            $salespersonName = User::find($salespersonId)?->name ?? 'Unknown';

            if (empty($salespersonName) || $salespersonName === 'Unknown') {
                continue;
            }

            $this->webinarDemoAverages[$salespersonName] = [
                'webinar_count' => $summary['webinar_count'],
                'total_leads' => $summary['total_leads'],
                'average_per_webinar' => $average,
            ];

            unset($this->webinarDemoAverages[$salespersonId]);
        }
    }

    public function fetchCloseWonAmount()
    {
        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();

        $result = $this->computeCloseWonForRange($start, $end);
        $this->closedDealsCount = $result['count'];
        $this->closeWonAmount = $result['amount'];
    }

    /**
     * Gather unique quotation IDs that count as close-won for a date window,
     * using the priority-ordered handover sources: software > hardware > headcount.
     *
     * `$taken` holds quotation IDs whose amounts get summed. `$blocked` holds
     * IDs that are already represented inside a software handover (via the
     * software_hardware_pi link) but whose amounts must NOT be re-summed —
     * they're the hardware twin of the software deal, otherwise we'd double-count.
     */
    private function gatherCloseWonQuotationIds(Carbon $start, Carbon $end): array
    {
        $taken = [];
        $blocked = [];

        // 1) software_handovers — column choice driven by training_type.
        //    Date window is the linked lead's closing_date, not the handover's created_at.
        //    Also collect software_hardware_pi IDs as "blocked" so the matching
        //    hardware row doesn't add the same deal twice.
        $softwareRows = DB::table('software_handovers as sh')
            ->join('leads as l', 'l.id', '=', 'sh.lead_id')
            ->whereBetween('l.closing_date', [$start, $end])
            ->whereIn('sh.training_type', ['online_webinar_training', 'online_hrdf_training'])
            ->get(['sh.training_type', 'sh.proforma_invoice_product', 'sh.proforma_invoice_hrdf', 'sh.software_hardware_pi']);

        foreach ($softwareRows as $row) {
            $col = $row->training_type === 'online_webinar_training'
                ? 'proforma_invoice_product'
                : 'proforma_invoice_hrdf';
            foreach ($this->decodeQuotationIds($row->{$col}) as $id) {
                $taken[$id] = true;
            }
            foreach ($this->decodeQuotationIds($row->software_hardware_pi) as $id) {
                $blocked[$id] = true;
            }
        }

        // 2) hardware_handovers_v2 — skip if already taken or blocked by software.
        $hardwareRows = DB::table('hardware_handovers_v2 as hh')
            ->join('leads as l', 'l.id', '=', 'hh.lead_id')
            ->whereBetween('l.closing_date', [$start, $end])
            ->get(['hh.proforma_invoice_product']);

        foreach ($hardwareRows as $row) {
            foreach ($this->decodeQuotationIds($row->proforma_invoice_product) as $id) {
                if (isset($taken[$id]) || isset($blocked[$id])) continue;
                $taken[$id] = true;
            }
        }

        // 3) headcount_handovers — skip if already taken or blocked.
        $headcountRows = DB::table('headcount_handovers as hc')
            ->join('leads as l', 'l.id', '=', 'hc.lead_id')
            ->whereBetween('l.closing_date', [$start, $end])
            ->get(['hc.proforma_invoice_product']);

        foreach ($headcountRows as $row) {
            foreach ($this->decodeQuotationIds($row->proforma_invoice_product) as $id) {
                if (isset($taken[$id]) || isset($blocked[$id])) continue;
                $taken[$id] = true;
            }
        }

        return array_keys($taken);
    }

    /**
     * Aggregate close-won figures for a date window — sum of
     * quotation_details.total_before_tax for the handover-derived quotation IDs.
     */
    private function computeCloseWonForRange(Carbon $start, Carbon $end): array
    {
        $uniqueIds = $this->gatherCloseWonQuotationIds($start, $end);

        if (empty($uniqueIds)) {
            return ['amount' => 0.0, 'count' => 0];
        }

        $amount = (float) DB::table('quotation_details')
            ->whereIn('quotation_id', $uniqueIds)
            ->sum('total_before_tax');

        return ['amount' => $amount, 'count' => count($uniqueIds)];
    }

    /**
     * Resolve the handover-derived close-won quotation IDs back to lead IDs.
     */
    private function closeWonLeadIds(Carbon $start, Carbon $end): array
    {
        $quotationIds = $this->gatherCloseWonQuotationIds($start, $end);
        if (empty($quotationIds)) {
            return [];
        }

        return DB::table('quotations')
            ->whereIn('id', $quotationIds)
            ->pluck('lead_id')
            ->filter()
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Return [lead_id => close-won amount] for a date window — sums
     * quotation_details.total_before_tax per lead across that lead's
     * handover-derived quotation IDs.
     */
    private function closeWonAmountByLead(Carbon $start, Carbon $end): array
    {
        $quotationIds = $this->gatherCloseWonQuotationIds($start, $end);
        if (empty($quotationIds)) {
            return [];
        }

        $quotationToLead = DB::table('quotations')
            ->whereIn('id', $quotationIds)
            ->pluck('lead_id', 'id');

        $amountPerQuotation = DB::table('quotation_details')
            ->whereIn('quotation_id', $quotationIds)
            ->select('quotation_id', DB::raw('SUM(total_before_tax) as amount'))
            ->groupBy('quotation_id')
            ->pluck('amount', 'quotation_id');

        $amountPerLead = [];
        foreach ($quotationToLead as $qid => $leadId) {
            if (!$leadId) continue;
            $amountPerLead[$leadId] = ($amountPerLead[$leadId] ?? 0) + (float) ($amountPerQuotation[$qid] ?? 0);
        }
        return $amountPerLead;
    }

    private function decodeQuotationIds($raw): array
    {
        if (empty($raw)) {
            return [];
        }
        $decoded = is_string($raw) ? json_decode($raw, true) : $raw;
        // Handle double-encoded JSON (e.g. software_hardware_pi sometimes stored as "\"[\\\"6060\\\"]\"").
        if (is_string($decoded)) {
            $decoded = json_decode($decoded, true);
        }
        if (!is_array($decoded)) {
            return [];
        }
        $ids = [];
        foreach ($decoded as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $ids[] = $id;
            }
        }
        return $ids;
    }

    public function fetchMonthlyDealAmounts()
    {
        $start = Carbon::parse($this->startDate)->startOfMonth();
        $end = Carbon::parse($this->endDate)->endOfMonth();
        $period = \Carbon\CarbonPeriod::create($start, '1 month', $end);

        $data = [];
        foreach ($period as $date) {
            $monthStart = $date->copy()->startOfMonth();
            $monthEnd = $date->copy()->endOfMonth();
            $result = $this->computeCloseWonForRange($monthStart, $monthEnd);
            $data[$date->format('Y-m')] = $result['amount'];
        }

        $this->monthlyDealAmounts = $data;
    }

    public function fetchClosedWonBySource()
    {
        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();

        $quotationIds = $this->gatherCloseWonQuotationIds($start, $end);

        if (empty($quotationIds)) {
            $this->closedWonBySource = [];
            return;
        }

        // Map each quotation to its lead and the line-item amount.
        $quotationLeadMap = DB::table('quotations')
            ->whereIn('id', $quotationIds)
            ->pluck('lead_id', 'id'); // [quotation_id => lead_id]

        $amountByQuotation = DB::table('quotation_details')
            ->whereIn('quotation_id', $quotationIds)
            ->select('quotation_id', DB::raw('SUM(total_before_tax) as amount'))
            ->groupBy('quotation_id')
            ->pluck('amount', 'quotation_id'); // [quotation_id => amount]

        $leadIds = $quotationLeadMap->filter()->unique()->values()->all();
        $leadCodes = Lead::whereIn('id', $leadIds)->pluck('lead_code', 'id'); // [lead_id => lead_code]

        $closedWonData = [];
        $totalAmount = 0.0;

        foreach ($quotationIds as $qid) {
            $leadId   = $quotationLeadMap[$qid] ?? null;
            $source   = $leadId ? ($leadCodes[$leadId] ?? 'Unknown') : 'Unknown';
            $amount   = (float) ($amountByQuotation[$qid] ?? 0);

            if (!isset($closedWonData[$source])) {
                $closedWonData[$source] = ['amount' => 0.0, 'count' => 0, 'percentage' => 0];
            }
            $closedWonData[$source]['amount'] += $amount;
            $closedWonData[$source]['count']++;
            $totalAmount += $amount;
        }

        if ($totalAmount > 0) {
            foreach ($closedWonData as $source => &$row) {
                $row['percentage'] = round(($row['amount'] / $totalAmount) * 100, 2);
            }
            unset($row);
        }

        uasort($closedWonData, fn ($a, $b) => $b['amount'] <=> $a['amount']);
        $this->closedWonBySource = $closedWonData;
    }

    public function fetchAppointmentTypeBySource()
    {
        $user = Auth::user();
        $query = Lead::query();

        $this->applyBaseFilters($query);

        // Get leads with appointments
        $leads = $query->with('demoAppointment')->get();

        // Step 1: Dynamically collect all unique lead sources from the data
        $allLeadSources = $leads->pluck('lead_code')->unique()->map(function ($source) {
            return $source ?? 'Null';  // Convert null to 'Null' string
        })->values()->toArray();

        // Step 2: Initialize results array with dynamic lead sources
        $result = [];
        foreach ($allLeadSources as $source) {
            $result[$source] = [
                'NEW DEMO' => 0,
                'WEBINAR DEMO' => 0,
            ];
        }

        // Step 3: Dynamically collect all unique appointment types
        $appointmentTypes = [];
        foreach ($leads as $lead) {
            $appointments = $lead->demoAppointment ?? collect();
            foreach ($appointments as $appointment) {
                if ($appointment->status !== 'Cancelled' && !empty($appointment->type)) {
                    $appointmentTypes[$appointment->type] = true;
                }
            }
        }
        $appointmentTypes = array_keys($appointmentTypes);

        // Step 4: Add any missing appointment types to the result structure
        foreach ($result as $source => $types) {
            foreach ($appointmentTypes as $type) {
                if (!isset($result[$source][$type])) {
                    $result[$source][$type] = 0;
                }
            }
        }

        // Step 5: Count appointments by source and type
        foreach ($leads as $lead) {
            $source = $lead->lead_code ?? 'Null';
            $appointments = $lead->demoAppointment ?? collect();

            foreach ($appointments as $appointment) {
                if ($appointment->status === 'Cancelled') {
                    continue;
                }

                if (isset($result[$source][$appointment->type])) {
                    $result[$source][$appointment->type]++;
                }
            }
        }

        // Step 6: Sort by total appointments (descending)
        $sortedResults = collect($result)->map(function ($types, $source) {
            $total = array_sum($types);
            return [
                'source' => $source,
                'types' => $types,
                'total' => $total
            ];
        })->sortByDesc('total')->values()->all();

        $this->appointmentTypeBySource = $sortedResults;
    }

    public function fetchNoResponseByCallAttempts()
    {
        $user = Auth::user();
        $query = Lead::query();

        $this->applyBaseFilters($query);

        // Only get leads with No Response status
        $query->where('lead_status', 'No Response');

        // Get the leads
        $leads = $query->get();

        // Find the maximum call attempt value
        $maxCallAttempt = 0;
        foreach ($leads as $lead) {
            $attempts = (int)$lead->call_attempt;
            if ($attempts > $maxCallAttempt) {
                $maxCallAttempt = $attempts;
            }
        }

        // Create call attempt groups dynamically based on data
        $callAttemptGroups = [];
        for ($i = 0; $i <= $maxCallAttempt; $i++) {
            $callAttemptGroups[(string)$i] = 0;
        }

        // Count leads per call attempt
        foreach ($leads as $lead) {
            $attempts = (int)$lead->call_attempt;
            $callAttemptGroups[(string)$attempts]++;
        }

        return $callAttemptGroups;
    }

    public function openLeadStatusSlideOver($status)
    {
        $this->slideOverTitle = "Leads - " . ucfirst($status);

        $query = Lead::with('companyDetail')->where('lead_status', $status);

        $this->applyBaseFilters($query);

        $this->slideOverList = $query->get();
        $this->showSlideOver = true;
    }

    public function openLeadSourceSlideOver($source)
    {
        $this->slideOverTitle = "Leads from: " . $source;

        $user = Auth::user();
        $query = Lead::with('companyDetail');

        if ($source === 'Null') {
            $query->whereNull('lead_code');
        } else {
            $query->where('lead_code', $source);
        }

        $this->applyBaseFilters($query);

        $this->slideOverList = $query->get();
        $this->showSlideOver = true;
    }

    public function openCompanySizeSlideOver($label)
    {
        $this->slideOverTitle = "Company Size: " . ucfirst($label);

        $sizeMap = [
            'Small' => ['1-24', '20-24', '1-19'],
            'Medium' => ['25-99'],
            'Large' => ['100-500'],
            'Enterprise' => ['501 and Above'],
        ];

        $rawSizes = $sizeMap[$label] ?? [];

        $query = Lead::with('companyDetail')->whereIn('company_size', $rawSizes);

        $this->applyBaseFilters($query);

        // Same filtering logic as fetchLeads()
        $query->where(function ($q) {
            $q->whereNotIn('lead_status', ['Junk', 'On Hold', 'Lost'])
            ->orWhere(function ($sub) {
                $sub->where('lead_status', 'Lost')->whereNotNull('demo_appointment');
            });
        });

        $this->slideOverList = $query->get();
        $this->showSlideOver = true;
    }

    public function openDemoCompanySizeSlideOver($label)
    {
        $this->slideOverTitle = "Demo Leads - " . ucfirst($label);

        $sizeMap = [
            'Small' => ['1-24', '20-24', '1-19'],
            'Medium' => ['25-99'],
            'Large' => ['100-500'],
            'Enterprise' => ['501 and Above'],
        ];

        $rawSizes = $sizeMap[$label] ?? [];

        $query = Lead::with('companyDetail')
            ->whereIn('company_size', $rawSizes)
            ->where(function ($q) {
                $q->whereIn('lead_status', [
                    'Closed',
                    'Demo-Assigned',
                    'RFQ-Follow Up',
                    'Hot',
                    'Warm',
                    'Cold',
                ])->orWhere(function ($sub) {
                    $sub->whereIn('lead_status', ['Lost', 'No Response'])
                        ->whereNotNull('demo_appointment');
                });
            });

        $this->applyBaseFilters($query);

        $this->slideOverList = $query->get();
        $this->showSlideOver = true;
    }

    public function openDemoTypeSlideOver($type)
    {
        $this->slideOverTitle = $type . ' Leads';

        $user = Auth::user();
        $query = Lead::query();

        $this->applyBaseFilters($query);

        $query->where(function ($q) {
            $q->whereIn('lead_status', [
                'Closed',
                'Demo-Assigned',
                'RFQ-Follow Up',
                'Hot',
                'Warm',
                'Cold',
            ])
            ->orWhere(function ($sub) {
                $sub->whereIn('lead_status', ['Lost', 'No Response'])
                    ->whereNotNull('demo_appointment');
            });
        });

        // Fetch leads with demo appointments
        $leads = $query->with('demoAppointment', 'companyDetail')->get();

        $matchedLeads = [];

        foreach ($leads as $lead) {
            $appointments = $lead->demoAppointment ?? collect();

            $appointments = $appointments->filter(function ($demo) use ($type) {
                return $demo->status !== 'Cancelled' &&
                    $demo->type === strtoupper($type);
            });

            if ($appointments->isNotEmpty()) {
                $matchedLeads[] = $lead;
            }
        }

        $this->slideOverList = collect($matchedLeads);
        $this->showSlideOver = true;
    }

    // public function openWebinarLeadList($salespersonName)
    // {
    //     $this->slideOverTitle = "Webinar Demo - " . $salespersonName;

    //     $salespersonId = User::where('name', $salespersonName)->value('id');
    //     if (!$salespersonId) {
    //         $this->slideOverList = collect();
    //         $this->showSlideOver = true;
    //         return;
    //     }

    //     $query = Lead::query()->with('companyDetail', 'demoAppointment');

    //     // UTM filters
    //     $utmLeadIds = $this->getLeadIdsFromUtmFilters();
    //     $utmFilterApplied = $this->utmCampaign || $this->utmAdgroup || $this->utmTerm || $this->utmMatchtype || $this->referrername || $this->device || $this->utmCreative;

    //     if ($utmFilterApplied && !empty($utmLeadIds)) {
    //         $query->whereIn('id', $utmLeadIds);
    //     }

    //     if (!empty($this->selectedLeadOwner)) {
    //         $ownerName = User::where('id', $this->selectedLeadOwner)->value('name');
    //         $query->where('lead_owner', $ownerName);
    //     }

    //     if (in_array(auth()->user()->role_id, [1, 3]) && $this->selectedUser) {
    //         $query->where('salesperson', $this->selectedUser);
    //     }

    //     if (auth()->user()->role_id == 2) {
    //         $query->where('salesperson', auth()->user()->id);
    //     }

    //     $leads = $query->get();

    //     $start = Carbon::parse($this->startDate)->toDateString();
    //     $end = Carbon::parse($this->endDate)->toDateString();

    //     $filteredLeads = [];

    //     foreach ($leads as $lead) {
    //         $appointments = $lead->demoAppointment ?? collect();

    //         $matched = $appointments->filter(function ($demo) use ($salespersonId, $start, $end) {
    //             return $demo->type === 'WEBINAR DEMO'
    //                 && $demo->status !== 'Cancelled'
    //                 && $demo->salesperson == $salespersonId
    //                 && $demo->date >= $start && $demo->date <= $end;
    //         });

    //         if ($matched->isNotEmpty()) {
    //             $filteredLeads[] = $lead;
    //         }
    //     }

    //     $this->slideOverList = collect($filteredLeads);
    //     $this->showSlideOver = true;
    // }

    public function openWebinarLeadList($salespersonName)
    {
        $this->slideOverTitle = "Webinar Demo - " . $salespersonName;

        $salespersonId = User::where('name', $salespersonName)->value('id');
        if (!$salespersonId) {
            $this->slideOverList = collect();
            $this->showSlideOver = true;
            return;
        }

        $query = Lead::query()->with('companyDetail', 'demoAppointment');

        $this->applyBaseFilters($query);

        $query->where(function ($q) {
            $q->whereIn('lead_status', [
                'Closed',
                'Demo-Assigned',
                'RFQ-Follow Up',
                'Hot',
                'Warm',
                'Cold',
            ])
            ->orWhere(function ($sub) {
                $sub->whereIn('lead_status', ['Lost', 'No Response'])
                    ->whereNotNull('demo_appointment');
            });
        });

        $leads = $query->get();

        $filteredLeads = [];

        foreach ($leads as $lead) {
            $appointments = $lead->demoAppointment ?? collect();

            $matched = $appointments->filter(function ($demo) use ($salespersonId) {
                return $demo->type === 'WEBINAR DEMO'
                    && $demo->status !== 'Cancelled'
                    && $demo->salesperson == $salespersonId;
            });

            if ($matched->isNotEmpty()) {
                $filteredLeads[] = $lead;
            }
        }

        $this->slideOverList = collect($filteredLeads);
        $this->showSlideOver = true;
    }

    public function openMonthlyDealsSlideOver($monthKey)
    {
        $this->slideOverTitle = "Closed Deals - " . Carbon::parse($monthKey)->format('F Y');

        $start = Carbon::parse($monthKey)->startOfMonth()->startOfDay();
        $end = Carbon::parse($monthKey)->endOfMonth()->endOfDay();

        $amountByLead = $this->closeWonAmountByLead($start, $end);

        if (empty($amountByLead)) {
            $this->slideOverList = collect();
            $this->slideOverAmounts = [];
            $this->showSlideOver = true;
            return;
        }

        $leads = Lead::with('companyDetail')
            ->whereIn('id', array_keys($amountByLead))
            ->get()
            ->sortByDesc(fn ($lead) => $amountByLead[$lead->id] ?? 0)
            ->values();

        $this->slideOverList = $leads;
        $this->slideOverAmounts = array_map(fn ($v) => (float) $v, $amountByLead);
        $this->showSlideOver = true;
    }

    public function openClosedDealsBySourceSlideOver($source)
    {
        $this->slideOverTitle = "Closed Deals - Source: " . $source;

        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();

        $amountByLead = $this->closeWonAmountByLead($start, $end);

        if (empty($amountByLead)) {
            $this->slideOverList = collect();
            $this->slideOverAmounts = [];
            $this->showSlideOver = true;
            return;
        }

        $query = Lead::with('companyDetail')->whereIn('id', array_keys($amountByLead));
        if ($source === 'Unknown') {
            $query->whereNull('lead_code');
        } else {
            $query->where('lead_code', $source);
        }

        $leads = $query->get()
            ->sortByDesc(fn ($lead) => $amountByLead[$lead->id] ?? 0)
            ->values();

        $this->slideOverList = $leads;
        $this->slideOverAmounts = array_map(fn ($v) => (float) $v, $amountByLead);
        $this->showSlideOver = true;
    }

    public function openAppointmentTypeSlideOver($source)
    {
        $this->slideOverTitle = "Appointments - Source: " . ($source === 'Null' ? 'Unknown' : $source);

        $user = Auth::user();
        $query = Lead::query()->with('demoAppointment', 'companyDetail');

        if ($source === 'Null') {
            $query->whereNull('lead_code');
        } else {
            $query->where('lead_code', $source);
        }

        $this->applyBaseFilters($query);

        // Get only leads with appointments
        $leads = $query->get()->filter(function ($lead) {
            $appointments = $lead->demoAppointment ?? collect();
            return $appointments->where('status', '!=', 'Cancelled')->isNotEmpty();
        });

        $this->slideOverList = $leads;
        $this->showSlideOver = true;
    }

    public function openNoResponseByCallAttemptsSlideOver($attempts)
    {
        $this->slideOverTitle = "No Response Leads - Call Attempts: " . $attempts;

        $user = Auth::user();
        $query = Lead::query()->with('companyDetail');

        $this->applyBaseFilters($query);

        // Only get leads with No Response status
        $query->where('lead_status', 'No Response');

        // Filter by exact call attempts value
        $query->where('call_attempt', $attempts);

        $this->slideOverList = $query->get();
        $this->showSlideOver = true;
    }

    public function getSelectedMonthRangeLabelProperty()
    {
        $start = Carbon::parse($this->startDate)->format('F Y');
        $end = Carbon::parse($this->endDate)->format('F Y');

        return $start === $end ? $start : "{$start} - {$end}";
    }
}
