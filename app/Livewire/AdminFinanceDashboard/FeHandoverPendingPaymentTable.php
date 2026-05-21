<?php

namespace App\Livewire\AdminFinanceDashboard;

use Livewire\Component;
use App\Models\ResellerHandoverFe;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkAction;
use Filament\Notifications\Notification;
use Livewire\Attributes\On;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class FeHandoverPendingPaymentTable extends Component implements HasForms, HasTable
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
            ->query(ResellerHandoverFe::query()->where('status', 'pending_reseller_payment')->orderBy('created_at', 'desc'))
            ->columns([
                TextColumn::make('fe_id')
                    ->label('FE ID')
                    ->sortable()
                    ->action(
                        Action::make('view_files')
                            ->label('View Files')
                            ->action(fn (ResellerHandoverFe $record) => $this->openFilesModal($record->id))
                    )
                    ->color('primary')
                    ->weight('bold')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereRaw("CONCAT('FE', LPAD(MONTH(created_at), 2, '0'), '-', LPAD(id, 4, '0')) LIKE ?", ["%{$search}%"]);
                    }),
                TextColumn::make('autocount_invoice_number')
                    ->label('A/C Invoice')
                    ->searchable(),
                TextColumn::make('ap_document')
                    ->label('AP Document')
                    ->searchable()
                    ->placeholder('-')
                    ->weight('bold')
                    ->url(fn (ResellerHandoverFe $record): ?string => $record->ap_document_url)
                    ->openUrlInNewTab()
                    ->color(fn (ResellerHandoverFe $record): ?string => $record->ap_document_url ? 'success' : null),
                TextColumn::make('timetec_proforma_invoice')
                    ->label('TT Invoice No')
                    ->searchable()
                    ->placeholder('-')
                    ->url(fn (ResellerHandoverFe $record): ?string => $record->invoice_url)
                    ->openUrlInNewTab()
                    ->color(fn (ResellerHandoverFe $record): ?string => $record->invoice_url ? 'primary' : null)
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
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending_reseller_payment',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucwords(str_replace('_', ' ', $state)))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Last Modified')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
                TextColumn::make('overdue')
                    ->label('Overdue')
                    ->getStateUsing(function (ResellerHandoverFe $record) {
                        $today = now()->startOfDay();
                        $updatedAt = $record->updated_at->startOfDay();
                        $daysDiff = $today->diffInDays($updatedAt);

                        return $daysDiff == 0 ? '0 Day' : '-' . $daysDiff . ' Days';
                    })
                    ->color(function (ResellerHandoverFe $record) {
                        $today = now()->startOfDay();
                        $updatedAt = $record->updated_at->startOfDay();
                        $daysDiff = $today->diffInDays($updatedAt);

                        return $daysDiff == 0 ? 'success' : 'danger';
                    })
                    ->weight(function (ResellerHandoverFe $record) {
                        $today = now()->startOfDay();
                        $updatedAt = $record->updated_at->startOfDay();
                        $daysDiff = $today->diffInDays($updatedAt);

                        return $daysDiff == 0 ? 'normal' : 'bold';
                    })
                    ->sortable(query: function ($query, $direction) {
                        return $query->orderBy('updated_at', $direction === 'asc' ? 'desc' : 'asc');
                    }),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('export_purchase_invoice')
                        ->label('Export Purchase Invoice')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('info')
                        ->visible(fn (ResellerHandoverFe $record): bool => !empty($record->ap_document))
                        ->url(fn (ResellerHandoverFe $record): string => route('fe-purchase-invoice.export', $record->id))
                        ->openUrlInNewTab(),
                    // Action::make('complete_task')
                    //     ->label('Complete Task')
                    //     ->icon('heroicon-o-check-badge')
                    //     ->color('success')
                    //     ->requiresConfirmation()
                    //     ->modalHeading('Complete Task')
                    //     ->modalDescription('Are you sure you want to mark this task as completed?')
                    //     ->visible(fn (ResellerHandoverFe $record): bool =>
                    //         !($record->reseller_payment_completed ?? false) && auth()->user()->role_id !== 2
                    //     )
                    //     ->action(function (ResellerHandoverFe $record): void {
                    //         try {
                    //             $record->update([
                    //                 'reseller_payment_completed' => true,
                    //             ]);

                    //             Notification::make()
                    //                 ->title('Task Completed')
                    //                 ->success()
                    //                 ->body('Task has been marked as completed successfully.')
                    //                 ->send();

                    //             $this->resetTable();
                    //         } catch (\Exception $e) {
                    //             Log::error("Error marking FE task as completed for handover {$record->id}: " . $e->getMessage());

                    //             Notification::make()
                    //                 ->title('Error')
                    //                 ->danger()
                    //                 ->body('Failed to mark task as completed.')
                    //                 ->send();
                    //         }
                    //     }),
                ])->button(),
            ])
            ->bulkActions([
                BulkAction::make('batch_export_purchase_invoice')
                    ->label('Batch Export Purchase Invoice')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->modalHeading(false)
                    ->modalWidth('lg')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('docdate')
                            ->label('Purchase Invoice Date')
                            ->required()
                            ->native(false)
                            ->default(now()->format('Y-m-d'))
                            ->displayFormat('d F Y'),
                    ])
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records, array $data) {
                        $ids = $records->pluck('id')->implode(',');
                        $docdate = $data['docdate'];
                        $this->js("window.open('" . route('fe-purchase-invoice.batch-export') . "?ids=" . $ids . "&docdate=" . $docdate . "', '_blank')");
                    }),
                // BulkAction::make('batchCompleteTask')
                //     ->label('Batch Complete Task')
                //     ->icon('heroicon-o-check-badge')
                //     ->color('success')
                //     ->requiresConfirmation()
                //     ->modalHeading('Batch Complete Tasks')
                //     ->modalDescription(fn (Collection $records) => 'Are you sure you want to mark ' . $records->count() . ' task(s) as completed?')
                //     ->visible(fn (): bool => auth()->user()->role_id !== 2)
                //     ->deselectRecordsAfterCompletion()
                //     ->action(function (Collection $records): void {
                //         $completedCount = 0;

                //         foreach ($records as $record) {
                //             if ($record->reseller_payment_completed) {
                //                 continue;
                //             }

                //             try {
                //                 $record->update([
                //                     'reseller_payment_completed' => true,
                //                 ]);
                //                 $completedCount++;
                //             } catch (\Exception $e) {
                //                 Log::error("Error marking FE task as completed for handover {$record->id}: " . $e->getMessage());
                //             }
                //         }

                //         Notification::make()
                //             ->title($completedCount . ' task(s) marked as completed')
                //             ->success()
                //             ->send();

                //         $this->resetTable();
                //     }),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50, 100])
            ->recordClasses(fn (ResellerHandoverFe $record) =>
                (bool)($record->reseller_payment_completed) ? 'success' : null
            )
            ->emptyState(fn () => view('components.empty-state-question'));
    }

    public function openFilesModal($handoverId)
    {
        $this->selectedHandover = ResellerHandoverFe::find($handoverId);

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
        return view('livewire.admin-finance-dashboard.fe-handover-pending-payment-table');
    }
}
