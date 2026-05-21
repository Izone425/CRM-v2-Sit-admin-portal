<?php

namespace App\Livewire\HrAdminDashboard;

use App\Models\SoftwareHandover;
use App\Models\LicenseCertificate;
use App\Models\Reseller;
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

    // Form data
    public ?string $trialStartDate = null;
    public ?string $trialEndDate = null;
    public ?int $dealerId = null;
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

        if ($softwareHandover) {
            $this->dealerId = $softwareHandover->reseller_id;

            // Load license certificate for trial period
            if ($softwareHandover->license_certification_id) {
                $licenseCert = LicenseCertificate::find($softwareHandover->license_certification_id);
                if ($licenseCert) {
                    $this->trialStartDate = $licenseCert->buffer_license_start?->format('Y-m-d');
                    $this->trialEndDate = $licenseCert->buffer_license_end?->format('Y-m-d');
                }
            }
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
        $sw = SoftwareHandover::find($this->softwareHandoverId);
        if ($sw) {
            $sw->update(['reseller_id' => $value ?: null]);
            Notification::make()
                ->title($value ? 'Dealer assigned successfully.' : 'Dealer unlinked successfully.')
                ->success()
                ->send();
        }
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
        return Reseller::pluck('company_name', 'id')->toArray();
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
