<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ResellerHandoverFd;
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
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Grid;
use Filament\Forms\Set;
use Filament\Tables\Filters\SelectFilter;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class AdminResellerHandoverFdPendingTimetecAdmin extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public $lastRefreshTime;
    public $showFilesModal = false;
    public $selectedHandover = null;
    public $handoverFiles = [];
    public $showRemarkModal = false;
    public $showAdminRemarkModal = false;
    public $creditTermWarningShown = false;

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

    public function openFilesModal($recordId)
    {
        $this->selectedHandover = ResellerHandoverFd::find($recordId);
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

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ResellerHandoverFd::query()
                    ->whereIn('status', ['new', 'pending_timetec_invoice', 'pending_timetec_license'])
            )
            ->columns([
                TextColumn::make('fd_id')
                    ->label('FD ID')
                    ->sortable()
                    ->action(
                        Action::make('view_files')
                            ->label('View Files')
                            ->action(fn (ResellerHandoverFd $record) => $this->openFilesModal($record->id))
                    )
                    ->color('primary')
                    ->weight('bold'),
                TextColumn::make('reseller_company_name')
                    ->label('Reseller Name')
                    ->searchable()
                    ->wrap()
                    ->sortable(),
                TextColumn::make('subscriber_name')
                    ->label('Subscriber Name')
                    ->searchable()
                    ->wrap()
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
                    ->label('Last Modified')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->actions([
                Action::make('complete_new')
                    ->label('Complete Task')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (ResellerHandoverFd $record) => $record->status === 'new')
                    ->modalWidth('xl')
                    ->form([
                        \Filament\Forms\Components\Placeholder::make('handover_info')
                            ->label('')
                            ->content(fn (ResellerHandoverFd $record): \Illuminate\Support\HtmlString =>
                                new \Illuminate\Support\HtmlString(
                                    "ID: {$record->fd_id}<br>RESELLER: {$record->reseller_company_name}<br>SUBSCRIBER: {$record->subscriber_name}"
                                )
                            )
                            ->columnSpanFull(),
                        \Filament\Forms\Components\Placeholder::make('category_display')
                            ->label('')
                            ->content(fn (ResellerHandoverFd $record): \Illuminate\Support\HtmlString =>
                                new \Illuminate\Support\HtmlString(
                                    '<div style="background: #fee2e2; border: 1px solid #ef4444; border-radius: 8px; padding: 10px 14px; color: #991b1b; font-weight: 600; font-size: 0.875rem;">'
                                    . 'Category: ' . ($record->category === 'renewal_subscription' ? 'Renewal Subscription' : ($record->category === 'addon_headcount' ? 'AddOn Headcount' : 'N/A'))
                                    . '</div>'
                                )
                            )
                            ->columnSpanFull(),
                        TextInput::make('timetec_proforma_invoice')
                            ->label('TimeTec Proforma Invoice Number')
                            ->required()
                            ->minLength(12)
                            ->maxLength(12)
                            ->alphanum()
                            ->validationMessages([
                                'min' => 'The TimeTec Proforma Invoice Number must be exactly 12 characters.',
                                'max' => 'The TimeTec Proforma Invoice Number must be exactly 12 characters.',
                                'required' => 'The TimeTec Proforma Invoice Number field is required.',
                            ])
                            ->extraAlpineAttributes([
                                'x-on:input' => '
                                    const start = $el.selectionStart;
                                    const end = $el.selectionEnd;
                                    const value = $el.value;
                                    $el.value = value.toUpperCase();
                                    $el.setSelectionRange(start, end);
                                '
                            ])
                            ->dehydrateStateUsing(fn ($state) => strtoupper($state)),
                        Textarea::make('admin_reseller_remark')
                            ->label('Admin Reseller Remark')
                            ->rows(2)
                            ->maxLength(1000)
                            ->extraAlpineAttributes([
                                'x-on:input' => '
                                    const start = $el.selectionStart;
                                    const end = $el.selectionEnd;
                                    const value = $el.value;
                                    $el.value = value.toUpperCase();
                                    $el.setSelectionRange(start, end);
                                '
                            ])
                            ->dehydrateStateUsing(fn ($state) => strtoupper($state)),
                    ])
                    ->action(function (ResellerHandoverFd $record, array $data) {
                        $record->update([
                            'status' => 'pending_quotation_confirmation',
                            'timetec_proforma_invoice' => $data['timetec_proforma_invoice'] ?? null,
                            'ttpi_submitted_at' => now(),
                            'admin_reseller_remark' => $data['admin_reseller_remark'] ?? null,
                        ]);

                        if (\App\Mail\ResellerHandoverFdStatusUpdate::shouldSend($record->status)) {
                            try {
                                \Illuminate\Support\Facades\Mail::send(new \App\Mail\ResellerHandoverFdStatusUpdate($record));
                            } catch (\Exception $e) {
                                \Illuminate\Support\Facades\Log::error('Failed to send FD handover email', [
                                    'handover_id' => $record->id,
                                    'status' => 'pending_quotation_confirmation',
                                    'error' => $e->getMessage()
                                ]);
                            }
                        }

                        Notification::make()
                            ->title('Task completed successfully')
                            ->success()
                            ->send();

                        $this->dispatch('refresh-leadowner-tables');
                    })
                    ->modalHeading(false)
                    ->modalSubmitActionLabel('Complete'),

                // Action for 'pending_timetec_invoice' status
                Action::make('complete_invoice')
                    ->label('Complete Task')
                    ->modalHeading(false)
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (ResellerHandoverFd $record) => $record->status === 'pending_timetec_invoice')
                    ->fillForm(function (ResellerHandoverFd $record) {
                        $this->creditTermWarningShown = false;

                        $defaultOption = 'cash_term';
                        if ($record->reseller_id) {
                            $resellerV2 = \App\Models\ResellerV2::where('reseller_id', $record->reseller_id)->first();
                            if ($resellerV2 && $resellerV2->payment_type === 'credit_term') {
                                $defaultOption = 'cash_term_without_payment';
                            }
                        }

                        return [
                            'reseller_option' => $defaultOption,
                            'reseller_option_default' => $defaultOption,
                        ];
                    })
                    ->form([
                        \Filament\Forms\Components\Placeholder::make('handover_info')
                            ->label('')
                            ->content(fn (ResellerHandoverFd $record): \Illuminate\Support\HtmlString =>
                                new \Illuminate\Support\HtmlString(
                                    "ID: {$record->fd_id}<br>RESELLER: {$record->reseller_company_name}<br>SUBSCRIBER: {$record->subscriber_name}"
                                )
                            )
                            ->columnSpanFull(),
                        FileUpload::make('autocount_invoice')
                            ->label('AutoCount Invoice')
                            ->required()
                            ->multiple()
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->disk('public')
                            ->directory('reseller-handover-fd/autocount-invoices')
                            ->maxSize(10240)
                            ->afterStateUpdated(function ($state, Set $set) {
                                if (!$state) {
                                    return;
                                }

                                try {
                                    $ocrService = app(\App\Services\InvoiceOcrService::class);
                                    $filePaths = [];

                                    if (is_array($state)) {
                                        foreach ($state as $file) {
                                            if ($file instanceof TemporaryUploadedFile) {
                                                $filePaths[] = $file->getRealPath();
                                            }
                                        }
                                    } elseif ($state instanceof TemporaryUploadedFile) {
                                        $filePaths[] = $state->getRealPath();
                                    }

                                    if (!empty($filePaths)) {
                                        $invoiceNumber = $ocrService->extractInvoiceNumberFromMultipleFiles($filePaths);

                                        if ($invoiceNumber) {
                                            $set('autocount_invoice_number', $invoiceNumber);

                                            Notification::make()
                                                ->title('Invoice number detected')
                                                ->body("Found: {$invoiceNumber}")
                                                ->success()
                                                ->send();
                                        } else {
                                            Notification::make()
                                                ->title('No invoice number detected')
                                                ->body('Please enter manually')
                                                ->warning()
                                                ->send();
                                        }
                                    }
                                } catch (\Exception $e) {
                                    \Illuminate\Support\Facades\Log::error('OCR failed in ResellerHandoverFd', [
                                        'error' => $e->getMessage()
                                    ]);

                                    Notification::make()
                                        ->title('OCR scan failed')
                                        ->body('Please enter invoice number manually')
                                        ->warning()
                                        ->send();
                                }
                            })
                            ->live(),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('autocount_invoice_number')
                                    ->label('AutoCount Invoice Number')
                                    ->required()
                                    ->extraAlpineAttributes([
                                        'x-on:input' => '
                                            const start = $el.selectionStart;
                                            const end = $el.selectionEnd;
                                            const value = $el.value;
                                            $el.value = value.toUpperCase();
                                            $el.setSelectionRange(start, end);
                                        '
                                    ])
                                    ->dehydrateStateUsing(fn ($state) => strtoupper($state))
                                    ->maxLength(13),
                                \Filament\Forms\Components\Group::make([
                                    \Filament\Forms\Components\Placeholder::make('export_actions_label')
                                        ->label('')
                                        ->content(''),
                                    \Filament\Forms\Components\Actions::make([
                                        \Filament\Forms\Components\Actions\Action::make('export_renewal_sales')
                                            ->label('Export - Renewal Sales')
                                            ->icon('heroicon-o-arrow-down-tray')
                                            ->color('primary')
                                            ->url(function (ResellerHandoverFd $record) {
                                                $encryptedId = \App\Classes\Encryptor::encrypt($record->id);
                                                return route('reseller-invoice-data-fd.export-renewal', ['resellerHandoverFd' => $encryptedId]);
                                            })
                                            ->openUrlInNewTab()
                                            ->extraAttributes(['style' => 'min-width: 200px;']),
                                        \Filament\Forms\Components\Actions\Action::make('export_addon_sales')
                                            ->label('Export - AddOn Sales')
                                            ->icon('heroicon-o-arrow-down-tray')
                                            ->color('success')
                                            ->url(function (ResellerHandoverFd $record) {
                                                $encryptedId = \App\Classes\Encryptor::encrypt($record->id);
                                                return route('reseller-invoice-data-fd.export-addon', ['resellerHandoverFd' => $encryptedId]);
                                            })
                                            ->openUrlInNewTab()
                                            ->extraAttributes(['style' => 'min-width: 200px;']),
                                    ])
                                    ->alignLeft(),
                                ])
                                ->columnSpan(1),
                            ]),

                        \Filament\Forms\Components\Hidden::make('reseller_option_default'),

                        Grid::make(2)
                            ->schema([
                                Radio::make('reseller_option')
                                    ->label('Reseller Option')
                                    ->required()
                                    ->options([
                                        'cash_term' => 'Cash Term',
                                        'cash_term_without_payment' => new \Illuminate\Support\HtmlString('<span style="color: red; font-weight: bold;">Credit Term (Without Payment)</span>'),
                                    ])
                                    ->default('cash_term')
                                    ->live()
                                    ->columnSpan(1),

                                Grid::make(1)
                                    ->schema([
                                        \Filament\Forms\Components\Placeholder::make('category_display')
                                            ->label('')
                                            ->content(fn (ResellerHandoverFd $record): \Illuminate\Support\HtmlString =>
                                                new \Illuminate\Support\HtmlString(
                                                    '<div style="background: #fee2e2; border: 1px solid #ef4444; border-radius: 8px; padding: 10px 14px; color: #991b1b; font-weight: 600; font-size: 0.875rem;">'
                                                    . 'Category: ' . ($record->category === 'renewal_subscription' ? 'Renewal Subscription' : ($record->category === 'addon_headcount' ? 'AddOn Headcount' : 'N/A'))
                                                    . '</div>'
                                                )
                                            )
                                            ->columnSpan(1),

                                        \Filament\Forms\Components\Placeholder::make('reseller_option_warning')
                                            ->label('')
                                            ->content(fn (\Filament\Forms\Get $get): \Illuminate\Support\HtmlString =>
                                                new \Illuminate\Support\HtmlString(
                                                    '<div style="background: #fef3c7; border: 1px solid #f59e0b; border-radius: 8px; padding: 10px 14px; color: #92400e; font-weight: 600; font-size: 0.875rem;">'
                                                    . '⚠ Warning: Reseller status is ' . ($get('reseller_option_default') === 'cash_term' ? 'Cash Term' : 'Credit Term') . '.'
                                                    . '</div>'
                                                )
                                            )
                                            ->visible(fn (\Filament\Forms\Get $get) =>
                                                $get('reseller_option') !== null
                                                && $get('reseller_option_default') !== null
                                                && $get('reseller_option') !== $get('reseller_option_default')
                                            )
                                            ->columnSpan(1),
                                    ])->columnSpan(1)
                            ]),
                    ])
                    ->action(function (ResellerHandoverFd $record, array $data) {
                        // Check if reseller has bypass_invoice enabled
                        $bypassInvoice = false;
                        if ($record->reseller_id) {
                            $resellerV2 = \App\Models\ResellerV2::where('reseller_id', $record->reseller_id)->first();
                            $bypassInvoice = $resellerV2 && $resellerV2->bypass_invoice === 'yes';
                        }

                        if ($bypassInvoice) {
                            $newStatus = $data['reseller_option'] === 'cash_term'
                                ? 'pending_reseller_payment'
                                : 'pending_timetec_license';
                        } else {
                            $newStatus = 'pending_invoice_confirmation';
                        }

                        $record->update([
                            'autocount_invoice' => $data['autocount_invoice'],
                            'autocount_invoice_number' => $data['autocount_invoice_number'],
                            'aci_submitted_at' => now(),
                            'reseller_option' => $data['reseller_option'],
                            'status' => $newStatus,
                            'completed_at' => now(),
                        ]);

                        $this->creditTermWarningShown = false;

                        if (\App\Mail\ResellerHandoverFdStatusUpdate::shouldSend($record->status)) {
                            try {
                                \Illuminate\Support\Facades\Mail::send(new \App\Mail\ResellerHandoverFdStatusUpdate($record));
                            } catch (\Exception $e) {
                                \Illuminate\Support\Facades\Log::error('Failed to send FD handover email', [
                                    'handover_id' => $record->id,
                                    'status' => $newStatus,
                                    'error' => $e->getMessage()
                                ]);
                            }
                        }

                        Notification::make()
                            ->title('Task completed successfully')
                            ->body($bypassInvoice ? 'Bypass invoice enabled. Status set to Pending TimeTec License.' : null)
                            ->success()
                            ->send();

                        $this->dispatch('refresh-leadowner-tables');
                    })
                    ->modalButton('Complete')
                    ->modalWidth('2xl'),

                // Reject action for 'pending_timetec_invoice' status
                Action::make('reject_invoice')
                    ->label('Reject')
                    ->modalHeading('Reject & Revert to Pending Quotation Confirmation')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (ResellerHandoverFd $record) => $record->status === 'pending_timetec_invoice')
                    ->form([
                        \Filament\Forms\Components\Placeholder::make('handover_info')
                            ->label('')
                            ->content(fn (ResellerHandoverFd $record): \Illuminate\Support\HtmlString =>
                                new \Illuminate\Support\HtmlString(
                                    "ID: {$record->fd_id}<br>RESELLER: {$record->reseller_company_name}<br>SUBSCRIBER: {$record->subscriber_name}"
                                )
                            )
                            ->columnSpanFull(),
                        Textarea::make('reject_remark')
                            ->label('Reason for Rejection')
                            ->required()
                            ->rows(3)
                            ->maxLength(1000)
                            ->extraAlpineAttributes([
                                'x-on:input' => '
                                    const start = $el.selectionStart;
                                    const end = $el.selectionEnd;
                                    const value = $el.value;
                                    $el.value = value.toUpperCase();
                                    $el.setSelectionRange(start, end);
                                '
                            ])
                            ->dehydrateStateUsing(fn ($state) => strtoupper($state)),
                    ])
                    ->action(function (ResellerHandoverFd $record, array $data) {
                        // Delete purchase order files from storage
                        if ($record->purchase_order) {
                            $files = is_array($record->purchase_order)
                                ? $record->purchase_order
                                : (json_decode($record->purchase_order, true) ?? [$record->purchase_order]);

                            foreach ($files as $file) {
                                \Illuminate\Support\Facades\Storage::disk('public')->delete($file);
                            }
                        }

                        $record->update([
                            'status' => 'pending_quotation_confirmation',
                            'purchase_order' => null,
                            'admin_reseller_remark' => $data['reject_remark'],
                        ]);

                        // Send rejection email
                        try {
                            \Illuminate\Support\Facades\Mail::send(new \App\Mail\ResellerHandoverFdStatusUpdate($record, 'rejected', $data['reject_remark']));
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error('Failed to send FD handover rejection email', [
                                'handover_id' => $record->id,
                                'error' => $e->getMessage()
                            ]);
                        }

                        Notification::make()
                            ->title('Handover rejected')
                            ->body('Status reverted to Pending Quotation Confirmation. Purchase order removed.')
                            ->warning()
                            ->send();

                        $this->dispatch('refresh-leadowner-tables');
                    })
                    ->modalButton('Reject')
                    ->modalWidth('md')
                    ->requiresConfirmation()
                    ->modalDescription('Are you sure you want to reject this handover? The purchase order will be removed and the reseller will be notified.'),

                // Action for 'pending_timetec_license' status
                Action::make('complete_license')
                    ->label('Complete Task')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (ResellerHandoverFd $record) => $record->status === 'pending_timetec_license')
                    ->form([
                        \Filament\Forms\Components\Placeholder::make('handover_info')
                            ->label('')
                            ->content(fn (ResellerHandoverFd $record): \Illuminate\Support\HtmlString =>
                                new \Illuminate\Support\HtmlString(
                                    "ID: {$record->fd_id}<br>RESELLER: {$record->reseller_company_name}<br>SUBSCRIBER: {$record->subscriber_name}"
                                )
                            )
                            ->columnSpanFull(),

                        TextInput::make('official_receipt_number')
                            ->label('Official Receipt Number')
                            ->required()
                            ->maxLength(12)
                            ->alphanum()
                            ->extraAlpineAttributes([
                                'x-on:input' => '
                                    const start = $el.selectionStart;
                                    const end = $el.selectionEnd;
                                    const value = $el.value;
                                    $el.value = value.toUpperCase();
                                    $el.setSelectionRange(start, end);
                                '
                            ])
                            ->dehydrateStateUsing(fn ($state) => strtoupper($state)),
                    ])
                    ->action(function (ResellerHandoverFd $record, array $data) {
                        $newStatus = $record->reseller_option === 'cash_term'
                            ? 'completed'
                            : 'pending_reseller_payment';

                        $record->update([
                            'official_receipt_number' => $data['official_receipt_number'],
                            'status' => $newStatus,
                            'completed_at' => now(),
                        ]);

                        // Send email notification
                        if (\App\Mail\ResellerHandoverFdStatusUpdate::shouldSend($record->status)) {
                            try {
                                \Illuminate\Support\Facades\Mail::send(new \App\Mail\ResellerHandoverFdStatusUpdate($record));
                            } catch (\Exception $e) {
                                \Illuminate\Support\Facades\Log::error('Failed to send FD handover email', [
                                    'handover_id' => $record->id,
                                    'status' => $newStatus,
                                    'error' => $e->getMessage()
                                ]);
                            }
                        }

                        $statusMessage = $newStatus === 'completed'
                            ? 'Task completed successfully.'
                            : 'Task completed successfully. Status changed to pending reseller payment.';

                        Notification::make()
                            ->title('Task completed successfully')
                            ->body($statusMessage)
                            ->success()
                            ->send();

                        $this->dispatch('refresh-leadowner-tables');
                    })
                    ->modalHeading(false)
                    ->modalButton('Complete')
                    ->modalWidth('md'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'new' => 'New',
                        'pending_timetec_invoice' => 'Pending TimeTec Invoice',
                        'pending_timetec_license' => 'Pending TimeTec License',
                    ])
                    ->default(null),
            ])
            ->defaultSort('updated_at', 'desc')
            ->paginated([10, 25, 50, 100])
            ->emptyState(fn () => view('components.empty-state-question'));
    }

    public function render()
    {
        return view('livewire.admin-reseller-handover-fd-pending-timetec-admin');
    }
}
