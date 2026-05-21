<?php

namespace App\Livewire;

use App\Models\CrmInvoiceDetail;
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
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;
use Filament\Notifications\Notification;
use Filament\Tables\Filters\SelectFilter;
use Livewire\Attributes\On;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class FinancePendingInvoiceConfirmation extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public $lastRefreshTime;
    public $showFilesModal = false;
    public $selectedHandover = null;
    public $handoverFiles = [];

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
            ->query(ResellerHandover::query()->where('status', 'pending_invoice_confirmation')->orderBy('created_at', 'desc'))
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
                TextColumn::make('financeInvoice.autocount_invoice_number')
                    ->label('A/C Invoice')
                    ->placeholder('-')
                    ->searchable(),

                TextColumn::make('financeInvoice.timetec_invoice_number')
                    ->label('TT Invoice No')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable()
                    ->url(function ($record) {
                        $ttInvoice = $record->financeInvoice?->timetec_invoice_number;
                        if (!$ttInvoice) {
                            return null;
                        }

                        $crmInvoice = CrmInvoiceDetail::where('f_invoice_no', $ttInvoice)->first();

                        if (!$crmInvoice) {
                            return null;
                        }

                        $aesKey = 'Epicamera@99';
                        try {
                            $encrypted = openssl_encrypt($crmInvoice->f_id, "AES-128-ECB", $aesKey);
                            $encryptedBase64 = base64_encode($encrypted);
                            return 'https://www.timeteccloud.com/paypal_reseller_invoice?iIn=' . $encryptedBase64;
                        } catch (\Exception $e) {
                            return null;
                        }
                    })
                    ->openUrlInNewTab()
                    ->color('primary')
                    ->weight('bold')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('reseller_company_name')
                    ->label('Reseller Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('subscriber_name')
                    ->label('Subscriber Name')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending_invoice_confirmation',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucwords(str_replace('_', ' ', $state)))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('overdue')
                    ->label('Overdue')
                    ->getStateUsing(function (ResellerHandover $record) {
                        $today = now()->startOfDay();
                        $updatedAt = $record->updated_at->startOfDay();
                        $daysDiff = $today->diffInDays($updatedAt);

                        if ($daysDiff == 0) {
                            return '0 Day';
                        } else {
                            return '-' . $daysDiff . ' Days';
                        }
                    })
                    ->color(function (ResellerHandover $record) {
                        $today = now()->startOfDay();
                        $updatedAt = $record->updated_at->startOfDay();
                        $daysDiff = $today->diffInDays($updatedAt);

                        return $daysDiff == 0 ? 'success' : 'danger';
                    })
                    ->weight(function (ResellerHandover $record) {
                        $today = now()->startOfDay();
                        $updatedAt = $record->updated_at->startOfDay();
                        $daysDiff = $today->diffInDays($updatedAt);

                        return $daysDiff == 0 ? 'normal' : 'bold';
                    })
                    ->sortable(query: function ($query, $direction) {
                        return $query->orderBy('updated_at', $direction === 'asc' ? 'desc' : 'asc');
                    }),
                TextColumn::make('updated_at')
                    ->label('Last Modified')
                    ->dateTime('d M Y, H:i')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('finance_invoice_status')
                    ->label('FC Status')
                    ->options([
                        'new' => 'New',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->multiple()
                    ->default(['new'])
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['values'])) {
                            return $query;
                        }

                        return $query->whereHas('financeInvoice', function ($q) use ($data) {
                            $q->whereIn('status', $data['values']);
                        });
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50, 100])
            ->recordClasses(fn (ResellerHandover $record) =>
                $record->financeInvoice?->status === 'completed' ? 'success' : null
            )
            ->actions([
                // ActionGroup::make([
                //     Action::make('export_purchase_invoice')
                //         ->label('Export Purchase Invoice')
                //         ->icon('heroicon-o-arrow-down-tray')
                //         ->color('info')
                //         ->url(fn (ResellerHandover $record): string => route('reseller-purchase-invoice.export', $record->id))
                //         ->openUrlInNewTab(),

                //     Action::make('mark_completed')
                //         ->label('Mark as Completed')
                //         ->icon('heroicon-o-check-badge')
                //         ->color('success')
                //         ->requiresConfirmation()
                //         ->modalHeading('Mark as Completed')
                //         ->modalDescription('Are you sure you want to mark this invoice as completed?')
                //         ->visible(fn (ResellerHandover $record): bool =>
                //             $record->financeInvoice?->status !== 'completed'
                //         )
                //         ->action(function (ResellerHandover $record): void {
                //             try {
                //                 $financeInvoice = $record->financeInvoice;

                //                 if ($financeInvoice) {
                //                     $financeInvoice->update(['status' => 'completed']);
                //                 }

                //                 Notification::make()
                //                     ->title('Completed')
                //                     ->success()
                //                     ->body('FC has been marked as completed.')
                //                     ->send();

                //                 $this->resetTable();
                //                 $this->dispatch('refresh-leadowner-tables');
                //             } catch (\Exception $e) {
                //                 Log::error("Error marking invoice as completed for handover {$record->id}: " . $e->getMessage());

                //                 Notification::make()
                //                     ->title('Error')
                //                     ->danger()
                //                     ->body('Failed to mark as completed.')
                //                     ->send();
                //             }
                //         }),
                //     Action::make('mark_cancelled')
                //         ->label('Mark as Cancelled')
                //         ->icon('heroicon-o-x-circle')
                //         ->color('danger')
                //         ->requiresConfirmation()
                //         ->modalHeading('Mark as Cancelled')
                //         ->modalDescription('Are you sure you want to cancel this invoice? This action cannot be undone.')
                //         ->visible(fn (ResellerHandover $record): bool =>
                //             $record->financeInvoice && !in_array($record->financeInvoice->status, ['completed', 'cancelled'])
                //         )
                //         ->action(function (ResellerHandover $record): void {
                //             try {
                //                 $financeInvoice = $record->financeInvoice;

                //                 if ($financeInvoice) {
                //                     $financeInvoice->update(['status' => 'cancelled']);
                //                 }

                //                 Notification::make()
                //                     ->title('Cancelled')
                //                     ->success()
                //                     ->body('Invoice has been marked as cancelled.')
                //                     ->send();

                //                 $this->resetTable();
                //             } catch (\Exception $e) {
                //                 Log::error("Error marking invoice as cancelled for handover {$record->id}: " . $e->getMessage());

                //                 Notification::make()
                //                     ->title('Error')
                //                     ->danger()
                //                     ->body('Failed to mark as cancelled.')
                //                     ->send();
                //             }
                //         }),
                // ])->button(),
            ])
            // ->bulkActions([
            //     BulkAction::make('bulk_mark_completed')
            //         ->label('Mark as Completed')
            //         ->icon('heroicon-o-check-badge')
            //         ->color('success')
            //         ->requiresConfirmation()
            //         ->modalHeading('Mark Selected as Completed')
            //         ->modalDescription('Are you sure you want to mark all selected invoices as completed?')
            //         ->action(function (Collection $records): void {
            //             $completedCount = 0;
            //             $skippedCount = 0;

            //             foreach ($records as $record) {
            //                 $financeInvoice = $record->financeInvoice;

            //                 if ($financeInvoice && $financeInvoice->status !== 'completed') {
            //                     $financeInvoice->update(['status' => 'completed']);
            //                     $completedCount++;
            //                 } else {
            //                     $skippedCount++;
            //                 }
            //             }

            //             $message = "{$completedCount} invoice(s) marked as completed.";
            //             if ($skippedCount > 0) {
            //                 $message .= " {$skippedCount} already completed or no FC found.";
            //             }

            //             Notification::make()
            //                 ->title('Batch Completed')
            //                 ->success()
            //                 ->body($message)
            //                 ->send();

            //             $this->resetTable();
            //             $this->dispatch('refresh-leadowner-tables');
            //         }),

            //     BulkAction::make('bulk_mark_cancelled')
            //         ->label('Mark as Cancelled')
            //         ->icon('heroicon-o-x-circle')
            //         ->color('danger')
            //         ->requiresConfirmation()
            //         ->modalHeading('Mark Selected as Cancelled')
            //         ->modalDescription('Are you sure you want to cancel all selected invoices? This action cannot be undone.')
            //         ->action(function (Collection $records): void {
            //             $cancelledCount = 0;
            //             $skippedCount = 0;

            //             foreach ($records as $record) {
            //                 $financeInvoice = $record->financeInvoice;

            //                 if ($financeInvoice && !in_array($financeInvoice->status, ['completed', 'cancelled'])) {
            //                     $financeInvoice->update(['status' => 'cancelled']);
            //                     $cancelledCount++;
            //                 } else {
            //                     $skippedCount++;
            //                 }
            //             }

            //             $message = "{$cancelledCount} invoice(s) marked as cancelled.";
            //             if ($skippedCount > 0) {
            //                 $message .= " {$skippedCount} skipped (already completed/cancelled or no FC found).";
            //             }

            //             Notification::make()
            //                 ->title('Batch Cancelled')
            //                 ->success()
            //                 ->body($message)
            //                 ->send();

            //             $this->resetTable();
            //             $this->dispatch('refresh-leadowner-tables');
            //         }),
            // ])
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
        return view('livewire.finance-pending-invoice-confirmation');
    }
}
