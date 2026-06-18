<?php

namespace App\Livewire\HrAdminDashboard;

use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use App\Models\Customer;

class CompanyUsersTab extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public ?int $softwareHandoverId = null;
    public array $companyData = [];
    public Collection $users;

    public bool $showEditDrawer = false;
    public ?string $editLoginId = null;
    public string $newPassword = '';
    public string $confirmPassword = '';

    public bool $showLoginLogModal = false;
    public Collection $loginLogs;

    public function mount(?int $softwareHandoverId = null, array $companyData = [])
    {
        $this->softwareHandoverId = $softwareHandoverId;
        $this->companyData = $companyData;
        $this->loginLogs = collect();
        $this->loadUsers();
    }

    public function openLoginLogModal(): void
    {
        $hrCompanyId = $this->companyData['hr_company_id'] ?? null;
        $this->loginLogs = $hrCompanyId
            ? \App\Models\HrLoginAsUserLog::with('causer')
                ->where('hr_company_id', $hrCompanyId)
                ->orderByDesc('id')
                ->limit(100)
                ->get()
            : collect();
        $this->showLoginLogModal = true;
    }

    public function closeLoginLogModal(): void
    {
        $this->showLoginLogModal = false;
        $this->loginLogs = collect();
    }

    protected function loadUsers(): void
    {
        $allHandoverIds = $this->companyData['all_handover_ids'] ?? [];
        if (empty($allHandoverIds) && $this->softwareHandoverId) {
            $allHandoverIds = [$this->softwareHandoverId];
        }

        $customers = Customer::whereIn('sw_id', $allHandoverIds)->get();

        $this->users = $customers->map(function ($customer, $index) {
            return [
                'id' => $customer->id,
                'backend_user_id' => $this->companyData['hr_user_id'] ?? '-',
                'full_name' => $customer->name ?? '-',
                'login_id' => $customer->email,
                'password' => $customer->plain_password ?? '-',
                'role' => $index === 0 ? 'OWNER' : 'USER',
                'status' => $customer->status ?? 'Active',
                'ta' => false,
                'tl' => false,
                'tc' => false,
                'tp' => false,
                'to' => false,
                'tr' => false,
                'tap' => false,
                'tt' => false,
            ];
        });

        // Reseller / Distributor fallback: no SoftwareHandover means no rows
        // in the Customer table. The CRM-side admin user (created by
        // createAccount during the approval flow) lives on the reseller_v2 /
        // distributor_v2 row itself. Synthesize a single OWNER user entry so
        // the Users tab reflects who can log in.
        if ($this->users->isEmpty()) {
            $partner = $this->companyData['reseller_v2']
                ?? $this->companyData['distributor_v2']
                ?? null;
            if ($partner) {
                $modules = is_array($partner->modules) ? $partner->modules : [];

                $this->users = collect([[
                    'id' => $partner->id,
                    'backend_user_id' => $this->companyData['hr_user_id']
                        ?? $partner->hr_user_id
                        ?? '-',
                    'full_name' => $partner->name ?? '-',
                    'login_id' => $partner->email,
                    'password' => $partner->plain_password ?? '-',
                    'role' => 'OWNER',
                    'status' => $partner->status ?? 'Active',
                    'ta' => in_array('attendance', $modules, true),
                    'tl' => in_array('leave', $modules, true),
                    'tc' => in_array('claim', $modules, true),
                    'tp' => in_array('payroll', $modules, true),
                    'to' => false,
                    'tr' => false,
                    'tap' => false,
                    'tt' => false,
                ]]);
            }
        }
    }

    public function openEditDrawer(string $loginId): void
    {
        $this->editLoginId = $loginId;
        $this->newPassword = '';
        $this->confirmPassword = '';
        $this->resetValidation();
        $this->showEditDrawer = true;
    }

    public function closeEditDrawer(): void
    {
        $this->showEditDrawer = false;
        $this->editLoginId = null;
        $this->newPassword = '';
        $this->confirmPassword = '';
        $this->resetValidation();
    }

    public function updatePassword(): void
    {
        $this->validate([
            'newPassword' => 'required|min:6',
            'confirmPassword' => 'required|same:newPassword',
        ], [
            'confirmPassword.same' => 'The confirm password must match the new password.',
        ]);

        $customer = Customer::where('sw_id', $this->softwareHandoverId)
            ->where('email', $this->editLoginId)
            ->first();

        if (!$customer) {
            $this->addError('newPassword', 'Customer record not found for this login ID.');
            return;
        }

        $customer->forceFill([
            'password' => Hash::make($this->newPassword),
            'plain_password' => $this->newPassword,
        ])->save();

        $this->closeEditDrawer();

        Notification::make()
            ->success()
            ->title('Password updated successfully')
            ->send();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                // Using a query builder with the collection as a workaround
                \App\Models\HrLicense::query()->whereRaw('1 = 0') // Empty query as placeholder
            )
            ->emptyState(fn () => view('livewire.hr-admin-dashboard.company-users-tab-content', ['users' => $this->users]))
            ->columns([])
            ->paginated(false);
    }

    public function render()
    {
        return view('livewire.hr-admin-dashboard.company-users-tab');
    }
}
