<?php

namespace App\Livewire\HrAdminDashboard;

use App\Models\SoftwareHandover;
use App\Models\LicenseCertificate;
use App\Models\Reseller;
use App\Models\ResellerV2;
use App\Models\DistributorV2;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Livewire\Component;

class CompanyAccountSettingTab extends Component implements HasForms
{
    use InteractsWithForms;

    public ?int $softwareHandoverId = null;
    public array $companyData = [];

    // Resolved save target for the Dealer/Distributor assignment.
    // Subscribers save to SoftwareHandover; Resellers/Distributors have no
    // SoftwareHandover, so they save to the v2 row's parent_reseller_id.
    public ?string $assignTargetType = null; // 'software_handover' | 'reseller_v2' | 'distributor_v2'
    public ?int $assignTargetId = null;

    // Form data
    public ?string $trialStartDate = null;
    public ?string $trialEndDate = null;
    public ?int $dealerId = null;
    public string $dealerSearch = '';
    public ?int $referralId = null;
    public ?string $billingMethod = null;
    public ?int $salesPersonId = null;

    public function mount(?int $softwareHandoverId = null, array $companyData = [])
    {
        $this->softwareHandoverId = $softwareHandoverId;
        $this->companyData = $companyData;
        $this->loadSettingsData();
    }

    protected function loadSettingsData(): void
    {
        $softwareHandover = $this->companyData['software_handover'] ?? null;
        $resellerV2 = $this->companyData['reseller_v2'] ?? null;
        $distributorV2 = $this->companyData['distributor_v2'] ?? null;

        if ($softwareHandover) {
            // Subscriber: the assigned dealer lives on the SoftwareHandover.
            $this->assignTargetType = 'software_handover';
            $this->assignTargetId = $this->softwareHandoverId;
            $this->dealerId = $softwareHandover->reseller_id;

            // Load license certificate for trial period
            if ($softwareHandover->license_certification_id) {
                $licenseCert = LicenseCertificate::find($softwareHandover->license_certification_id);
                if ($licenseCert) {
                    $this->trialStartDate = $licenseCert->buffer_license_start?->format('Y-m-d');
                    $this->trialEndDate = $licenseCert->buffer_license_end?->format('Y-m-d');
                }
            }
        } elseif ($resellerV2) {
            // Reseller: no SoftwareHandover — save the upline dealer to
            // parent_reseller_id (reseller_id stays the self/child-lookup code).
            $this->assignTargetType = 'reseller_v2';
            $this->assignTargetId = $resellerV2->id;
            $this->dealerId = $resellerV2->parent_reseller_id;
        } elseif ($distributorV2) {
            // Distributor: same shape as Reseller.
            $this->assignTargetType = 'distributor_v2';
            $this->assignTargetId = $distributorV2->id;
            $this->dealerId = $distributorV2->parent_reseller_id;
        }
    }

    public function updateTrialPeriod(): void
    {
        // TODO: Implement trial period update logic
        Notification::make()
            ->title('Trial period updated')
            ->success()
            ->send();
    }

    public function updatedDealerId($value): void
    {
        $record = match ($this->assignTargetType) {
            'software_handover' => SoftwareHandover::find($this->assignTargetId),
            'reseller_v2' => ResellerV2::find($this->assignTargetId),
            'distributor_v2' => DistributorV2::find($this->assignTargetId),
            default => null,
        };

        if (! $record) {
            Notification::make()
                ->title('Unable to save: no company record to assign.')
                ->danger()
                ->send();
            return;
        }

        $column = $this->assignTargetType === 'software_handover'
            ? 'reseller_id'
            : 'parent_reseller_id';

        $record->update([$column => $value ?: null]);

        Notification::make()
            ->title($value ? 'Dealer assigned successfully.' : 'Dealer unlinked successfully.')
            ->success()
            ->send();
    }

    public function updatedBillingMethod($value): void
    {
        // TODO: Implement billing method save logic
        if ($value) {
            Notification::make()
                ->title('Billing method updated successfully.')
                ->success()
                ->send();
        }
    }

    public function updatedReferralId($value): void
    {
        // TODO: Implement referral save logic
        if ($value) {
            Notification::make()
                ->title('Referral assigned successfully.')
                ->success()
                ->send();
        }
    }

    public function getDealerOptions(): array
    {
        return Reseller::query()
            ->when($this->dealerSearch !== '', fn ($q) =>
                $q->whereRaw('UPPER(company_name) LIKE ?', ['%' . strtoupper($this->dealerSearch) . '%'])
            )
            ->orderBy('company_name')
            ->limit(50)
            ->pluck('company_name', 'id')
            ->toArray();
    }

    public function getSelectedDealerLabel(): ?string
    {
        return $this->dealerId
            ? Reseller::where('id', $this->dealerId)->value('company_name')
            : null;
    }

    public function selectDealer(?int $id): void
    {
        $this->dealerSearch = '';
        $this->dealerId = $id;
        $this->updatedDealerId($id);
    }

    public function getSalesPersonOptions(): array
    {
        // Get sales persons from users table (role_id = 2 is for salesperson)
        return \App\Models\User::where('is_active', true)
            ->where('role_id', 2)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    public function updatedSalesPersonId($value): void
    {
        if ($value) {
            $sw = SoftwareHandover::find($this->softwareHandoverId);
            if ($sw) {
                $salesPerson = \App\Models\User::find($value);
                $sw->update(['salesperson' => $salesPerson?->name]);
                Notification::make()
                    ->title('Sales Person assigned successfully.')
                    ->success()
                    ->send();
            }
        }
    }

    public function render()
    {
        return view('livewire.hr-admin-dashboard.company-account-setting-tab');
    }
}
