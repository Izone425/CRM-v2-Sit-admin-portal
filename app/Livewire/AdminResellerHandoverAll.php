<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ResellerHandover;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Livewire\Attributes\On;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class AdminResellerHandoverAll extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public $lastRefreshTime;
    public $showFilesModal = false;
    public $selectedHandover = null;
    public $handoverFiles = [];
    public $showRemarkModal = false;
    public $showAdminRemarkModal = false;

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

    #[On('refresh-leadowner-tables')]
    public function refreshData()
    {
        $this->resetTable();
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(ResellerHandover::query()->orderBy('created_at', 'desc'))
            ->columns([
                TextColumn::make('fb_id')
                    ->label('FB ID')
                    ->sortable()
                    ->action(
                        Action::make('view_files')
                            ->label('View Files')
                            ->action(fn (ResellerHandover $record) => $this->openFilesModal($record->id))
                    )
                    ->color('primary')
                    ->weight('bold')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereRaw("CONCAT('FB', DATE_FORMAT(created_at, '%y%m'), '-', LPAD((SELECT COUNT(*) FROM reseller_handovers AS rh2 WHERE YEAR(rh2.created_at) = YEAR(reseller_handovers.created_at) AND MONTH(rh2.created_at) = MONTH(reseller_handovers.created_at) AND rh2.id <= reseller_handovers.id), 4, '0')) LIKE ?", ["%{$search}%"]);
                    }),
                TextColumn::make('fc_id')
                    ->label('FC ID')
                    ->getStateUsing(fn (ResellerHandover $record) => $record->financeInvoice?->formatted_id)
                    ->placeholder('-')
                    ->color('success')
                    ->weight('bold')
                    ->url(fn (ResellerHandover $record) => $record->financeInvoice
                        ? route('pdf.print-finance-invoice', $record->financeInvoice->id)
                        : null
                    )
                    ->openUrlInNewTab()
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('financeInvoice', function ($q) use ($search) {
                            $q->whereRaw("CONCAT('FC', DATE_FORMAT(created_at, '%y%m'), '-', LPAD((SELECT COUNT(*) FROM finance_invoices AS fi2 WHERE fi2.created_at BETWEEN DATE_FORMAT(finance_invoices.created_at, '%Y-%m-01') AND LAST_DAY(finance_invoices.created_at) AND fi2.id <= finance_invoices.id), 4, '0')) LIKE ?", ["%{$search}%"]);
                        });
                    }),
                TextColumn::make('autocount_invoice_number')
                    ->label('Invoice')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('official_receipt_number')
                    ->label('OR Number')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('reseller_company_name')
                    ->label('Reseller Name')
                    ->wrap()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('subscriber_name')
                    ->label('Subscriber Name')
                    ->wrap()
                    ->searchable()
                    ->sortable(),
                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'primary' => 'new',
                        'warning' => 'pending_quotation_confirmation',
                        'info' => 'pending_timetec_invoice',
                        'danger' => 'pending_invoice_confirmation',
                        'success' => 'pending_timetec_license',
                        'warning' => 'pending_reseller_payment',
                        'info' => 'pending_timetec_finance',
                        'success' => 'completed',
                        'gray' => 'inactive',
                    ])
                    ->formatStateUsing(fn (string $state): string => $state === 'inactive' ? 'InActive' : str_replace('Timetec', 'TimeTec', ucwords(str_replace('_', ' ', $state)))),
                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Updated At')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'new' => 'New',
                        'pending_quotation_confirmation' => 'Pending Quotation Confirmation',
                        'pending_timetec_invoice' => 'Pending TimeTec Invoice',
                        'pending_invoice_confirmation' => 'Pending Invoice Confirmation',
                        'pending_timetec_license' => 'Pending TimeTec License',
                        'pending_reseller_payment' => 'Pending Reseller Payment',
                        'pending_timetec_finance' => 'Pending TimeTec Finance',
                        'completed' => 'Completed',
                        'inactive' => 'InActive',
                    ])
                    ->default(null),

                SelectFilter::make('official_receipt')
                    ->label('Official Receipt')
                    ->options([
                        'with' => 'With OR Number',
                        'without' => 'Without OR Number',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'with' => $query->whereNotNull('official_receipt_number')->where('official_receipt_number', '!=', ''),
                            'without' => $query->where(fn ($q) => $q->whereNull('official_receipt_number')->orWhere('official_receipt_number', '')),
                            default => $query,
                        };
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50, 100])
            ->emptyState(fn () => view('components.empty-state-question'));
    }

    public function openFilesModal($handoverId)
    {
        $this->selectedHandover = ResellerHandover::find($handoverId);

        if ($this->selectedHandover) {
            $this->handoverFiles = $this->selectedHandover->getCategorizedFilesForModal();

            $this->showFilesModal = true;
        }
    }

    public function closeFilesModal()
    {
        $this->showFilesModal = false;
        $this->selectedHandover = null;
        $this->handoverFiles = [];
    }

    public function render()
    {
        return view('livewire.admin-reseller-handover-all');
    }
}
