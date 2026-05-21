<?php
namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\ActivityLog;
use App\Models\Lead;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Illuminate\Support\Facades\Cache;
class DashboardForm extends Page
{
    use InteractsWithPageTable;

    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?string $title = '';
    protected static string $view = 'filament.pages.dashboard-form';
    public $users; // List of users to select from
    public $selectedUser; // Selected user's ID
    public $selectedUserRole;
    public $selectedUserModel;
    public $assignToMeModalVisible = false;
    public $currentLeadId;
    public $selectedAdditionalRole;
    public $lastRefreshTime;

    public function refreshTable()
    {
        // Update timestamp
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');

        // Dispatch events to refresh child components
        $this->dispatch('refresh-implementer-tables');
        $this->dispatch('refresh-leadowner-tables');
        $this->dispatch('refresh-softwarehandover-tables');
        $this->dispatch('refresh-hardwarehandover-tables');
        $this->dispatch('refresh-salesperson-tables');
        $this->dispatch('refresh-adminrepair-tables');
        $this->dispatch('refresh-manager-tables');

        // Force Alpine components to reset
        $this->dispatch('forceResetDashboards');

        // Show notification
        Notification::make()
            ->title('Dashboard refreshed')
            ->success()
            ->send();
    }

    public function mount()
    {
        $this->users = User::whereIn('role_id', [1, 2, 4, 5, 8, 9])->get(); // Lead Owner, Salesperson, Implementer, Team Lead, Support, Technician
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');

        $currentUser = auth()->user();
        $defaultDashboard = match($currentUser->role_id) {
            1 => 'LeadOwner',
            2 => 'Salesperson',
            3 => 'Manager',
            4 => 'Implementer',
            5 => 'Implementer',
            8 => 'Support',
            9 => 'Technician',
            10 => 'Finance',
            default => 'LeadOwner',
        };

        // Set default to LeadOwner
        $this->currentDashboard = session('currentDashboard', 'LeadOwner');

        // Default selectedUser to 7 (Your Own Dashboard) when the page loads
        $this->selectedUser = session('selectedUser') == 7;
        session(['selectedUser' => $this->selectedUser]); // Store it in session

        // Initialize selectedUserModel for the current user
        $this->selectedUserModel = $currentUser;

        // Initialize additional role from session
        $this->selectedAdditionalRole = session('selectedAdditionalRole');

        if (request()->has('page') && request()->get('page') != 1) {
            return redirect()->to(url()->current() . '?page=1');
        }
    }

    public $currentDashboard = 'LeadOwner';

    public function toggleDashboard($dashboard)
    {
        // Add the new dashboard options to the valid list
        $validDashboards = [
            'LeadOwner',
            'Salesperson',
            'Manager',
            'SoftwareHandover',
            'HardwareHandover',
            'MainAdminDashboard',
            'SoftwareAdmin',
            'SoftwareAdminV2',
            'HardwareAdmin',
            'HardwareAdminV2',
            'AdminRepair',
            'Training',
            'Finance',
            'HRDF',
            'AdminHRDFAttLog',
            'AdminHRDF',
            'AdminFinance',
            'AdminReseller',
            'AdminHeadcount',
            'AdminGeneral',
            'Finance',
            // 'AdminUSDInvoice',
            'General',
            'Credit Controller',
            'Trainer',
            'Implementer',
            'Support',
            'Technician',
            'Debtor'
        ];

        if (in_array($dashboard, $validDashboards)) {
            $this->currentDashboard = $dashboard;
            session(['currentDashboard' => $dashboard]);

            // For users with additional_role=1, update their view accordingly
            if (isset($this->selectedUserModel) && $this->selectedUserModel &&
                $this->selectedUserModel->role_id == 1 && $this->selectedUserModel->additional_role == 1) {
                // Store the selected dashboard view for this user
                session(['selectedUserDashboard_' . $this->selectedUserModel->id => $dashboard]);
            }

            // Force a UI refresh - dispatch Livewire event only
            $this->dispatch('dashboard-changed', ['dashboard' => $dashboard]);
        }
    }

    public function updatedSelectedUser($userId)
    {
        $this->selectedUser = $userId;
        session(['selectedUser' => $userId]);

        if (in_array($userId, ['all-lead-owners', 'all-salespersons', 'all-implementer', 'all-support'])) {
            $this->selectedUserRole = match ($userId) {
                'all-salespersons' => 2,
                'all-implementer' => 4,
                'all-support' => 8,
                default => 1, // all-lead-owners
            };
            $this->selectedUserModel = null;
            $this->toggleDashboard(match ($this->selectedUserRole) {
                2 => 'Salesperson',
                4 => 'Implementer',
                8 => 'Support',
                default => 'LeadOwner',
            });
        } else {
            $selectedUser = User::find($userId);

            if ($selectedUser) {
                $this->selectedUserModel = $selectedUser; // Store the selected user model
                $this->selectedUserRole = $selectedUser->role_id;

                // Change dashboard based on role and additional_role if applicable
                if ($selectedUser->role_id == 1 && $selectedUser->additional_role == 1) {
                    $this->toggleDashboard('SoftwareHandover'); // Or choose an appropriate default
                } else {
                    $this->toggleDashboard(match($selectedUser->role_id) {
                        1 => 'LeadOwner',
                        2 => 'Salesperson',
                        3 => 'Manager',
                        4, 5 => 'Implementer',
                        8 => 'Support',
                        9 => 'Technician',
                        default => 'Manager',
                    });
                }
            } else {
                $this->selectedUserRole = null;
                $this->selectedUserModel = null;
                $this->toggleDashboard('Manager');
            }
        }

        $this->dispatch('updateTablesForUser', selectedUser: $userId);
    }

    // Get dashboard counts in real-time (no caching)
    public function getCachedCounts()
    {
        return [
            'manager_total' => $this->getManagerTotal(),
            'admin_software_total' => $this->getAdminSoftwareTotal(),
            'admin_hardware_total' => $this->getAdminHardwareTotal(),
            'finance_total' => $this->getFinanceTotal(),
        ];
    }

    private function getManagerTotal()
    {
        try {
            $leadTransferCount = app(\App\Livewire\LeadOwnerChangeRequestTable::class)
                ->getTableQuery()
                ->count();
            $bypassDuplicateCount = app(\App\Livewire\ManagerDashboard\BypassDuplicatedLead::class)
                ->getTableQuery()
                ->count();
            return $leadTransferCount + $bypassDuplicateCount;
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getAdminSoftwareTotal()
    {
        try {
            $softwareNewCount = app(\App\Livewire\SalespersonDashboard\SoftwareHandoverNew::class)
                ->getNewSoftwareHandovers()
                ->count();
            $softwarePendingLicenseCount = app(\App\Livewire\SoftwareHandoverPendingLicense::class)
                ->getNewSoftwareHandovers()
                ->count();
            return $softwareNewCount + $softwarePendingLicenseCount;
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getAdminHardwareTotal()
    {
        try {
            $hardwareNewCount = app(\App\Livewire\SalespersonDashboard\HardwareHandoverNew::class)
                ->getNewHardwareHandovers()
                ->count();
            $hardwarePendingStockCount = app(\App\Livewire\HardwareHandoverPendingStock::class)
                ->getOverdueHardwareHandovers()
                ->count();
            return $hardwareNewCount + $hardwarePendingStockCount;
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getFinanceTotal()
    {
        try {
            // E-Invoice Registration count
            $newCount = \App\Models\EInvoiceHandover::where('status', 'New')->count();

            $resellerPendingFinanceCount = \App\Models\ResellerHandover::where('status', 'pending_timetec_finance')->count();

            $resellerMainBoxCount = $resellerPendingFinanceCount;

            // Self Billed E-Invoice count
            $totalInvoiceCount = \App\Models\FinanceInvoice::where('status', 'new')->count();

            // Finance Handover pending payment count
            $financeHandoverPendingPaymentCount = \App\Models\FinanceHandover::where('status', 'Pending Payment')->count();

            // Billed as End User (FE) counts
            $fePendingPaymentCount = \App\Models\ResellerHandoverFe::where('status', 'pending_reseller_payment')
                ->where(function ($query) {
                    $query->whereNull('reseller_payment_completed')
                          ->orWhere('reseller_payment_completed', false);
                })->count();
            $fePendingFinancePaymentCount = \App\Models\ResellerHandoverFe::where('status', 'pending_finance_payment')->count();
            $feMainBoxCount = $fePendingFinancePaymentCount;

            // USD With Invoice (FG) counts
            $fgPendingFinanceCount = \App\Models\ResellerHandoverFg::where('status', 'pending_timetec_finance')->count();

            // Offset Payment counts
            $offsetPaymentNewCount = \App\Models\OffsetPaymentHandover::where('status', 'new')->count();

            // Reseller Commission Handover (FH) — only pending_finance counted.
            $resellerCommissionPendingFinanceCount = \App\Models\ResellerCommissionHandover::where('status', 'pending_finance')->count();

            return $newCount + $resellerMainBoxCount + $totalInvoiceCount + $financeHandoverPendingPaymentCount + $feMainBoxCount + $fgPendingFinanceCount + $offsetPaymentNewCount + $resellerCommissionPendingFinanceCount;
        } catch (\Exception $e) {
            return 0;
        }
    }

    public function getRenewalEndUserCount()
    {
        try {
            $today = now()->format('Y-m-d');
            $next90Days = now()->addDays(90)->format('Y-m-d');

            // Same logic as AdminRenewalProcessDataMyr::getNewStats()
            $renewals = \App\Models\Renewal::where('renewal_progress', 'new')
                ->where('task_status', false)
                ->get();

            $endUserCount = 0;

            foreach ($renewals as $renewal) {
                // Check if company has expiring license in frontenddb (same as getNewStats)
                $hasExpiringLicense = RenewalDataMyr::where('f_company_id', $renewal->f_company_id)
                    ->whereBetween('f_expiry_date', [$today, $next90Days])
                    ->where('f_currency', 'MYR')
                    ->exists();

                if (!$hasExpiringLicense) {
                    continue;
                }

                // Check reseller status (same as getNewStats)
                $reseller = RenewalDataMyr::getResellerForCompany($renewal->f_company_id);
                if (!$reseller || !$reseller->f_rate) {
                    $endUserCount++;
                }
            }

            return $endUserCount;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('RenewalEndUserCount error: ' . $e->getMessage());
            return 0;
        }
    }

    private function getAdminRenewalCounts()
    {
        try {
            // Shared query: get reseller company IDs per currency ONCE
            $getResellerIds = function (string $currency) {
                $companyIds = \Illuminate\Support\Facades\DB::connection('frontenddb')->table('crm_expiring_license')
                    ->select('f_company_id')
                    ->where('f_currency', $currency)
                    ->whereDate('f_expiry_date', '>=', today())
                    ->whereDate('f_expiry_date', '<=', today()->addDays(90))
                    ->distinct()
                    ->pluck('f_company_id')
                    ->flatMap(function ($id) {
                        $withoutZeros = (string) (int) $id;
                        $withZeros = str_pad($withoutZeros, 10, '0', STR_PAD_LEFT);
                        return [$withoutZeros, $withZeros];
                    })
                    ->toArray();

                $resellerIds = \Illuminate\Support\Facades\DB::connection('frontenddb')
                    ->table('crm_reseller_link')
                    ->whereIn('f_id', $companyIds)
                    ->pluck('f_id')
                    ->toArray();

                return array_intersect($companyIds, $resellerIds);
            };

            $myrIds = $getResellerIds('MYR');
            $usdIds = $getResellerIds('USD');

            $countQuery = function (array $ids, string $period) {
                if (empty($ids)) return 0;
                $query = \App\Models\Renewal::query()
                    ->whereIn('f_company_id', $ids)
                    ->where('follow_up_counter', true)
                    ->where('mapping_status', 'completed_mapping')
                    ->whereIn('renewal_progress', ['pending_confirmation']);

                match ($period) {
                    'today' => $query->whereDate('follow_up_date', today()),
                    'overdue' => $query->whereDate('follow_up_date', '<', today()),
                };

                return $query->count();
            };

            return [
                'followUpTodayMYR' => $countQuery($myrIds, 'today'),
                'followUpOverdueMYR' => $countQuery($myrIds, 'overdue'),
                'followUpTodayUSD' => $countQuery($usdIds, 'today'),
                'followUpOverdueUSD' => $countQuery($usdIds, 'overdue'),
            ];
        } catch (\Exception $e) {
            return [
                'followUpTodayMYR' => 0,
                'followUpOverdueMYR' => 0,
                'followUpTodayUSD' => 0,
                'followUpOverdueUSD' => 0,
            ];
        }
    }

    // Get additional counts in real-time (no caching)
    public function loadAdditionalCounts()
    {
            $additionalCounts = [];

            // Load remaining hardware V2 counts
            try {
                $additionalCounts['pendingCourier'] = app(\App\Livewire\AdminHardwareV2Dashboard\HardwareV2PendingCourierTable::class)
                    ->getNewHardwareHandovers()->count();
                $additionalCounts['pendingAdminPickUp'] = app(\App\Livewire\AdminHardwareV2Dashboard\HardwareV2PendingAdminSelfPickUpTable::class)
                    ->getNewHardwareHandovers()->count();
                $additionalCounts['pendingExternalInstallation'] = app(\App\Livewire\AdminHardwareV2Dashboard\HardwareV2PendingExternalInstallationTable::class)
                    ->getNewHardwareHandovers()->count();
                $additionalCounts['pendingInternalInstallation'] = app(\App\Livewire\AdminHardwareV2Dashboard\HardwareV2PendingInternalInstallationTable::class)
                    ->getNewHardwareHandovers()->count();
            } catch (\Exception $e) {
                $additionalCounts['pendingCourier'] = 0;
                $additionalCounts['pendingAdminPickUp'] = 0;
                $additionalCounts['pendingExternalInstallation'] = 0;
                $additionalCounts['pendingInternalInstallation'] = 0;
            }

            // Load additional software V2 counts
            try {
                $additionalCounts['softwareV2PendingKickOff'] = app(\App\Livewire\SoftwareHandoverV2KickOffReminder::class)
                    ->getNewSoftwareHandovers()->count();
                $additionalCounts['softwareV2PendingLicense'] = app(\App\Livewire\SoftwareHandoverV2PendingLicense::class)
                    ->getNewSoftwareHandovers()->count();
            } catch (\Exception $e) {
                $additionalCounts['softwareV2PendingKickOff'] = 0;
                $additionalCounts['softwareV2PendingLicense'] = 0;
            }

            return $additionalCounts;
    }

    public function updatedSelectedAdditionalRole($additionalRoleId)
    {
        $this->selectedAdditionalRole = $additionalRoleId;
        session(['selectedAdditionalRole' => $additionalRoleId]);

        if (in_array($additionalRoleId, ['implementer', 'sales-manager', 'team-lead'])) {
            // Handle specific predefined role groups
            switch ($additionalRoleId) {
                case 'implementer':
                    $this->toggleDashboard('Implementer');
                    break;
                case 'sales-manager':
                    $this->toggleDashboard('SalesManager');
                    break;
                case 'team-lead':
                    $this->toggleDashboard('TeamLead');
                    break;
                default:
                    $this->toggleDashboard('Manager');
            }
        } else {
            // Handle specific additional role IDs
            $role = \App\Models\Role::find($additionalRoleId);

            if ($role) {
                if ($role->name === 'Implementer') {
                    $this->toggleDashboard('Implementer');
                } elseif ($role->name === 'Sales Manager') {
                    $this->toggleDashboard('SalesManager');
                } elseif ($role->name === 'Team Lead') {
                    $this->toggleDashboard('TeamLead');
                } else {
                    // Default view for other roles
                    $this->toggleDashboard('Manager');
                }
            } else {
                // Fallback to default view
                $this->toggleDashboard('Manager');
            }
        }

        // Dispatch event to update tables based on the selected additional role
        $this->dispatch('updateTablesForAdditionalRole', selectedAdditionalRole: $additionalRoleId);
    }

    // New method for toggling between Lead Owner, Software Handover, and Hardware Handover
    public function toggleHandoverView($view)
    {
        $this->toggleDashboard($view);
        session(['currentDashboard' => $view]);
    }
}
