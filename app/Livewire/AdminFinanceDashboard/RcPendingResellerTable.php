<?php

namespace App\Livewire\AdminFinanceDashboard;

use Livewire\Component;
use App\Models\CrmInvoiceDetail;
use App\Models\ResellerCommissionHandover;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\SelectFilter;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;

class RcPendingResellerTable extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public $lastRefreshTime;
    public $showFilesModal = false;
    public $selectedHandover = null;

    public static function placeholder(array $params = [])
    {
        return view('components.rc-table-skeleton');
    }

    public function mount()
    {
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
    }

    public function openFilesModal($handoverId)
    {
        $this->selectedHandover = ResellerCommissionHandover::find($handoverId);
        $this->showFilesModal = true;
    }

    public function closeFilesModal()
    {
        $this->showFilesModal = false;
        $this->selectedHandover = null;
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
            ->query(
                ResellerCommissionHandover::query()
                    ->where('status', 'pending_reseller')
            )
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('fh_id')
                    ->label('FH ID')
                    ->state(fn ($record) => $record->fh_id)
                    ->color('primary')
                    ->weight('bold')
                    ->action(
                        Action::make('viewDetails')
                            ->action(fn (ResellerCommissionHandover $record) => $this->openFilesModal($record->id))
                    )
                    ->searchable(query: function ($query, string $search) {
                        $query->orWhereRaw("CONCAT('FH', DATE_FORMAT(created_at, '%y%m'), '-', LPAD(
                            (SELECT COUNT(*) FROM reseller_commission_handovers rc2
                             WHERE YEAR(rc2.created_at) = YEAR(reseller_commission_handovers.created_at)
                             AND MONTH(rc2.created_at) = MONTH(reseller_commission_handovers.created_at)
                             AND rc2.id <= reseller_commission_handovers.id), 4, '0')) LIKE ?", ['%' . $search . '%']);
                    }),

                TextColumn::make('ap_invoice_no')
                    ->label('AP Number')
                    ->sortable()
                    ->searchable()
                    ->url(fn ($record) => $record->ap_invoice_url, shouldOpenInNewTab: true)
                    ->color(fn ($record) => $record->ap_invoice_url ? 'success' : null)
                    ->weight('bold'),

                TextColumn::make('reseller_name')
                    ->label('Reseller Name')
                    ->placeholder('N/A')
                    ->searchable()
                    ->formatStateUsing(fn ($state) => strtoupper($state ?? ''))
                    ->wrap(),

                TextColumn::make('subscriber_name')
                    ->label('Subscriber Name')
                    ->placeholder('N/A')
                    ->searchable()
                    ->formatStateUsing(fn ($state) => strtoupper($state ?? ''))
                    ->wrap(),

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),

                TextColumn::make('overdue')
                    ->label('Overdue')
                    ->getStateUsing(function (ResellerCommissionHandover $record) {
                        if (!$record->created_at) return null;
                        $days = (int) $record->created_at->startOfDay()->diffInDays(now()->startOfDay());
                        return $days == 0 ? '0 Day' : '-' . $days . ' Days';
                    })
                    ->color(fn ($state) => $state === '0 Day' ? 'success' : 'danger')
                    ->weight(fn ($state) => $state === '0 Day' ? 'normal' : 'bold'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color('warning')
                    ->searchable()
                    ->formatStateUsing(fn () => 'Pending Reseller'),

                TextColumn::make('tt_invoice_no')
                    ->label('TTPI No')
                    ->sortable()
                    ->searchable()
                    ->url(fn ($record) => $record->tt_invoice_url, shouldOpenInNewTab: true)
                    ->color(fn ($record) => $record->tt_invoice_url ? 'primary' : null)
                    ->weight('bold')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('autocount_inv_no')
                    ->label('AutoCount Inv No')
                    ->placeholder('-')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('amount')
                    ->label('Amount')
                    ->money(fn ($record) => $record->currency ?? 'MYR')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('currency')
                    ->label('Currency')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('amount_type')
                    ->label('Amount Type')
                    ->options([
                        'zero_negative' => 'Zero + Negative Amount',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if ($data['value'] === null || $data['value'] === '') return $query;
                        return $query->where('amount', '<=', 0);
                    }),
            ])
            ->actions([])
            ->bulkActions([])
            ->emptyState(fn () => view('components.empty-state-question'))
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(100)
            ->recordClasses(function (ResellerCommissionHandover $record) {
                $ttInvoiceNo = $record->tt_invoice_no;
                if (!$ttInvoiceNo) return null;

                $count = CrmInvoiceDetail::query()
                    ->where('f_invoice_no', 'LIKE', 'AP%')
                    ->where('f_created_time', '>=', '2026-01-01')
                    ->whereRaw("TRIM(SUBSTRING(f_desc, LOCATE('TT', f_desc))) = ?", [$ttInvoiceNo])
                    ->count();

                return $count > 1 ? 'bg-red-100 dark:bg-red-900/20' : null;
            });
    }

    public function render()
    {
        return view('livewire.admin-finance-dashboard.rc-pending-reseller-table');
    }
}
