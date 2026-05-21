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
use Filament\Notifications\Notification;
use Livewire\Attributes\On;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Actions\ActionGroup;
use Illuminate\Database\Eloquent\Builder;

class AdminResellerHandoverPendingTimetecFinance extends Component implements HasForms, HasTable
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
            ->query(ResellerHandover::query()->where('status', 'pending_timetec_finance')->orderBy('created_at', 'desc'))
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
                    ->label('A/C Invoice')
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
                        'warning' => 'pending_timetec_finance',
                    ])
                    ->formatStateUsing(fn (string $state): string => str_replace('Timetec', 'TimeTec', ucwords(str_replace('_', ' ', $state))))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Last Modified')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
                TextColumn::make('overdue')
                    ->label('Overdue')
                    ->state(function (ResellerHandover $record) {
                        if (!$record->updated_at) {
                            return null;
                        }
                        $days = (int) $record->updated_at->startOfDay()->diffInDays(now()->startOfDay());
                        return $days > 0 ? '-' . $days . ' Days' : '0 Day';
                    })
                    ->formatStateUsing(function ($state) {
                        if (!$state) {
                            return '';
                        }
                        $color = $state === '0 Day' ? '#16a34a' : '#dc2626';
                        return new \Illuminate\Support\HtmlString('<span style="color:' . $color . ';font-weight:bold;">' . e($state) . '</span>');
                    })
                    ->html()
                    ->sortable(query: function ($query, $direction) {
                        return $query->orderBy('updated_at', $direction === 'asc' ? 'desc' : 'asc');
                    }),
            ])
            ->actions([
                ActionGroup::make([
                    // Action::make('export_purchase_invoice')
                    //     ->label('Export Purchase Invoice')
                    //     ->icon('heroicon-o-arrow-down-tray')
                    //     ->color('info')
                    //     ->url(fn (ResellerHandover $record): string => route('reseller-purchase-invoice.export', $record->id))
                    //     ->openUrlInNewTab(),

                    Action::make('upload_einvoice')
                        ->label('Upload Self Billed E-Invoice')
                        ->icon('heroicon-o-arrow-up-tray')
                        ->color('primary')
                        ->modalHeading(false)
                        ->form([
                            \Filament\Forms\Components\Placeholder::make('handover_info')
                                ->label('')
                                ->content(fn (ResellerHandover $record): \Illuminate\Support\HtmlString =>
                                    new \Illuminate\Support\HtmlString(
                                        "ID: {$record->fb_id}<br>RESELLER: {$record->reseller_company_name}<br>SUBSCRIBER: {$record->subscriber_name}"
                                        . ($record->financeInvoice?->autocount_invoice_number
                                            ? "<br><div style='text-align:center; font-weight:bold; color:red; margin-top:16px; font-size:2em;'>{$record->financeInvoice->autocount_invoice_number}</div>"
                                              . "<div style='text-align:center; font-weight:bold; margin-top:8px; font-size:1.5em;'>" . ($record->financeInvoice->currency ?? 'MYR') . " " . number_format($record->financeInvoice->reseller_commission_amount, 2) . "</div>"
                                            : '')
                                    )
                                )
                                ->columnSpanFull(),
                            FileUpload::make('self_billed_einvoice')
                                ->label('Self Billed E-Invoice')
                                ->required()
                                ->acceptedFileTypes(['application/pdf'])
                                ->maxSize(10240)
                                ->disk('public')
                                ->directory('reseller-handover/self-billed-einvoices')
                                ->helperText(fn (ResellerHandover $record) => 'File name must contain: ' . ($record->financeInvoice?->autocount_invoice_number ?? 'N/A'))
                                ->rules([
                                    fn (ResellerHandover $record) => function (string $attribute, $value, $fail) use ($record) {
                                        $invoiceNo = $record->financeInvoice?->autocount_invoice_number;
                                        if (!$invoiceNo) return;

                                        $fileName = is_object($value) ? $value->getClientOriginalName() : (is_array($value) ? collect($value)->first() : $value);
                                        if (is_object($fileName)) {
                                            $fileName = $fileName->getClientOriginalName();
                                        }
                                        $fileName = (string) $fileName;

                                        if (!str_contains($fileName, $invoiceNo)) {
                                            $fail("File name must contain the AutoCount invoice number: {$invoiceNo}");
                                        }
                                    },
                                ]),
                        ])
                        ->modalWidth('md')
                        ->action(function (ResellerHandover $record, array $data) {
                            $record->update([
                                'self_billed_einvoice' => $data['self_billed_einvoice'] ?? null,
                                'self_billed_einvoice_submitted_at' => now(),
                                'status' => 'completed',
                            ]);

                            // Mark the related finance invoice as completed
                            if ($record->financeInvoice) {
                                $record->financeInvoice->update(['status' => 'completed']);
                            }

                            // Send email notification
                            if (\App\Mail\ResellerHandoverStatusUpdate::shouldSend($record->status)) {
                                try {
                                    \Illuminate\Support\Facades\Mail::send(new \App\Mail\ResellerHandoverStatusUpdate($record));
                                } catch (\Exception $e) {
                                    \Illuminate\Support\Facades\Log::error('Failed to send reseller handover email', [
                                        'handover_id' => $record->id,
                                        'status' => 'completed',
                                        'error' => $e->getMessage()
                                    ]);
                                }
                            }

                            Notification::make()
                                ->title('Self Billed E-Invoice uploaded successfully')
                                ->success()
                                ->send();

                            $this->dispatch('refresh-leadowner-tables');
                        })
                        ->modalSubmitActionLabel('Upload'),
                ])->button(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50, 100])
            ->recordClasses(fn (ResellerHandover $record) =>
                (bool)($record->reseller_payment_completed) ? 'success' : null
            )
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
        return view('livewire.admin-reseller-handover-pending-timetec-finance');
    }
}
