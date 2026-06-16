<?php

namespace App\Livewire\HrAdminDashboard;

use App\Models\HrLicense;
use App\Models\Reseller;
use App\Models\SoftwareHandover;
use App\Models\ResellerV2;
use Livewire\Component;

class CompanyLicenseDetailsContainer extends Component
{
    public $activeTab = 'profile';
    public ?string $handoverId = null;
    public ?int $softwareHandoverId = null;
    public ?int $hrAccountId = null;
    public ?int $hrCompanyId = null;
    public $companyData = [];

    protected $listeners = ['switchTab'];

    public function mount(
        ?string $handoverId = null,
        ?int $softwareHandoverId = null,
        ?int $hrAccountId = null,
        ?int $hrCompanyId = null,
        ?string $tab = null
    )
    {
        $this->handoverId = $handoverId;
        $this->softwareHandoverId = $softwareHandoverId;
        $this->hrAccountId = $hrAccountId;
        $this->hrCompanyId = $hrCompanyId;

        if (($this->hrAccountId === null || $this->hrCompanyId === null) && $this->softwareHandoverId) {
            $softwareHandover = SoftwareHandover::select(['id', 'hr_account_id', 'hr_company_id'])
                ->find($this->softwareHandoverId);
            $this->hrAccountId = $this->hrAccountId ?? $softwareHandover?->hr_account_id;
            $this->hrCompanyId = $this->hrCompanyId ?? $softwareHandover?->hr_company_id;
        }

        if (!$this->softwareHandoverId && $this->hrAccountId !== null && $this->hrCompanyId !== null) {
            $this->softwareHandoverId = SoftwareHandover::where('hr_account_id', $this->hrAccountId)
                ->where('hr_company_id', $this->hrCompanyId)
                ->latest('id')
                ->value('id');
        }

        if (($this->hrAccountId === null || $this->hrCompanyId === null) && $this->handoverId) {
            $hrLicense = HrLicense::where('handover_id', $this->handoverId)->first();
            if (!$this->softwareHandoverId && $hrLicense) {
                $this->softwareHandoverId = $hrLicense->software_handover_id;
            }

            if ($this->softwareHandoverId) {
                $softwareHandover = SoftwareHandover::select(['id', 'hr_account_id', 'hr_company_id'])
                    ->find($this->softwareHandoverId);
                $this->hrAccountId = $this->hrAccountId ?? $softwareHandover?->hr_account_id;
                $this->hrCompanyId = $this->hrCompanyId ?? $softwareHandover?->hr_company_id;
            }
        }

        $this->loadCompanyData();

        if ($tab) {
            $validTabs = ['users', 'profile', 'products', 'customer', 'commission', 'invoice', 'account_setting'];
            if (in_array($tab, $validTabs)) {
                $this->activeTab = $tab;
            }
        }
    }

    protected function loadCompanyData(): void
    {
        $softwareHandover = null;
        $hrLicense = null;
        $companyDetail = null;

        // Load software handover by explicit ID first.
        if ($this->softwareHandoverId) {
            $softwareHandover = SoftwareHandover::with([
                'lead.companyDetail',
                'lead.bankDetail',
                'lead.subsidiaries',
            ])->find($this->softwareHandoverId);
            $companyDetail = $softwareHandover?->lead?->companyDetail;
        } elseif ($this->hrAccountId !== null && $this->hrCompanyId !== null) {
            // Primary resolution by customer identity in HR backend.
            $softwareHandover = SoftwareHandover::with([
                'lead.companyDetail',
                'lead.bankDetail',
                'lead.subsidiaries',
            ])
                ->where('hr_account_id', $this->hrAccountId)
                ->where('hr_company_id', $this->hrCompanyId)
                ->latest('id')
                ->first();

            if ($softwareHandover) {
                $this->softwareHandoverId = $softwareHandover->id;
                $companyDetail = $softwareHandover->lead?->companyDetail;
            }
        }

        // Load HrLicense
        if ($this->handoverId) {
            $hrLicense = HrLicense::where('handover_id', $this->handoverId)->first();
        } elseif ($this->softwareHandoverId) {
            $hrLicense = HrLicense::where('software_handover_id', $this->softwareHandoverId)->first();
        } elseif ($this->hrAccountId !== null && $this->hrCompanyId !== null) {
            $hrLicense = HrLicense::whereHas('softwareHandover', function ($q) {
                $q->where('hr_account_id', $this->hrAccountId)
                    ->where('hr_company_id', $this->hrCompanyId);
            })->first();
        }

        // Lookup ResellerV2 via the shared reseller_id (Subscriber path).
        // For reseller licenses (no software handover) fall back to looking up
        // ResellerV2 by CRM IDs, or by parsing the RSL_xxxxxx handover_id pattern.
        $resellerV2 = null;
        if ($softwareHandover && $softwareHandover->reseller_id) {
            $resellerV2 = ResellerV2::with('commission')
                ->where('reseller_id', $softwareHandover->reseller_id)
                ->first();
        } elseif (
            ($this->hrAccountId !== null && $this->hrCompanyId !== null)
            || ($this->handoverId && str_starts_with($this->handoverId, 'RSL_'))
        ) {
            if ($this->hrAccountId !== null && $this->hrCompanyId !== null) {
                $resellerV2 = ResellerV2::with('commission')
                    ->where('hr_account_id', $this->hrAccountId)
                    ->where('hr_company_id', $this->hrCompanyId)
                    ->first();
            }

            if (! $resellerV2 && $this->handoverId && str_starts_with($this->handoverId, 'RSL_')) {
                $resellerId = (int) ltrim(substr($this->handoverId, 4), '0');
                $resellerV2 = ResellerV2::with('commission')->find($resellerId);
            }

            // Backfill the CRM IDs onto the component so downstream code
            // (CRM API tabs, etc.) can pick them up the same as for Subscriber.
            if ($resellerV2) {
                $this->hrAccountId = $this->hrAccountId ?? $resellerV2->hr_account_id;
                $this->hrCompanyId = $this->hrCompanyId ?? $resellerV2->hr_company_id;
            }
        }

        // Build upline info for Reseller/Subscriber companies
        $uplineInfo = null;
        if ($softwareHandover && $softwareHandover->reseller_id) {
            $parentReseller = Reseller::find($softwareHandover->reseller_id);
            if ($parentReseller) {
                // Find the parent Distributor's HrLicense via reseller_id relationship
                $parentLicense = HrLicense::where('license_category', 'Distributor')
                    ->whereHas('softwareHandover', function ($q) use ($softwareHandover) {
                        $q->where('reseller_id', $softwareHandover->reseller_id);
                    })
                    ->where('software_handover_id', '!=', $this->softwareHandoverId)
                    ->first();
                $parentSwId = $parentLicense?->software_handover_id;

                // Only show upline if parent is a different company
                if ($parentSwId) {
                    $uplineInfo = [
                        'name' => $parentReseller->company_name,
                        'software_handover_id' => $parentSwId,
                        'commission_rate' => $resellerV2?->commission_rate,
                    ];
                }
            }
        }

        // Collect all software handover IDs for this customer
        $resolvedAccountId = $this->hrAccountId ?? $softwareHandover?->hr_account_id;
        $resolvedCompanyId = $this->hrCompanyId ?? $softwareHandover?->hr_company_id;

        $allHandoverIds = [];
        $allFormattedHandoverIds = [];
        if ($resolvedAccountId && $resolvedCompanyId) {
            $swHandovers = SoftwareHandover::where('hr_account_id', $resolvedAccountId)
                ->where('hr_company_id', $resolvedCompanyId)
                ->where('hr_version', 2)
                ->get(['id', 'created_at']);
            $allHandoverIds = $swHandovers->pluck('id')->toArray();
            foreach ($swHandovers as $sw) {
                $allFormattedHandoverIds[] = $sw->formatted_handover_id;
            }
            // Also find headcount handover formatted IDs via HrLicense or HrSalesInvoice
            $leadIds = SoftwareHandover::whereIn('id', $allHandoverIds)->pluck('lead_id')->unique()->toArray();
            if (!empty($leadIds)) {
                $hcHandovers = \App\Models\HeadcountHandover::whereIn('lead_id', $leadIds)->get(['id']);
                foreach ($hcHandovers as $hc) {
                    $allFormattedHandoverIds[] = $hc->formatted_handover_id;
                }
            }
        } elseif ($this->softwareHandoverId) {
            $allHandoverIds = [$this->softwareHandoverId];
        }
        // Collect formatted handover IDs from HrLicense if not already populated
        if (empty($allFormattedHandoverIds) && !empty($allHandoverIds)) {
            $allFormattedHandoverIds = \App\Models\HrLicense::whereIn('software_handover_id', $allHandoverIds)
                ->pluck('handover_id')->unique()->filter()->toArray();
        }

        // Reseller fallback: no SoftwareHandover exists for resellers, so
        // derive the formatted RSL_xxxxxx handover ID from reseller_v2.id.
        // Mirrors the padding used by ResellerApprovalService when it inserts
        // the hr_licenses rows.
        if (empty($allFormattedHandoverIds) && $resellerV2) {
            $allFormattedHandoverIds[] = 'RSL_' . str_pad((string) $resellerV2->id, 6, '0', STR_PAD_LEFT);
        }

        // Build company data context
        $this->companyData = [
            'software_handover' => $softwareHandover,
            'all_handover_ids' => $allHandoverIds,
            'all_formatted_handover_ids' => $allFormattedHandoverIds,
            'hr_license' => $hrLicense,
            'company_detail' => $companyDetail,
            'lead' => $softwareHandover?->lead,
            'bank_detail' => $softwareHandover?->lead?->bankDetail,
            'subsidiary' => $softwareHandover?->lead?->subsidiaries?->first(),
            'company_name' => $hrLicense?->company_name ?? $softwareHandover?->company_name ?? 'Unknown Company',
            'handover_id' => $this->handoverId ?? $hrLicense?->handover_id,
            'hr_account_id' => $resolvedAccountId,
            'hr_company_id' => $resolvedCompanyId,
            'hr_user_id' => $softwareHandover?->hr_user_id ?? $resellerV2?->hr_user_id,
            'license_category' => $hrLicense?->license_category ?? 'Subscriber',
            'reseller_v2' => $resellerV2,
            'upline_info' => $uplineInfo,
        ];
    }

    public function switchToTab(string $tab): void
    {
        $validTabs = ['users', 'profile', 'products', 'customer', 'commission', 'invoice', 'account_setting'];
        if (in_array($tab, $validTabs)) {
            $this->activeTab = $tab;
        }
    }

    public function render()
    {
        return view('livewire.hr-admin-dashboard.company-license-details-container');
    }
}
