<?php

namespace App\Livewire\HrAdminDashboard;

use App\Models\HrLicense;
use App\Models\ResellerV2;
use App\Models\SoftwareHandover;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class CompanyCustomerTab extends Component
{
    public ?int $softwareHandoverId = null;
    public array $companyData = [];

    // Search/Filter properties
    public string $search = '';
    public string $statusFilter = 'all';
    public ?string $startDate = null;
    public ?string $endDate = null;

    // Data arrays
    public array $resellers = [];
    public array $subscribers = [];

    // Count properties (unfiltered totals for badges)
    public int $resellerActiveCount = 0;
    public int $resellerInactiveCount = 0;
    public int $subscriberActiveCount = 0;
    public int $subscriberInactiveCount = 0;

    // Resellers sub-section is only meaningful when the parent is a Distributor.
    // A Reseller has only downstream customers, no downstream resellers.
    public bool $showResellersSection = true;

    public function mount(?int $softwareHandoverId = null, array $companyData = [])
    {
        $this->softwareHandoverId = $softwareHandoverId;
        $this->companyData = $companyData;
        $this->showResellersSection = ($companyData['license_category'] ?? 'Subscriber') !== 'Reseller';
        $this->loadCustomers();
    }

    public function loadCustomers(): void
    {
        $softwareHandover = $this->companyData['software_handover'] ?? null;
        $resellerV2 = $this->companyData['reseller_v2'] ?? null;
        $distributorV2 = $this->companyData['distributor_v2'] ?? null;

        // Subscriber view: use the dealer assigned to the current handover.
        // Reseller/Distributor view: no SoftwareHandover exists — fall back to
        // the local resellers.id linked from the respective v2 row.
        $resellerId = $softwareHandover?->reseller_id
            ?? $resellerV2?->reseller_id
            ?? $distributorV2?->reseller_id;
        $currentSwId = $softwareHandover?->id;

        if (!$resellerId) {
            $this->resellers = [];
            $this->subscribers = [];
            return;
        }

        try {
            // Build query for all HrLicense records under this reseller (excluding current company)
            $query = HrLicense::whereHas('softwareHandover', function ($q) use ($resellerId, $currentSwId) {
                $q->where('reseller_id', $resellerId);
                if ($currentSwId !== null) {
                    $q->where('id', '!=', $currentSwId);
                }
            })
                        ->with('softwareHandover:id,hr_account_id,hr_company_id,completed_at,status');

            // Apply search filter
            if (!empty($this->search)) {
                $query->where('company_name', 'like', '%' . $this->search . '%');
            }

            // Apply status filter
            if ($this->statusFilter === 'active') {
                $query->whereRaw('LOWER(status) = ?', ['active']);
            } elseif ($this->statusFilter === 'inactive') {
                $query->whereRaw('LOWER(status) != ?', ['active']);
            }

            // Apply date range filter (on softwareHandover.completed_at)
            if ($this->startDate) {
                $query->whereHas('softwareHandover', function ($q) {
                    $q->whereDate('completed_at', '>=', $this->startDate);
                });
            }
            if ($this->endDate) {
                $query->whereHas('softwareHandover', function ($q) {
                    $q->whereDate('completed_at', '<=', $this->endDate);
                });
            }

            $allLicenses = $query->get();

            // Split by license_category
            $resellerRecords = $allLicenses->where('license_category', 'Reseller');
            $subscriberRecords = $allLicenses->where('license_category', 'Subscriber');

            // Collapse multiple HrLicense rows into ONE display row per customer
            // (keyed by software_handover_id). The customer is "Active" if ANY
            // of its licenses is currently Enabled; otherwise Inactive.
            // Normalises hr_licenses' Enabled/Disabled vocabulary into the
            // view's expected 'active'/'inactive' badge styling check.
            $buildDisplay = function ($licenses) {
                return $licenses
                    ->groupBy('software_handover_id')
                    ->map(function ($group) {
                        $first = $group->first();
                        $sw = $first->softwareHandover;
                        $anyEnabled = $group->contains(fn ($l) => strtolower((string) $l->status) === 'enabled');
                        return [
                            'id' => $sw?->hr_account_id ?? '-',
                            'software_handover_id' => $first->software_handover_id,
                            'hr_account_id' => $sw?->hr_account_id,
                            'hr_company_id' => $sw?->hr_company_id,
                            'name' => $first->company_name ?? '-',
                            'joined_date' => $sw?->completed_at
                                ? Carbon::parse($sw->completed_at)->format('d-m-Y')
                                : '-',
                            'status' => $anyEnabled ? 'Active' : 'Inactive',
                        ];
                    })
                    ->values()
                    ->toArray();
            };

            $this->resellers = $buildDisplay($resellerRecords);
            $this->subscribers = $buildDisplay($subscriberRecords);

            // Reseller children are linked through reseller_v2.parent_reseller_id
            // (the dealer assignment), NOT software_handover.reseller_id — they
            // have no SoftwareHandover, so the HrLicense query above can never
            // surface them. Fetch and merge them into the Resellers section.
            // (Distributor children would follow the identical parent_reseller_id
            // pattern via DistributorV2 if distributors are ever nested.)
            $this->resellers = array_merge(
                $this->resellers,
                $this->loadResellerChildren($resellerId)
            );

            // Compute unfiltered counts for badges
            $this->computeCounts($resellerId, $currentSwId);

        } catch (\Exception $e) {
            Log::error('CompanyCustomerTab: Failed to load customers', [
                'software_handover_id' => $this->softwareHandoverId,
                'error' => $e->getMessage(),
            ]);
            $this->resellers = [];
            $this->subscribers = [];
        }
    }

    /**
     * Resellers assigned to this company via reseller_v2.parent_reseller_id.
     * Built directly from the v2 row (no SoftwareHandover exists) and shaped to
     * match the Resellers-section display rows. Honors the search/status filters.
     */
    protected function loadResellerChildren(int $resellerId): array
    {
        return $this->resellerChildrenQuery($resellerId)
            ->get()
            ->map(function ($r) {
                return [
                    'id' => $r->hr_account_id ?? '-',
                    'software_handover_id' => null,
                    'hr_account_id' => $r->hr_account_id,
                    'hr_company_id' => $r->hr_company_id,
                    'name' => $r->company_name ?? '-',
                    'joined_date' => $r->created_at
                        ? Carbon::parse($r->created_at)->format('d-m-Y')
                        : '-',
                    'status' => strtolower((string) $r->status) === 'active' ? 'Active' : 'Inactive',
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Base query for reseller children with the active search/status filters
     * applied (shared by the list builder and the badge counts).
     */
    protected function resellerChildrenQuery(int $resellerId)
    {
        $query = ResellerV2::where('parent_reseller_id', $resellerId);

        if (!empty($this->search)) {
            $query->where('company_name', 'like', '%' . $this->search . '%');
        }

        if ($this->statusFilter === 'active') {
            $query->whereRaw('LOWER(status) = ?', ['active']);
        } elseif ($this->statusFilter === 'inactive') {
            $query->whereRaw('LOWER(status) != ?', ['active']);
        }

        return $query;
    }

    protected function computeCounts(int $resellerId, ?int $currentSwId): void
    {
        // Count DISTINCT customers (software_handover_id + license_category
        // bucket), not raw HrLicense rows. A customer counts as "Active" if
        // any of its licenses is currently Enabled. This matches the dedupe
        // logic in loadCustomers() and the view's Active/Inactive semantics.
        $rows = HrLicense::whereHas('softwareHandover', function ($q) use ($resellerId, $currentSwId) {
            $q->where('reseller_id', $resellerId);
            if ($currentSwId !== null) {
                $q->where('id', '!=', $currentSwId);
            }
        })
        ->select('software_handover_id', 'license_category', 'status')
        ->get();

        $perCustomer = $rows
            ->groupBy(fn ($r) => $r->software_handover_id . '|' . $r->license_category)
            ->map(fn ($group) => [
                'license_category' => $group->first()->license_category,
                'enabled' => $group->contains(fn ($r) => strtolower((string) $r->status) === 'enabled'),
            ]);

        $this->resellerActiveCount     = $perCustomer->where('license_category', 'Reseller')->where('enabled', true)->count();
        $this->resellerInactiveCount   = $perCustomer->where('license_category', 'Reseller')->where('enabled', false)->count();
        $this->subscriberActiveCount   = $perCustomer->where('license_category', 'Subscriber')->where('enabled', true)->count();
        $this->subscriberInactiveCount = $perCustomer->where('license_category', 'Subscriber')->where('enabled', false)->count();

        // Add reseller children linked via parent_reseller_id (unfiltered totals).
        $childStatuses = ResellerV2::where('parent_reseller_id', $resellerId)->pluck('status');
        $this->resellerActiveCount   += $childStatuses->filter(fn ($s) => strtolower((string) $s) === 'active')->count();
        $this->resellerInactiveCount += $childStatuses->filter(fn ($s) => strtolower((string) $s) !== 'active')->count();
    }

    public function searchCustomers(): void
    {
        $this->loadCustomers();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->statusFilter = 'all';
        $this->startDate = null;
        $this->endDate = null;
        $this->loadCustomers();
    }

    public function render()
    {
        return view('livewire.hr-admin-dashboard.company-customer-tab');
    }
}
