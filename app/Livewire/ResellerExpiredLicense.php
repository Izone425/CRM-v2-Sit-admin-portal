<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ResellerExpiredLicense extends Component
{
    public $expandedCompany = null;
    public $invoiceDetails = [];
    public $search = '';
    public $sortField = 'days_until_expiry';
    public $sortDirection = 'asc';
    public $activeTab = '90days'; // '90days' or 'all'
    public $showRenewalModal = false;
    public $renewalDetails = [];
    public $renewalCompanyName = '';
    public $renewalStatusFilter = [];

    public function toggleRenewalStatusFilter($status)
    {
        if (in_array($status, $this->renewalStatusFilter)) {
            $this->renewalStatusFilter = array_values(array_diff($this->renewalStatusFilter, [$status]));
        } else {
            $this->renewalStatusFilter[] = $status;
        }
    }

    public function clearRenewalStatusFilter()
    {
        $this->renewalStatusFilter = [];
    }

    public function updatedSearch()
    {
        // Search updated
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->expandedCompany = null;
        $this->invoiceDetails = [];
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function toggleExpand($fId)
    {
        // Convert to int for comparison
        $fId = (int) $fId;

        if ($this->expandedCompany === $fId) {
            $this->expandedCompany = null;
            $this->invoiceDetails = [];
        } else {
            $this->expandedCompany = $fId;
            $this->loadInvoiceDetails($fId);
        }
    }

    public function getCompaniesProperty()
    {
        $reseller = Auth::guard('reseller')->user();

        if (!$reseller || !$reseller->reseller_id) {
            return collect([]);
        }

        $today            = Carbon::now()->startOfDay();
        $ninetyDaysFromNow = Carbon::now()->startOfDay()->addDays(90);
        $canManageAdvanced = ($reseller->advanced_modules ?? 'disable') === 'enable';

        $resellerLinks = DB::connection('frontenddb')
            ->table('crm_reseller_link')
            ->where('reseller_id', $reseller->reseller_id)
            ->get(['f_id', 'f_company_name']);

        if ($resellerLinks->isEmpty()) {
            return collect([]);
        }

        if ($this->search) {
            $resellerLinks = $resellerLinks->filter(
                fn($l) => stripos($l->f_company_name, $this->search) !== false
            );
        }

        $allIds = $resellerLinks->pluck('f_id')->toArray();

        $nameCondition = function($q) use ($canManageAdvanced) {
            $q->where('f_name', 'like', '%TA%')
              ->orWhere('f_name', 'like', '%leave%')
              ->orWhere('f_name', 'like', '%claim%')
              ->orWhere('f_name', 'like', '%payroll%')
              ->orWhere('f_name', 'like', '%Face & QR Code%');
            if ($canManageAdvanced) {
                $q->orWhere('f_name', 'like', '%VMS%')
                  ->orWhere('f_name', 'like', '%FCC%')
                  ->orWhere('f_name', 'like', '%Patrol%');
            }
        };

        // Bulk query 1: earliest expiry per company (tab-filtered)
        $earliestQuery = DB::connection('frontenddb')
            ->table('crm_company_license')
            ->whereIn('f_company_id', $allIds)
            ->where('f_type', 'PAID')
            ->where('status', 'Active')
            ->where($nameCondition);

        if ($this->activeTab === '90days') {
            $earliestQuery->whereBetween('f_expiry_date', [$today->format('Y-m-d'), $ninetyDaysFromNow->format('Y-m-d')]);
        } else {
            $earliestQuery->whereDate('f_expiry_date', '>=', $today->format('Y-m-d'));
        }

        $earliestMap = $earliestQuery
            ->select('f_company_id', DB::raw('MIN(f_expiry_date) as min_expiry'))
            ->groupBy('f_company_id')
            ->get()
            ->keyBy('f_company_id');

        $qualifyingIds = $earliestMap->keys()->toArray();

        if (empty($qualifyingIds)) {
            return collect([]);
        }

        // Bulk query 2: max expiry per company (for renewal_status)
        $latestMap = DB::connection('frontenddb')
            ->table('crm_company_license')
            ->whereIn('f_company_id', $qualifyingIds)
            ->where('f_type', 'PAID')
            ->where('status', 'Active')
            ->where($nameCondition)
            ->select('f_company_id', DB::raw('MAX(f_expiry_date) as max_expiry'))
            ->groupBy('f_company_id')
            ->get()
            ->keyBy('f_company_id');

        // Bulk query 3: total distinct invoices per company (all active non-expired)
        $totalMap = DB::connection('frontenddb')
            ->table('crm_company_license')
            ->whereIn('f_company_id', $qualifyingIds)
            ->where('f_type', 'PAID')
            ->where('status', 'Active')
            ->where($nameCondition)
            ->whereDate('f_expiry_date', '>=', $today->format('Y-m-d'))
            ->select('f_company_id', DB::raw('COUNT(DISTINCT f_invoice_no) as total'))
            ->groupBy('f_company_id')
            ->get()
            ->keyBy('f_company_id');

        // Bulk query 4: done invoice count per company from reminders
        // Only count invoices that are currently active — prevents stale "done" marks from
        // old/expired invoices inflating the counter against current active invoices.
        $activeInvoiceNos = DB::connection('frontenddb')
            ->table('crm_company_license')
            ->whereIn('f_company_id', $qualifyingIds)
            ->where('f_type', 'PAID')
            ->where('status', 'Active')
            ->where($nameCondition)
            ->whereDate('f_expiry_date', '>=', $today->format('Y-m-d'))
            ->whereNotNull('f_invoice_no')
            ->pluck('f_invoice_no')
            ->toArray();

        $doneInvoiceMap = \App\Models\ResellerRenewalReminder::where('reseller_id', $reseller->reseller_id)
            ->whereIn('f_company_id', $qualifyingIds)
            ->whereIn('f_invoice_no', $activeInvoiceNos)
            ->get(['f_company_id', 'f_invoice_no'])
            ->groupBy(fn($r) => (int) $r->f_company_id)
            ->map(fn($rows) => $rows->pluck('f_invoice_no')->filter()->count())
            ->all();

        $companies = [];
        foreach ($resellerLinks as $link) {
            $row = $earliestMap->get($link->f_id);
            if (!$row) continue;

            $expiryDate      = Carbon::parse($row->min_expiry);
            $daysUntilExpiry = $today->diffInDays($expiryDate);

            $latestRow     = $latestMap->get($link->f_id);
            $renewalStatus = 'pending';
            if ($latestRow && $latestRow->max_expiry > $row->min_expiry) {
                $latestExpiry  = Carbon::parse($latestRow->max_expiry);
                $renewalStatus = $latestExpiry->gt($ninetyDaysFromNow) ? 'done' : 'done_expiring';
            }

            $totalInvoices = (int) ($totalMap->get($link->f_id)?->total ?? 0);
            $doneInvoices  = $doneInvoiceMap[(int) $link->f_id] ?? 0;

            $companies[] = (object) [
                'f_id'              => $link->f_id,
                'f_company_name'    => $link->f_company_name,
                'f_expiry_date'     => $row->min_expiry,
                'days_until_expiry' => $daysUntilExpiry,
                'renewal_status'    => $renewalStatus,
                'total_invoices'    => $totalInvoices,
                'done_invoices'     => $doneInvoices,
                'is_done'           => $totalInvoices > 0 && $doneInvoices >= $totalInvoices,
            ];
        }

        if (!empty($this->renewalStatusFilter)) {
            $companies = array_values(array_filter($companies, fn($c) => in_array($c->renewal_status, $this->renewalStatusFilter)));
        }

        usort($companies, function($a, $b) {
            $cmp = $this->sortField === 'f_expiry_date'
                ? strtotime($a->f_expiry_date) - strtotime($b->f_expiry_date)
                : $a->days_until_expiry - $b->days_until_expiry;
            return $this->sortDirection === 'asc' ? $cmp : -$cmp;
        });

        return collect($companies);
    }

    public function loadInvoiceDetails($fId)
    {
        $today = Carbon::now()->format('Y-m-d');
        $ninetyDaysFromNow = Carbon::now()->addDays(90)->format('Y-m-d');

        // Get reseller information
        $reseller = DB::connection('frontenddb')->table('crm_reseller_link')
            ->select('reseller_name', 'f_rate', 'f_id')
            ->where('f_id', (int) $fId)
            ->first();

        // Get all licenses for this f_id (company)
        $reseller = Auth::guard('reseller')->user();
        $canManageAdvanced = ($reseller->advanced_modules ?? 'disable') === 'enable';

        $query = DB::connection('frontenddb')
            ->table('crm_company_license')
            ->where('crm_company_license.f_company_id', (int) $fId)
            ->where('crm_company_license.f_type', 'PAID')
            ->where(function($q) use ($canManageAdvanced) {
                $q->where('crm_company_license.f_name', 'like', '%TA%')
                  ->orWhere('crm_company_license.f_name', 'like', '%leave%')
                  ->orWhere('crm_company_license.f_name', 'like', '%claim%')
                  ->orWhere('crm_company_license.f_name', 'like', '%payroll%')
                  ->orWhere('crm_company_license.f_name', 'like', '%Face & QR Code%');

                if ($canManageAdvanced) {
                    $q->orWhere('crm_company_license.f_name', 'like', '%VMS%')
                      ->orWhere('crm_company_license.f_name', 'like', '%FCC%')
                      ->orWhere('crm_company_license.f_name', 'like', '%Patrol%');
                }
            });

        // Show all Active licenses with expiry on/after today regardless of tab.
        // The 90-day window only narrows the COMPANY list — the per-company breakdown
        // shows every still-valid license, but excludes already-expired ones.
        $query->where('crm_company_license.status', 'Active')
              ->whereDate('crm_company_license.f_expiry_date', '>=', $today);

        $licenses = $query->get([
                'crm_company_license.f_name', 'crm_company_license.f_total_user',
                'crm_company_license.f_start_date',
                'crm_company_license.f_expiry_date', 'crm_company_license.f_invoice_no',
                'crm_company_license.f_billing_cycle', 'crm_company_license.status'
            ]);

        $invoiceGroups = [];
        $moduleLicenses = [
            'attendance' => [],
            'leave' => [],
            'claim' => [],
            'payroll' => [],
        ];

        foreach ($licenses as $license) {
            $invoiceNo = $license->f_invoice_no ?? 'No Invoice';
            $licenseName = $license->f_name;
            $quantity = $license->f_total_user;

            // Collect per-module license periods for smart headcount calculation
            $entry = ['headcount' => $quantity, 'start' => $license->f_start_date, 'end' => $license->f_expiry_date];
            if (strpos($licenseName, 'TimeTec TA') !== false) {
                $moduleLicenses['attendance'][] = $entry;
            }
            if (strpos($licenseName, 'TimeTec Leave') !== false) {
                $moduleLicenses['leave'][] = $entry;
            }
            if (strpos($licenseName, 'TimeTec Claim') !== false) {
                $moduleLicenses['claim'][] = $entry;
            }
            if (strpos($licenseName, 'TimeTec Payroll') !== false) {
                $moduleLicenses['payroll'][] = $entry;
            }

            $calculatedAmount = 0;
            $discountRate = ($reseller && $reseller->f_rate) ? $reseller->f_rate : '0.00';

            if (!isset($invoiceGroups[$invoiceNo])) {
                $invoiceGroups[$invoiceNo] = [
                    'f_id' => $fId,
                    'products' => [],
                    'total_amount' => 0
                ];
            }

            $invoiceGroups[$invoiceNo]['products'][] = [
                'f_name' => $license->f_name,
                'f_total_user' => $quantity,
                'f_total_amount' => $calculatedAmount,
                'f_start_date' => $license->f_start_date,
                'f_expiry_date' => $license->f_expiry_date,
                'billing_cycle' => $license->f_billing_cycle ?? 0,
                'discount' => $discountRate,
                'status' => $license->status ?? 'Active'
            ];

            $invoiceGroups[$invoiceNo]['total_amount'] += $calculatedAmount;
        }

        $licenseSummary = self::calculateMaxConcurrentHeadcount($moduleLicenses);

        // Per-invoice "renewal done" marks live in reseller_renewal_reminders, keyed by
        // (reseller_id, f_company_id, f_invoice_no). Stamp each invoice with its own flag.
        $authReseller = Auth::guard('reseller')->user();
        $doneInvoices = [];
        if ($authReseller && $authReseller->reseller_id) {
            $doneInvoices = \App\Models\ResellerRenewalReminder::where('reseller_id', $authReseller->reseller_id)
                ->where('f_company_id', (int) $fId)
                ->pluck('f_invoice_no')
                ->filter()
                ->flip()
                ->toArray();
        }
        foreach ($invoiceGroups as $invoiceNo => &$group) {
            $group['is_done'] = isset($doneInvoices[(string) $invoiceNo]);
        }
        unset($group);

        $this->invoiceDetails = $invoiceGroups;
        $this->invoiceDetails['_summary'] = $licenseSummary;
    }

    public function openRenewalModal($fId)
    {
        $fId = (int) $fId;
        $today = Carbon::now();
        $ninetyDaysFromNow = Carbon::now()->addDays(90);

        // Get company name
        $resellerLink = DB::connection('frontenddb')->table('crm_reseller_link')
            ->where('f_id', $fId)
            ->first(['f_company_name', 'f_rate']);

        $this->renewalCompanyName = $resellerLink->f_company_name ?? '';

        // Find the earliest expiring license for this company (same logic as getCompaniesProperty)
        $earliestQuery = DB::connection('frontenddb')
            ->table('crm_company_license')
            ->where('f_company_id', $fId)
            ->where('f_type', 'PAID')
            ->where('status', 'Active');

        if ($this->activeTab === '90days') {
            $earliestQuery->whereBetween('f_expiry_date', [$today->format('Y-m-d'), $ninetyDaysFromNow->format('Y-m-d')]);
        } else {
            $earliestQuery->whereDate('f_expiry_date', '>=', $today->format('Y-m-d'));
        }

        $earliestLicense = $earliestQuery->orderBy('f_expiry_date', 'asc')->first(['f_expiry_date']);
        $earliestExpiry = $earliestLicense->f_expiry_date ?? $today->format('Y-m-d');

        // Get renewed licenses (expiry after the earliest expiring one)
        $reseller = Auth::guard('reseller')->user();
        $canManageAdvanced = ($reseller->advanced_modules ?? 'disable') === 'enable';

        $licenses = DB::connection('frontenddb')
            ->table('crm_company_license')
            ->where('f_company_id', $fId)
            ->where('f_type', 'PAID')
            ->where('status', 'Active')
            ->where('f_expiry_date', '>', $earliestExpiry)
            ->where(function($q) use ($canManageAdvanced) {
                $q->where('f_name', 'like', '%TA%')
                  ->orWhere('f_name', 'like', '%leave%')
                  ->orWhere('f_name', 'like', '%claim%')
                  ->orWhere('f_name', 'like', '%payroll%')
                  ->orWhere('f_name', 'like', '%Face & QR Code%');

                if ($canManageAdvanced) {
                    $q->orWhere('f_name', 'like', '%VMS%')
                      ->orWhere('f_name', 'like', '%FCC%')
                      ->orWhere('f_name', 'like', '%Patrol%');
                }
            })
            ->get(['f_name', 'f_total_user', 'f_start_date', 'f_expiry_date', 'f_invoice_no', 'f_billing_cycle', 'status']);

        $invoiceGroups = [];
        $moduleLicenses = [
            'attendance' => [],
            'leave' => [],
            'claim' => [],
            'payroll' => [],
        ];

        foreach ($licenses as $license) {
            $invoiceNo = $license->f_invoice_no ?? 'No Invoice';
            $quantity = $license->f_total_user;

            $entry = ['headcount' => $quantity, 'start' => $license->f_start_date, 'end' => $license->f_expiry_date];
            if (strpos($license->f_name, 'TimeTec TA') !== false) {
                $moduleLicenses['attendance'][] = $entry;
            }
            if (strpos($license->f_name, 'TimeTec Leave') !== false) {
                $moduleLicenses['leave'][] = $entry;
            }
            if (strpos($license->f_name, 'TimeTec Claim') !== false) {
                $moduleLicenses['claim'][] = $entry;
            }
            if (strpos($license->f_name, 'TimeTec Payroll') !== false) {
                $moduleLicenses['payroll'][] = $entry;
            }

            $discountRate = ($resellerLink && $resellerLink->f_rate) ? $resellerLink->f_rate : '0.00';

            if (!isset($invoiceGroups[$invoiceNo])) {
                $invoiceGroups[$invoiceNo] = [
                    'f_id' => $fId,
                    'products' => [],
                    'total_amount' => 0,
                ];
            }

            $invoiceGroups[$invoiceNo]['products'][] = [
                'f_name' => $license->f_name,
                'f_total_user' => $quantity,
                'f_total_amount' => 0,
                'f_start_date' => $license->f_start_date,
                'f_expiry_date' => $license->f_expiry_date,
                'billing_cycle' => $license->f_billing_cycle ?? 0,
                'discount' => $discountRate,
                'status' => $license->status ?? 'Active',
            ];
        }

        $invoiceGroups['_summary'] = self::calculateMaxConcurrentHeadcount($moduleLicenses);
        $this->renewalDetails = $invoiceGroups;
        $this->showRenewalModal = true;
    }

    public function closeRenewalModal()
    {
        $this->showRenewalModal = false;
        $this->renewalDetails = [];
        $this->renewalCompanyName = '';
    }

    /**
     * Mark a company's renewal as DONE — this removes it from the reseller's weekly
     * renewal reminder email. The presence of a row in reseller_renewal_reminders now
     * means "excluded from reminders".
     */
    public function markRenewalDone($fId, $invoiceNo = null): void
    {
        $reseller = Auth::guard('reseller')->user();
        if (!$reseller || !$reseller->reseller_id) return;

        $companyName = DB::connection('frontenddb')
            ->table('crm_reseller_link')
            ->where('reseller_id', $reseller->reseller_id)
            ->where('f_id', (int) $fId)
            ->value('f_company_name');

        \App\Models\ResellerRenewalReminder::updateOrCreate(
            [
                'reseller_id'   => $reseller->reseller_id,
                'f_company_id'  => (int) $fId,
                'f_invoice_no'  => (string) $invoiceNo,
            ],
            [
                'f_company_name' => $companyName,
                'added_by'       => $reseller->id,
            ]
        );

        // Flip the flag in-place — no need to re-query.
        if ($this->expandedCompany === (int) $fId && isset($this->invoiceDetails[$invoiceNo])) {
            $this->invoiceDetails[$invoiceNo]['is_done'] = true;
        }

        $this->dispatch('reminder-toast',
            type: 'success',
            message: 'Invoice ' . $invoiceNo . ' marked as Done Renewal.'
        );
    }

    /**
     * Undo a "renewal done" mark — flip the row back to Pending so it shows up in reminders again.
     */
    public function unmarkRenewalDone($fId, $invoiceNo = null): void
    {
        $reseller = Auth::guard('reseller')->user();
        if (!$reseller || !$reseller->reseller_id) return;

        \App\Models\ResellerRenewalReminder::where('reseller_id', $reseller->reseller_id)
            ->where('f_company_id', (int) $fId)
            ->where('f_invoice_no', (string) $invoiceNo)
            ->delete();

        // Flip the flag in-place — no need to re-query.
        if ($this->expandedCompany === (int) $fId && isset($this->invoiceDetails[$invoiceNo])) {
            $this->invoiceDetails[$invoiceNo]['is_done'] = false;
        }

        $this->dispatch('reminder-toast',
            type: 'info',
            message: 'Invoice ' . $invoiceNo . ' marked as Pending.'
        );
    }

    /**
     * Calculate the max concurrent headcount per module.
     * For overlapping date ranges, sum the headcounts.
     * For non-overlapping ranges, take the max headcount across periods.
     */
    protected static function calculateMaxConcurrentHeadcount(array $moduleLicenses): array
    {
        $result = [];

        foreach ($moduleLicenses as $module => $licenses) {
            if (empty($licenses)) {
                $result[$module] = 0;
                continue;
            }

            // Collect all date boundary events
            // type=0 for END (process first), type=1 for START (process second)
            $events = [];
            foreach ($licenses as $lic) {
                $start = Carbon::parse($lic['start'])->startOfDay();
                $end = Carbon::parse($lic['end'])->endOfDay()->addDay()->startOfDay();
                $events[] = ['date' => $start->timestamp, 'type' => 1, 'delta' => $lic['headcount']];
                $events[] = ['date' => $end->timestamp, 'type' => 0, 'delta' => -$lic['headcount']];
            }

            // Sort by date first, then by type (END=0 before START=1 on same date)
            usort($events, function ($a, $b) {
                if ($a['date'] !== $b['date']) return $a['date'] <=> $b['date'];
                return $a['type'] <=> $b['type'];
            });

            // Sweep line to find max concurrent headcount
            $maxHeadcount = 0;
            $current = 0;
            foreach ($events as $event) {
                $current += $event['delta'];
                $maxHeadcount = max($maxHeadcount, $current);
            }

            $result[$module] = $maxHeadcount;
        }

        return $result;
    }

    private function encryptCompanyId($companyId): string
    {
        $aesKey = 'Epicamera@99';
        try {
            $encrypted = openssl_encrypt($companyId, "AES-128-ECB", $aesKey);
            return base64_encode($encrypted);
        } catch (\Exception $e) {
            return $companyId;
        }
    }

    public function getExpiredWithin90DaysCountProperty()
    {
        $reseller = Auth::guard('reseller')->user();

        if (!$reseller || !$reseller->reseller_id) {
            return 0;
        }

        $canManageAdvanced = ($reseller->advanced_modules ?? 'disable') === 'enable';
        $today = Carbon::now();
        $ninetyDaysFromNow = Carbon::now()->addDays(90);

        $resellerLinks = DB::connection('frontenddb')
            ->table('crm_reseller_link')
            ->where('reseller_id', $reseller->reseller_id)
            ->pluck('f_id');

        return DB::connection('frontenddb')
            ->table('crm_company_license')
            ->whereIn('crm_company_license.f_company_id', $resellerLinks)
            ->where('crm_company_license.f_type', 'PAID')
            ->where('crm_company_license.status', 'Active')
            ->where(function($q) use ($canManageAdvanced) {
                $q->where('crm_company_license.f_name', 'like', '%TA%')
                  ->orWhere('crm_company_license.f_name', 'like', '%leave%')
                  ->orWhere('crm_company_license.f_name', 'like', '%claim%')
                  ->orWhere('crm_company_license.f_name', 'like', '%payroll%')
                  ->orWhere('crm_company_license.f_name', 'like', '%Face & QR Code%');

                if ($canManageAdvanced) {
                    $q->orWhere('crm_company_license.f_name', 'like', '%VMS%')
                      ->orWhere('crm_company_license.f_name', 'like', '%FCC%')
                      ->orWhere('crm_company_license.f_name', 'like', '%Patrol%');
                }
            })
            ->whereBetween('crm_company_license.f_expiry_date', [$today->format('Y-m-d'), $ninetyDaysFromNow->format('Y-m-d')])
            ->distinct('crm_company_license.f_company_id')
            ->count('crm_company_license.f_company_id');
    }

    public function getAllExpiredCountProperty()
    {
        $reseller = Auth::guard('reseller')->user();

        if (!$reseller || !$reseller->reseller_id) {
            return 0;
        }

        $canManageAdvanced = ($reseller->advanced_modules ?? 'disable') === 'enable';
        $today = Carbon::now();

        $resellerLinks = DB::connection('frontenddb')
            ->table('crm_reseller_link')
            ->where('reseller_id', $reseller->reseller_id)
            ->pluck('f_id');

        return DB::connection('frontenddb')
            ->table('crm_company_license')
            ->whereIn('crm_company_license.f_company_id', $resellerLinks)
            ->where('crm_company_license.f_type', 'PAID')
            ->where('crm_company_license.status', 'Active')
            ->where(function($q) use ($canManageAdvanced) {
                $q->where('crm_company_license.f_name', 'like', '%TA%')
                  ->orWhere('crm_company_license.f_name', 'like', '%leave%')
                  ->orWhere('crm_company_license.f_name', 'like', '%claim%')
                  ->orWhere('crm_company_license.f_name', 'like', '%payroll%')
                  ->orWhere('crm_company_license.f_name', 'like', '%Face & QR Code%');

                if ($canManageAdvanced) {
                    $q->orWhere('crm_company_license.f_name', 'like', '%VMS%')
                      ->orWhere('crm_company_license.f_name', 'like', '%FCC%')
                      ->orWhere('crm_company_license.f_name', 'like', '%Patrol%');
                }
            })
            ->whereDate('crm_company_license.f_expiry_date', '>=', $today->format('Y-m-d'))
            ->distinct('crm_company_license.f_company_id')
            ->count('crm_company_license.f_company_id');
    }

    public function render()
    {
        return view('livewire.reseller-expired-license', [
            'companies' => $this->companies,
            'expiredWithin90DaysCount' => $this->expiredWithin90DaysCount,
            'allExpiredCount' => $this->allExpiredCount,
            'activeTab' => $this->activeTab
        ]);
    }
}
