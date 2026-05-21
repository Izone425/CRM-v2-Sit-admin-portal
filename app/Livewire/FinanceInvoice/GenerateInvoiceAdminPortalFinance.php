<?php

namespace App\Livewire\FinanceInvoice;

use App\Models\FinanceInvoice;
use App\Models\ResellerHandover;
use App\Models\CrmInvoiceDetail;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\Attributes\On;
use Filament\Tables\Actions\ActionGroup;

class GenerateInvoiceAdminPortalFinance extends Component implements HasTable, HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected $listeners = ['refresh-finance-invoice-tables' => '$refresh'];

    public $selectedUser;
    public $lastRefreshTime;

    public function mount()
    {
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
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

    #[On('refresh-softwarehandover-tables')]
    public function refreshData()
    {
        $this->resetTable();
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
    }

    #[On('updateTablesForUser')] // Listen for updates
    public function updateTablesForUser($selectedUser)
    {
        $this->selectedUser = $selectedUser;
        session(['selectedUser' => $selectedUser]); // Store for consistency

        $this->resetTable(); // Refresh the table
    }

    public function table(Table $table): Table
    {
        return $table
            ->emptyState(fn () => view('components.empty-state-question'))
            ->query(
                FinanceInvoice::where('portal_type', 'admin')
                    ->with(['resellerHandover', 'creator'])
                    ->orderBy('created_at', 'desc')
            )
            ->columns([
                TextColumn::make('formatted_id')
                    ->label('ID')
                    ->sortable()
                    ->weight('bold')
                    ->color('primary'),

                TextColumn::make('autocount_invoice_number')
                    ->label('AC Invoice')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('timetec_invoice_number')
                    ->label('TT Invoice')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('reseller_name')
                    ->label('Reseller Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('subscriber_name')
                    ->label('Subscriber Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('reseller_commission_amount')
                    ->label('Amount')
                    ->money('MYR')
                    ->sortable(),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('view_pdf')
                        ->label('PDF')
                        ->icon('heroicon-o-document-text')
                        ->url(fn (FinanceInvoice $record): string => route('pdf.print-finance-invoice', $record))
                        ->openUrlInNewTab(),

                    Action::make('export_purchase_invoice')
                        ->label('Export Purchase Invoice')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('info')
                        ->url(fn (FinanceInvoice $record): string => route('finance-purchase-invoice.export', $record->id))
                        ->openUrlInNewTab(),
                ])->button(),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('300s');
    }

    public function render()
    {
        return view('livewire.finance-invoice.generate-invoice-admin-portal-finance');
    }
}
