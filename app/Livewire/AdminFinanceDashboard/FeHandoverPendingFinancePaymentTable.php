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
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Builder;

class FeHandoverPendingFinancePaymentTable extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public $lastRefreshTime;
    public $showFilesModal = false;
    public $selectedHandover = null;
    public $handoverFiles = [];
    public $showRemarkModal = false;
    public $showAdminRemarkModal = false;
    public $source = 'admin';

    public function mount($source = 'admin')
    {
        $this->source = $source;
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
            ->query(ResellerHandoverFe::query()->where('status', 'pending_finance_payment')->orderBy('created_at', 'desc'))
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
                        'warning' => 'pending_finance_payment',
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
            ->actions($this->source === 'finance' ? [
                ActionGroup::make([
                    Action::make('export_purchase_invoice')
                        ->label('Export Purchase Invoice')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('info')
                        ->visible(fn (ResellerHandoverFe $record): bool => !empty($record->ap_document))
                        ->url(fn (ResellerHandoverFe $record): string => route('fe-purchase-invoice.export', $record->id))
                        ->openUrlInNewTab(),
                    Action::make('upload_finance_payment_slip')
                        ->label('Upload Document')
                        ->icon('heroicon-o-arrow-up-tray')
                        ->color('primary')
                        ->modalHeading(false)
                        ->form([
                            \Filament\Forms\Components\Placeholder::make('handover_info')
                                ->label('')
                                ->content(function (ResellerHandoverFe $record): \Illuminate\Support\HtmlString {
                                    $html = "ID: {$record->fe_id}<br>RESELLER: {$record->reseller_company_name}<br>SUBSCRIBER: {$record->subscriber_name}";

                                    if ($record->ap_document) {
                                        $invoiceDetail = \Illuminate\Support\Facades\DB::connection('frontenddb')
                                            ->table('crm_invoice_details')
                                            ->where('f_invoice_no', $record->ap_document)
                                            ->first(['f_total_amount', 'f_currency']);

                                        if ($invoiceDetail) {
                                            $html .= "<br><div style='text-align:center; font-weight:bold; color:red; margin-top:16px; font-size:2em;'>{$record->autocount_invoice_number}</div>"
                                                . "<div style='text-align:center; font-weight:bold; margin-top:8px; font-size:1.5em;'>" . ($invoiceDetail->f_currency ?? 'MYR') . " " . number_format($invoiceDetail->f_total_amount, 2) . "</div>";
                                        }
                                    }

                                    return new \Illuminate\Support\HtmlString($html);
                                })
                                ->columnSpanFull(),
                            FileUpload::make('finance_payment_slip')
                                ->label('Finance Payment Slip')
                                ->required()
                                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'])
                                ->maxSize(10240)
                                ->disk('public')
                                ->directory('reseller-handover-fe/finance-payment-slips'),
                            FileUpload::make('self_billed_einvoice')
                                ->label('Self Billed E-Invoice')
                                ->required()
                                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'])
                                ->maxSize(10240)
                                ->disk('public')
                                ->directory('reseller-handover-fe/self-billed-einvoices'),
                        ])
                        ->modalWidth('md')
                        ->action(function (ResellerHandoverFe $record, array $data) {
                            $updateData = [
                                'finance_payment_slip' => $data['finance_payment_slip'] ?? null,
                                'finance_payment_slip_submitted_at' => now(),
                                'status' => 'completed',
                                'completed_at' => now(),
                            ];

                            if (!empty($data['self_billed_einvoice'])) {
                                $updateData['self_billed_einvoice'] = $data['self_billed_einvoice'];
                                $updateData['self_billed_einvoice_submitted_at'] = now();
                            }

                            $record->update($updateData);

                            if (\App\Mail\ResellerHandoverFeStatusUpdate::shouldSend($record->status)) {
                                try {
                                    \Illuminate\Support\Facades\Mail::send(new \App\Mail\ResellerHandoverFeStatusUpdate($record));
                                } catch (\Exception $e) {
                                    \Illuminate\Support\Facades\Log::error('Failed to send FE handover email', [
                                        'handover_id' => $record->id,
                                        'status' => 'completed',
                                        'error' => $e->getMessage()
                                    ]);
                                }
                            }

                            Notification::make()
                                ->title('Finance Payment Slip uploaded successfully')
                                ->success()
                                ->send();

                            $this->dispatch('refresh-leadowner-tables');
                        })
                        ->modalSubmitActionLabel('Upload'),
                ])->button(),
            ] : [])
            ->bulkActions($this->source === 'finance' ? [
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
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                        $ids = $records->pluck('id')->implode(',');
                        $docdate = $data['docdate'];
                        $this->js("window.open('" . route('fe-purchase-invoice.batch-export') . "?ids=" . $ids . "&docdate=" . $docdate . "', '_blank')");
                    }),
            ] : [])
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
        return view('livewire.admin-finance-dashboard.fe-handover-pending-finance-payment-table');
    }
}
