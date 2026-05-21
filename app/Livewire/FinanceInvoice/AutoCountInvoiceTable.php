<?php

namespace App\Livewire\FinanceInvoice;

use App\Models\CrmInvoiceDetail;
use App\Models\FinanceInvoice;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;
use Livewire\Attributes\On;

class AutoCountInvoiceTable extends Component implements HasTable, HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected $listeners = ['refresh-finance-invoice-tables' => '$refresh'];

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

    #[On('refresh-finance-invoice-tables')]
    public function refreshData()
    {
        $this->resetTable();
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
    }

    public function table(Table $table): Table
    {
        return $table
            ->emptyState(fn () => view('components.empty-state-question'))
            ->query(
                FinanceInvoice::query()
            )
            ->columns([
                TextColumn::make('formatted_id')
                    ->label('ID')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('id', $direction);
                    })
                    ->weight('bold')
                    ->color('primary')
                    ->url(fn (FinanceInvoice $record) => route('pdf.print-finance-invoice', $record->id))
                    ->openUrlInNewTab()
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereRaw("CONCAT('FC', DATE_FORMAT(created_at, '%y%m'), '-', LPAD((SELECT COUNT(*) FROM finance_invoices AS fi2 WHERE fi2.created_at BETWEEN DATE_FORMAT(finance_invoices.created_at, '%Y-%m-01') AND LAST_DAY(finance_invoices.created_at) AND fi2.id <= finance_invoices.id), 4, '0')) LIKE ?", ["%{$search}%"]);
                    }),

                TextColumn::make('created_at')
                    ->label('FC ID Date')
                    ->dateTime('d M Y')
                    ->sortable(),

                TextColumn::make('autocount_invoice_number')
                    ->label('A/C Invoice No')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('resellerHandover.fb_id')
                    ->label('FB ID')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('resellerHandover.status')
                    ->label('Reseller Status')
                    ->formatStateUsing(fn (?string $state): string => $state ? ucwords(str_replace('_', ' ', $state)) : '-')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('reseller_name')
                    ->label('Reseller Name')
                    ->searchable()
                    ->wrap()
                    ->sortable(),

                TextColumn::make('currency')
                    ->label('Currency')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('reseller_commission_amount')
                    ->label('Amount')
                    ->sortable()
                    ->extraCellAttributes(fn (FinanceInvoice $record) => [
                        'data-currency-rate' => $record->currency_rate ?? 1,
                    ])
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('timetec_invoice_number')
                    ->label('TT Invoice No')
                    ->searchable()
                    ->sortable()
                    ->url(function ($record) {
                        if (!$record->timetec_invoice_number) {
                            return null;
                        }

                        // Get the CrmInvoiceDetail record based on timetec_invoice_number
                        $crmInvoice = CrmInvoiceDetail::where('f_invoice_no', $record->timetec_invoice_number)->first();

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

                TextColumn::make('portal_type')
                    ->label('Handover Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'software' => 'Software Handover',
                        'hardware' => 'Hardware Handover',
                        'admin' => 'Admin Portal',
                        'reseller' => 'Reseller Portal',
                        'reseller_usd' => 'Reseller USD',
                        'headcount' => 'Headcount Handover',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'software' => 'success',
                        'hardware' => 'info',
                        'reseller_handover' => 'warning',
                        'reseller_usd' => 'warning',
                        'admin' => 'primary',
                        'reseller' => 'danger',
                        'headcount' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('overdue')
                    ->label('Overdue')
                    ->getStateUsing(function (FinanceInvoice $record) {
                        if (!$record->created_at) {
                            return null;
                        }
                        $days = (int) $record->created_at->startOfDay()->diffInDays(now()->startOfDay());
                        return $days > 0 ? '-' . $days . ' Days' : '0 Day';
                    })
                    ->formatStateUsing(function ($state) {
                        if (!$state) {
                            return '';
                        }
                        $color = $state === '0 Day' ? '#16a34a' : '#dc2626';
                        return new \Illuminate\Support\HtmlString('<span style="color:' . $color . ';font-weight:bold;">' . e($state) . '</span>');
                    })
                    ->html(),
            ])
            ->filters([
                SelectFilter::make('month')
                    ->label('Month')
                    ->options([
                        '1' => 'January',
                        '2' => 'February',
                        '3' => 'March',
                        '4' => 'April',
                        '5' => 'May',
                        '6' => 'June',
                        '7' => 'July',
                        '8' => 'August',
                        '9' => 'September',
                        '10' => 'October',
                        '11' => 'November',
                        '12' => 'December',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if ($data['value']) {
                            $query->whereMonth('created_at', '>=', $data['value']);
                        }
                    }),
                SelectFilter::make('year')
                    ->label('Year')
                    ->options(function () {
                        $years = FinanceInvoice::selectRaw('YEAR(created_at) as year')
                            ->distinct()
                            ->orderByDesc('year')
                            ->pluck('year', 'year')
                            ->toArray();
                        return !empty($years) ? $years : [now()->year => now()->year];
                    })
                    ->query(function (Builder $query, array $data) {
                        if ($data['value']) {
                            $query->whereYear('created_at', $data['value']);
                        }
                    }),
                SelectFilter::make('portal_type')
                    ->label('Handover Type')
                    ->multiple()
                    ->options([
                        'software' => 'Software Handover',
                        'hardware' => 'Hardware Handover',
                        'headcount' => 'Headcount Handover',
                        'reseller_usd' => 'Reseller USD',
                        'admin' => 'Admin Portal',
                        'reseller' => 'Reseller Portal',
                    ]),
                SelectFilter::make('handover_type_filter')
                    ->label('Handover Type Filter 2')
                    ->options([
                        'exclude_reseller' => 'Exclude Reseller Portal',
                        'all' => 'All Type',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if ($data['value'] === 'exclude_reseller') {
                            $query->where('portal_type', '!=', 'reseller');
                        }
                    }),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'new' => 'New',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->default('new'),
                SelectFilter::make('currency')
                    ->label('Currency')
                    ->options(fn () => FinanceInvoice::whereNotNull('currency')
                        ->distinct()
                        ->pluck('currency', 'currency')
                        ->toArray()
                    ),
                TernaryFilter::make('exclude_zero_amount')
                    ->label('Exclude Amount 0')
                    ->placeholder('All')
                    ->trueLabel('Exclude 0')
                    ->falseLabel('Only 0')
                    ->queries(
                        true: fn ($query) => $query->where('reseller_commission_amount', '!=', 0),
                        false: fn ($query) => $query->where('reseller_commission_amount', 0),
                        blank: fn ($query) => $query,
                    ),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('view_self_billed_invoice')
                        ->label('View Self Billed Invoice')
                        ->icon('heroicon-o-document-text')
                        ->url(fn (FinanceInvoice $record): string => route('pdf.print-finance-invoice', $record))
                        ->openUrlInNewTab(),

                    Action::make('export_purchase_invoice')
                        ->label('Export Purchase Invoice')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('info')
                        ->url(fn (FinanceInvoice $record): string => route('finance-purchase-invoice.export', $record->id))
                        ->openUrlInNewTab(),

                    Action::make('mark_completed')
                        ->label('Mark as Completed')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Mark as Completed')
                        ->modalDescription('Are you sure you want to mark this invoice as completed?')
                        ->modalSubmitActionLabel('Yes, mark as completed')
                        ->action(function (FinanceInvoice $record) {
                            $record->update(['status' => 'completed']);

                            Notification::make()
                                ->title('Invoice marked as completed')
                                ->success()
                                ->send();
                        })
                        ->visible(fn (FinanceInvoice $record): bool =>
                            !in_array($record->status, ['completed', 'cancelled'])
                        ),

                    Action::make('mark_cancelled')
                        ->label('Mark as Cancelled')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Mark as Cancelled')
                        ->modalDescription('Are you sure you want to mark this invoice as cancelled?')
                        ->modalSubmitActionLabel('Yes, mark as cancelled')
                        ->action(function (FinanceInvoice $record) {
                            $record->update(['status' => 'cancelled']);

                            Notification::make()
                                ->title('Invoice marked as cancelled')
                                ->success()
                                ->send();
                        })
                        ->visible(fn (FinanceInvoice $record): bool =>
                            !in_array($record->status, ['completed', 'cancelled'])
                        ),
                ])->button(),
            ])
            ->bulkActions([
                BulkAction::make('bulk_export_purchase_invoice')
                    ->label('Export Purchase Invoice')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records) {
                        $ids = $records->pluck('id')->implode(',');
                        $url = route('finance-purchase-invoice.export-batch', ['ids' => $ids]);
                        $this->js("window.open('{$url}', '_blank')");
                    }),
                BulkAction::make('bulk_mark_completed')
                    ->label('Mark as Completed')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading(function (Collection $records) {
                        $hasCompleted = $records->where('status', 'completed')->count() > 0;
                        if ($hasCompleted) {
                            return 'Cannot Mark as Completed';
                        }
                        return 'Mark Selected as Completed';
                    })
                    ->modalDescription(function (Collection $records) {
                        $hasCompleted = $records->where('status', 'completed')->count() > 0;
                        if ($hasCompleted) {
                            return new \Illuminate\Support\HtmlString('You have selected invoice(s) that are <strong style="color: red;">already Completed</strong>. Please deselect the completed invoice(s) and try again.');
                        }
                        return 'Are you sure you want to mark all selected invoices as completed?';
                    })
                    ->modalSubmitActionLabel(function (Collection $records) {
                        $hasCompleted = $records->where('status', 'completed')->count() > 0;
                        if ($hasCompleted) {
                            return 'Close';
                        }
                        return 'Yes, mark all as completed';
                    })
                    ->before(function (Collection $records, BulkAction $action) {
                        $hasCompleted = $records->where('status', 'completed')->count() > 0;

                        if ($hasCompleted) {
                            Notification::make()
                                ->title('Cannot mark as completed')
                                ->body('You have selected invoice(s) that are already Completed. Please deselect the completed invoice(s) and try again.')
                                ->danger()
                                ->persistent()
                                ->send();

                            $action->halt();
                        }
                    })
                    ->action(function (Collection $records) {
                        $count = $records->where('status', '!=', 'completed')->count();

                        if ($count > 0) {
                            FinanceInvoice::whereIn('id', $records->pluck('id'))
                                ->where('status', '!=', 'completed')
                                ->update(['status' => 'completed']);

                            Notification::make()
                                ->title($count . ' invoice(s) marked as completed')
                                ->success()
                                ->send();
                        }
                    })
                    ->deselectRecordsAfterCompletion(),

                BulkAction::make('bulk_mark_cancelled')
                    ->label('Mark as Cancelled')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading(function (Collection $records) {
                        $hasCancelled = $records->where('status', 'cancelled')->count() > 0;
                        $hasCompleted = $records->where('status', 'completed')->count() > 0;
                        if ($hasCancelled || $hasCompleted) {
                            return 'Cannot Mark as Cancelled';
                        }
                        return 'Mark Selected as Cancelled';
                    })
                    ->modalDescription(function (Collection $records) {
                        $hasCancelled = $records->where('status', 'cancelled')->count() > 0;
                        $hasCompleted = $records->where('status', 'completed')->count() > 0;
                        $messages = [];
                        if ($hasCancelled) {
                            $messages[] = 'You have selected invoice(s) that are <strong style="color: red;">already Cancelled</strong>. Please deselect the cancelled invoice(s) and try again.';
                        }
                        if ($hasCompleted) {
                            $messages[] = 'You have selected invoice(s) that are <strong style="color: red;">already Completed</strong>. Please deselect the completed invoice(s) and try again.';
                        }
                        if (!empty($messages)) {
                            return new \Illuminate\Support\HtmlString(implode('<br><br>', $messages));
                        }
                        return 'Are you sure you want to mark all selected invoices as cancelled?';
                    })
                    ->modalSubmitActionLabel(function (Collection $records) {
                        $hasCancelled = $records->where('status', 'cancelled')->count() > 0;
                        $hasCompleted = $records->where('status', 'completed')->count() > 0;
                        if ($hasCancelled || $hasCompleted) {
                            return 'Close';
                        }
                        return 'Yes, mark all as cancelled';
                    })
                    ->before(function (Collection $records, BulkAction $action) {
                        $hasCancelled = $records->where('status', 'cancelled')->count() > 0;
                        $hasCompleted = $records->where('status', 'completed')->count() > 0;

                        if ($hasCancelled) {
                            Notification::make()
                                ->title('Cannot mark as cancelled')
                                ->body('You have selected invoice(s) that are already Cancelled. Please deselect the cancelled invoice(s) and try again.')
                                ->danger()
                                ->persistent()
                                ->send();
                        }

                        if ($hasCompleted) {
                            Notification::make()
                                ->title('Cannot mark as cancelled')
                                ->body('You have selected invoice(s) that are already Completed. Please deselect the completed invoice(s) and try again.')
                                ->danger()
                                ->persistent()
                                ->send();
                        }

                        if ($hasCancelled || $hasCompleted) {
                            $action->halt();
                        }
                    })
                    ->action(function (Collection $records) {
                        $count = $records->where('status', '!=', 'cancelled')->count();

                        if ($count > 0) {
                            FinanceInvoice::whereIn('id', $records->pluck('id'))
                                ->where('status', '!=', 'cancelled')
                                ->update(['status' => 'cancelled']);

                            Notification::make()
                                ->title($count . ' invoice(s) marked as cancelled')
                                ->success()
                                ->send();
                        }
                    })
                    ->deselectRecordsAfterCompletion(),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('300s')
            ->recordClasses(function (FinanceInvoice $record) {
                return match ($record->status) {
                    'completed' => 'success',
                    'cancelled' => 'danger',
                    default => null,
                };
            });
    }

    public function calculateTotal(array $ids): array
    {
        if (empty($ids)) {
            return ['total' => 0, 'count' => 0];
        }

        $records = FinanceInvoice::whereIn('id', $ids)->get();
        $total = 0;

        foreach ($records as $record) {
            $amount = (float) ($record->reseller_commission_amount ?? 0);
            $rate = (float) ($record->currency_rate ?? 1);
            $total += $amount * $rate;
        }

        return ['total' => round($total, 2), 'count' => $records->count()];
    }

    public function render()
    {
        return view('livewire.finance-invoice.auto-count-invoice-table');
    }
}
