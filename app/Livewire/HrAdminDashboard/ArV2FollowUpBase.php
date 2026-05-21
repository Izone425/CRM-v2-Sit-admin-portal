<?php

namespace App\Livewire\HrAdminDashboard;

use App\Filament\Actions\AdminRenewalActions;
use App\Models\AdminRenewalLogs;
use App\Models\CompanyDetail;
use App\Models\HrLicense;
use App\Models\Renewal;
use App\Models\User;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\On;
use Livewire\Component;

abstract class ArV2FollowUpBase extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public $lastRefreshTime;

    // Override in subclasses
    protected string $renewalProgress = 'pending_confirmation';
    protected string $datePeriod = 'today'; // today, overdue, upcoming, all

    public function mount()
    {
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
    }

    public function refreshTable()
    {
        $this->resetTable();
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');

        Notification::make()->title('Table refreshed')->success()->send();
    }

    #[On('refresh-admin-renewal-tables')]
    public function refreshData()
    {
        $this->resetTable();
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
    }

    protected function getBaseQuery()
    {
        $query = Renewal::query()
            ->where('hr_version', 2)
            ->where('follow_up_counter', true)
            ->where('mapping_status', 'completed_mapping')
            ->where('renewal_progress', $this->renewalProgress);

        match ($this->datePeriod) {
            'today' => $query->whereDate('follow_up_date', today()),
            'overdue' => $query->whereDate('follow_up_date', '<', today()),
            'upcoming' => $query->whereDate('follow_up_date', '>', today()),
            'all' => null,
        };

        $query->selectRaw('*, DATEDIFF(NOW(), follow_up_date) as pending_days');

        return $query;
    }

    protected function getEarliestExpiryDate($softwareHandoverId): ?string
    {
        if (!$softwareHandoverId) return null;

        try {
            return HrLicense::where('software_handover_id', $softwareHandoverId)
                ->where('type', 'PAID')
                ->where('status', 'Enabled')
                ->min('end_date');
        } catch (\Exception $e) {
            return null;
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('300s')
            ->query($this->getBaseQuery())
            ->emptyState(fn () => view('components.empty-state-question'))
            ->defaultPaginationPageOption(5)
            ->paginated([5])
            ->filters([
                SelectFilter::make('admin_renewal')
                    ->label('Filter by Admin Renewal')
                    ->options(fn () => User::where('role_id', 3)->pluck('name', 'name')->toArray())
                    ->placeholder('All Admin Renewals')
                    ->multiple(),
            ])
            ->columns([
                TextColumn::make('admin_renewal')
                    ->label('Admin Renewal')
                    ->visible(fn (): bool => auth()->user()->role_id !== 3),

                TextColumn::make('company_name')
                    ->label('Company Name')
                    ->searchable()
                    ->formatStateUsing(function ($state, $record) {
                        if ($record->lead_id) {
                            $company = CompanyDetail::where('lead_id', $record->lead_id)->first();
                            if ($company) {
                                $encryptedId = \App\Classes\Encryptor::encrypt($company->lead_id);
                                return new HtmlString('<a href="' . url('admin/leads/' . $encryptedId) . '?view=admin_renewal_v2" target="_blank" style="color:#338cf0;">' . e($company->company_name) . '</a>');
                            }
                        }
                        return $state;
                    })
                    ->html(),

                TextColumn::make('renewal_progress')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'new' => 'New',
                        'pending_confirmation' => 'Pending Confirmation',
                        'pending_payment' => 'Pending Payment',
                        'completed_renewal' => 'Completed Payment',
                        default => ucfirst(str_replace('_', ' ', $state)),
                    }),

                TextColumn::make('earliest_expiry_date')
                    ->label('Expiry Date')
                    ->default('N/A')
                    ->getStateUsing(function ($record) {
                        $date = $this->getEarliestExpiryDate($record->software_handover_id);
                        return $date ? Carbon::parse($date)->format('d M Y') : 'N/A';
                    }),

                TextColumn::make('pending_days')
                    ->label('Pending Days')
                    ->alignCenter()
                    ->sortable()
                    ->default('0')
                    ->formatStateUsing(fn ($state) => $state . ' ' . ($state == 0 ? 'Day' : 'Days')),

                TextColumn::make('follow_up_date')
                    ->label('Follow Up Date')
                    ->date('d M Y'),
            ])
            ->actions([
                ActionGroup::make([
                    AdminRenewalActions::viewAction(),
                    AdminRenewalActions::viewLastFollowUpAction(),
                    AdminRenewalActions::addAdminRenewalFollowUp()
                        ->action(function (Renewal $record, array $data) {
                            AdminRenewalActions::processFollowUpWithEmail($record, $data);
                            $this->dispatch('refresh-admin-renewal-tables');
                        }),
                ])
                ->button()
                ->color($this->renewalProgress === 'pending_confirmation' ? 'warning' : 'danger')
                ->label('Actions')
            ]);
    }
}
