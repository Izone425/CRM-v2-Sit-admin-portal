<?php

namespace App\Livewire\SalespersonDashboard;

use App\Models\ResellerInstallationPayment;
use App\Models\FinanceHandover;
use App\Models\User;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\Attributes\On;

class InstallationPaymentCompleted extends Component implements HasForms, HasTable
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

    public function getCompletedPayments()
    {
        $this->selectedUser = $this->selectedUser ?? session('selectedUser') ?? auth()->id();

        $query = ResellerInstallationPayment::query()
            ->where('status', 'completed')
            ->orderBy('completed_at', 'desc');

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
            ->query($this->getCompletedPayments())
            ->columns([
                TextColumn::make('formatted_id')
                    ->label('ID')
                    ->searchable(query: function ($query, string $search) {
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

                TextColumn::make('finance_handover_id')
                    ->label('Finance Handover')
                    ->default('-')
                    ->weight('bold')
                    ->color('primary')
                    ->action(
                        Action::make('viewFinanceHandover')
                            ->modalHeading(false)
                            ->modalWidth('4xl')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalContent(function (ResellerInstallationPayment $record): ?View {
                                $formattedId = $record->finance_handover_id;
                                if (!$formattedId) {
                                    return null;
                                }
                                $numericId = intval(substr($formattedId, 5));
                                $handover = FinanceHandover::find($numericId);
                                if (!$handover) {
                                    return null;
                                }
                                return view('components.finance-handover-details', [
                                    'record' => $handover,
                                ]);
                            })
                    ),
            ])
            ->emptyState(fn () => view('components.empty-state-question'))
            ->defaultPaginationPageOption(10);
    }

    public function render()
    {
        return view('livewire.salesperson-dashboard.installation-payment-completed');
    }
}
