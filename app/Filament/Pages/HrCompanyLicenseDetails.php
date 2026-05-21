<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Request;

class HrCompanyLicenseDetails extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static string $view = 'filament.pages.hr-company-license-details';
    protected static ?string $title = 'Company License Details';
    protected static ?string $slug = 'hr-company-license-details';
    protected static bool $shouldRegisterNavigation = false;

    public ?string $handoverId = null;
    public ?int $softwareHandoverId = null;
    public ?int $hrAccountId = null;
    public ?int $hrCompanyId = null;
    public ?string $companyName = null;
    public ?string $tab = null;

    public function mount(): void
    {
        // Get parameters from query string
        $this->handoverId = Request::query('handoverId');
        $this->softwareHandoverId = Request::query('softwareHandoverId') ? (int) Request::query('softwareHandoverId') : null;
        $this->hrAccountId = Request::query('hrAccountId') ? (int) Request::query('hrAccountId') : null;
        $this->hrCompanyId = Request::query('hrCompanyId') ? (int) Request::query('hrCompanyId') : null;
        $this->tab = Request::query('tab');

        // Resolve HR IDs from software handover when legacy URL only contains softwareHandoverId.
        if (($this->hrAccountId === null || $this->hrCompanyId === null) && $this->softwareHandoverId) {
            $softwareHandover = \App\Models\SoftwareHandover::select(['id', 'hr_account_id', 'hr_company_id'])
                ->find($this->softwareHandoverId);
            $this->hrAccountId = $this->hrAccountId ?? $softwareHandover?->hr_account_id;
            $this->hrCompanyId = $this->hrCompanyId ?? $softwareHandover?->hr_company_id;
        }

        // Resolve software handover + HR IDs from handover/project code link.
        if ($this->handoverId) {
            $hrLicense = \App\Models\HrLicense::where('handover_id', $this->handoverId)->first();
            $this->companyName = $hrLicense?->company_name;

            if (!$this->softwareHandoverId && $hrLicense) {
                $this->softwareHandoverId = $hrLicense->software_handover_id;
            }

            if (($this->hrAccountId === null || $this->hrCompanyId === null) && $this->softwareHandoverId) {
                $softwareHandover = \App\Models\SoftwareHandover::select(['id', 'hr_account_id', 'hr_company_id'])
                    ->find($this->softwareHandoverId);
                $this->hrAccountId = $this->hrAccountId ?? $softwareHandover?->hr_account_id;
                $this->hrCompanyId = $this->hrCompanyId ?? $softwareHandover?->hr_company_id;
            }
        }

        // Prefer resolving company by HR identity to support multi-handover customers.
        if ($this->hrAccountId !== null && $this->hrCompanyId !== null) {
            $latestSoftwareHandover = \App\Models\SoftwareHandover::where('hr_account_id', $this->hrAccountId)
                ->where('hr_company_id', $this->hrCompanyId)
                ->latest('id')
                ->first();

            if (!$this->softwareHandoverId && $latestSoftwareHandover) {
                $this->softwareHandoverId = $latestSoftwareHandover->id;
            }

            if (!$this->companyName) {
                $this->companyName = $latestSoftwareHandover?->company_name;
            }
        }

        if (!$this->companyName && $this->softwareHandoverId) {
            $softwareHandover = \App\Models\SoftwareHandover::find($this->softwareHandoverId);
            $this->companyName = $softwareHandover?->company_name;
        }
    }

    public function getTitle(): string
    {
        return 'Company License Details';
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin/hr-license') => 'All Licenses',
            '#' => 'Company Details',
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('viewLead')
                ->label('Swipe UI')
                ->icon('heroicon-o-arrows-right-left')
                ->color('info')
                ->url(function () {
                    if (!$this->softwareHandoverId) return null;

                    $handover = \App\Models\SoftwareHandover::find($this->softwareHandoverId);
                    if (!$handover || !$handover->lead_id) return null;

                    return route('filament.admin.resources.leads.view', [
                        'record' => \App\Classes\Encryptor::encrypt($handover->lead_id),
                    ]) . '?view=admin_renewal_v2';
                })
                ->visible(fn () => $this->softwareHandoverId !== null),
        ];
    }
}
