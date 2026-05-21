<?php

namespace App\Livewire\AdminFinanceDashboard;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\CrmInvoiceDetail;
use App\Models\ResellerCommissionHandover;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Livewire\Attributes\On;
use App\Mail\ResellerCommissionStatusUpdate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Collection;

class RcPendingFinanceTable extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;
    use WithFileUploads;

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
                    ->where('status', 'pending_finance')
            )
            ->defaultSort('reseller_proceeded_at', 'desc')
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
                    ->color('info')
                    ->searchable()
                    ->formatStateUsing(fn () => 'Pending Finance'),

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

                TextColumn::make('reseller_proceeded_at')
                    ->label('Reseller Proceeded')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('amount_type')
                    ->label('Amount Type')
                    ->options([
                        'zero_negative' => 'Zero + Negative Amount',
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data): \Illuminate\Database\Eloquent\Builder {
                        if ($data['value'] === null || $data['value'] === '') return $query;
                        return $query->where('amount', '<=', 0);
                    }),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('export_purchase_invoice')
                        ->label('Export Purchase Invoice')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('info')
                        ->visible(fn (ResellerCommissionHandover $record): bool => !empty($record->ap_invoice_no))
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
                        ->action(function (ResellerCommissionHandover $record, array $data) {
                            $docdate = $data['docdate'];
                            $this->js("window.open('" . route('rc-purchase-invoice.export', $record->id) . "?docdate=" . $docdate . "', '_blank')");
                        }),
                    Action::make('upload_payment')
                        ->label('Upload Document')
                        ->modalHeading((false))
                        ->icon('heroicon-o-arrow-up-tray')
                        ->color('primary')
                        ->form([
                            \Filament\Forms\Components\Placeholder::make('handover_info')
                                ->label('')
                                ->content(function (ResellerCommissionHandover $record): \Illuminate\Support\HtmlString {
                                    $html = "ID: {$record->fh_id}<br>RESELLER: {$record->reseller_name}<br>SUBSCRIBER: {$record->subscriber_name}";

                                    if ($record->ap_invoice_no) {
                                        $invoiceDetail = \Illuminate\Support\Facades\DB::connection('frontenddb')
                                            ->table('crm_invoice_details')
                                            ->where('f_invoice_no', $record->ap_invoice_no)
                                            ->first(['f_total_amount', 'f_currency']);

                                        if ($invoiceDetail) {
                                            $html .= "<br><div style='text-align:center; font-weight:bold; color:red; margin-top:16px; font-size:2em;'>{$record->autocount_inv_no}</div>"
                                                . "<div style='text-align:center; font-weight:bold; margin-top:8px; font-size:1.5em;'>" . ($invoiceDetail->f_currency ?? 'MYR') . " " . number_format($invoiceDetail->f_total_amount, 2) . "</div>";
                                        }
                                    }

                                    return new \Illuminate\Support\HtmlString($html);
                                })
                                ->columnSpanFull(),
                            FileUpload::make('payment_slip')
                                ->label('Payment Slip')
                                ->required()
                                ->directory('reseller-commission-payments')
                                ->acceptedFileTypes(['image/*', 'application/pdf'])
                                ->maxSize(5120),
                            FileUpload::make('self_billed_einvoice')
                                ->label('Self-Billed E-Invoice')
                                ->required()
                                ->directory('reseller-commission-self-billed-einvoices')
                                ->acceptedFileTypes(['image/*', 'application/pdf'])
                                ->maxSize(5120),
                        ])
                        ->modalWidth('lg')
                        ->action(function (ResellerCommissionHandover $record, array $data) {
                            $record->update([
                                'payment_slip' => $data['payment_slip'],
                                'payment_slip_uploaded_at' => now(),
                                'self_billed_einvoice' => $data['self_billed_einvoice'],
                                'self_billed_einvoice_uploaded_at' => now(),
                                'status' => 'completed',
                                'completed_at' => now(),
                            ]);

                            // Send completed email notification to reseller
                            try {
                                $record->refresh();
                                if (ResellerCommissionStatusUpdate::shouldSend($record->status)) {
                                    Mail::send(new ResellerCommissionStatusUpdate($record));
                                }
                            } catch (\Exception $e) {
                                Log::error('Failed to send commission completed email', [
                                    'handover_id' => $record->id,
                                    'error' => $e->getMessage(),
                                ]);
                            }

                            Notification::make()
                                ->title('Payment uploaded & completed')
                                ->body('PI No ' . $record->ap_invoice_no . ' has been marked as completed.')
                                ->success()
                                ->send();

                            $this->resetTable();
                        }),
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
                        $this->js("window.open('" . route('rc-purchase-invoice.batch-export') . "?ids=" . $ids . "&docdate=" . $docdate . "', '_blank')");
                    }),
            ])
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
        return view('livewire.admin-finance-dashboard.rc-pending-finance-table');
    }
}
