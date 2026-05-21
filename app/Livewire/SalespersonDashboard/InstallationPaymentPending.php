<?php

namespace App\Livewire\SalespersonDashboard;

use App\Models\ResellerInstallationPayment;
use App\Models\FinanceHandover;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\On;

class InstallationPaymentPending extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public $selectedUser;
    public $lastRefreshTime;
    public $showDetailModal = false;
    public $selectedPayment = null;

    public function mount()
    {
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
    }

    public function openDetailModal($paymentId)
    {
        $this->selectedPayment = ResellerInstallationPayment::find($paymentId);
        $this->showDetailModal = true;
    }

    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->selectedPayment = null;
    }

    public function refreshTable()
    {
        $this->resetTable();
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');

        Notification::make()
            ->title('Table refreshed')
            ->success()
            ->send();
    }

    #[On('updateTablesForUser')]
    public function updateTablesForUser($selectedUser)
    {
        $this->selectedUser = $selectedUser;
        session(['selectedUser' => $selectedUser]);
        $this->resetTable();
    }

    public function getPendingPayments()
    {
        $this->selectedUser = $this->selectedUser ?? session('selectedUser') ?? auth()->id();

        $query = ResellerInstallationPayment::query()
            ->where('status', 'new')
            ->orderBy('created_at', 'desc');

        if ($this->selectedUser === 'all-salespersons') {
            $salespersonIds = User::where('role_id', 2)->pluck('id');
            $query->whereIn('attention_to', $salespersonIds);
        } elseif (is_numeric($this->selectedUser)) {
            $query->where('attention_to', $this->selectedUser);
        } else {
            $query->where('attention_to', auth()->id());
        }

        return $query;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getPendingPayments())
            ->columns([
                TextColumn::make('formatted_id')
                    ->label('ID')
                    ->searchable(query: function ($query, string $search) {
                        // formatted_id is an accessor, search by id instead
                        $query->where('id', 'like', "%{$search}%");
                    })
                    ->sortable(query: function ($query, string $direction) {
                        $query->orderBy('id', $direction);
                    })
                    ->weight('bold')
                    ->color('primary')
                    ->action(
                        Action::make('viewDetail')
                            ->action(fn (ResellerInstallationPayment $record) => $this->openDetailModal($record->id))
                    ),

                TextColumn::make('salesperson.name')
                    ->label('SalesPerson')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('reseller_company_name')
                    ->label('Reseller Company Name')
                    ->searchable(query: function ($query, string $search) {
                        $query->where('reseller_name', 'like', "%{$search}%");
                    })
                    ->formatStateUsing(fn ($state) => strtoupper($state)),

                TextColumn::make('customer_name')
                    ->label('Customer Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Submitted At')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->actions([
                Action::make('complete')
                    ->label('Complete')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->modalWidth('md')
                    ->form([
                        Select::make('finance_handover_id')
                            ->label('Finance Handover ID')
                            ->required()
                            ->searchable()
                            ->options(function () {
                                $usedIds = ResellerInstallationPayment::whereNotNull('finance_handover_id')
                                    ->pluck('finance_handover_id')
                                    ->toArray();

                                return FinanceHandover::with(['lead.companyDetail', 'reseller'])
                                    ->where('created_by', auth()->id())
                                    ->where('id', '>=', 37)
                                    ->orderBy('id', 'desc')
                                    ->limit(200)
                                    ->get()
                                    ->filter(fn ($handover) => !in_array($handover->formatted_id, $usedIds))
                                    ->mapWithKeys(function ($handover) {
                                        $company = $handover->lead?->companyDetail?->company_name ?? $handover->lead?->name ?? '-';
                                        $reseller = $handover->reseller?->company_name ?? '-';
                                        return [$handover->formatted_id => $handover->formatted_id . ' | ' . $company . ' | ' . $reseller];
                                    })
                                    ->toArray();
                            })
                            ->getSearchResultsUsing(function (string $search) {
                                $usedIds = ResellerInstallationPayment::whereNotNull('finance_handover_id')
                                    ->pluck('finance_handover_id')
                                    ->toArray();

                                return FinanceHandover::with(['lead.companyDetail', 'reseller'])
                                    ->where('created_by', auth()->id())
                                    ->where('id', '>=', 37)
                                    ->where(function ($query) use ($search) {
                                        $query->where('id', 'like', "%{$search}%")
                                            ->orWhereHas('lead.companyDetail', function ($q) use ($search) {
                                                $q->where('company_name', 'like', "%{$search}%");
                                            })
                                            ->orWhereHas('reseller', function ($q) use ($search) {
                                                $q->where('company_name', 'like', "%{$search}%");
                                            });
                                    })
                                    ->orderBy('id', 'desc')
                                    ->limit(50)
                                    ->get()
                                    ->filter(fn ($handover) => !in_array($handover->formatted_id, $usedIds))
                                    ->mapWithKeys(function ($handover) {
                                        $company = $handover->lead?->companyDetail?->company_name ?? $handover->lead?->name ?? '-';
                                        $reseller = $handover->reseller?->company_name ?? '-';
                                        return [$handover->formatted_id => $handover->formatted_id . ' | ' . $company . ' | ' . $reseller];
                                    })
                                    ->toArray();
                            }),
                    ])
                    ->action(function (ResellerInstallationPayment $record, array $data): void {
                        $record->update([
                            'status' => 'completed',
                            'completed_at' => now(),
                            'finance_handover_id' => $data['finance_handover_id'],
                        ]);

                        Notification::make()
                            ->title('Installation Payment Completed')
                            ->success()
                            ->send();

                        $this->dispatch('installation-payment-updated');
                    }),
            ])
            ->emptyState(fn () => view('components.empty-state-question'))
            ->defaultPaginationPageOption(10);
    }

    public function render()
    {
        return view('livewire.salesperson-dashboard.installation-payment-pending');
    }
}
