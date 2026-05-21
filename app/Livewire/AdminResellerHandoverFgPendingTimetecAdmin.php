<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ResellerHandoverFg;
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
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use App\Services\InvoiceOcrService;
use App\Models\FinanceInvoice;
use Illuminate\Support\Facades\Mail;

class AdminResellerHandoverFgPendingTimetecAdmin extends Component implements HasForms, HasTable
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
        $this->selectedHandover = ResellerHandoverFg::find($recordId);
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
                ResellerHandoverFg::query()
                    ->whereIn('status', ['new', 'pending_timetec_invoice', 'pending_timetec_license'])
            )
            ->columns([
                TextColumn::make('fg_id')
                    ->label('FG ID')
                    ->sortable()
                    ->action(
                        Action::make('view_files')
                            ->label('View Files')
                            ->action(fn (ResellerHandoverFg $record) => $this->openFilesModal($record->id))
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
                        'info' => fn (string $state): bool => in_array($state, ['pending_timetec_invoice', 'pending_timetec_license']),
                        'danger' => 'pending_reseller_payment',
                        'success' => 'completed',
                        'gray' => 'inactive',
                    ])
                    ->formatStateUsing(fn (string $state): string => $state === 'inactive' ? 'InActive' : str_replace('Timetec', 'TimeTec', ucwords(str_replace('_', ' ', $state)))),
                TextColumn::make('updated_at')
                    ->label('Last Modified')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->actions([
                // Action for new → pending_quotation_confirmation
                Action::make('complete_new')
                    ->label('Complete Task')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (ResellerHandoverFg $record) => $record->status === 'new')
                    ->modalWidth('xl')
                    ->form([
                        \Filament\Forms\Components\Placeholder::make('handover_info')
                            ->label('')
                            ->content(fn (ResellerHandoverFg $record): \Illuminate\Support\HtmlString =>
                                new \Illuminate\Support\HtmlString(
                                    "ID: {$record->fg_id}<br>RESELLER: {$record->reseller_company_name}<br>SUBSCRIBER: {$record->subscriber_name}"
                                )
                            )
                            ->columnSpanFull(),
                        \Filament\Forms\Components\Placeholder::make('category_display')
                            ->label('')
                            ->content(fn (ResellerHandoverFg $record): \Illuminate\Support\HtmlString =>
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
                    ->action(function (ResellerHandoverFg $record, array $data) {
                        $record->update([
                            'status' => 'pending_quotation_confirmation',
                            'timetec_proforma_invoice' => $data['timetec_proforma_invoice'] ?? null,
                            'ttpi_submitted_at' => now(),
                            'admin_reseller_remark' => $data['admin_reseller_remark'] ?? null,
                        ]);

                        // Send email notification
                        if (\App\Mail\ResellerHandoverFgStatusUpdate::shouldSend($record->status)) {
                            try {
                                \Illuminate\Support\Facades\Mail::send(new \App\Mail\ResellerHandoverFgStatusUpdate($record));
                            } catch (\Exception $e) {
                                \Illuminate\Support\Facades\Log::error('Failed to send FG handover email', [
                                    'handover_id' => $record->id,
                                    'status' => $record->status,
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
                    ->visible(fn (ResellerHandoverFg $record) => $record->status === 'pending_timetec_invoice')
                    ->fillForm(function (ResellerHandoverFg $record) {
                        // Reset the credit term warning flag when modal opens
                        $this->creditTermWarningShown = false;

                        $financeInvoice = \App\Models\FinanceInvoice::where('handover_id', $record->id)
                            ->where('portal_type', 'reseller_usd')
                            ->where('subscriber_name', $record->subscriber_name)
                            ->latest()
                            ->first();

                        // Default reseller_option based on reseller_v2 payment_type
                        $defaultOption = 'cash_term';
                        if ($record->reseller_id) {
                            $resellerV2 = \App\Models\ResellerV2::where('reseller_id', $record->reseller_id)->first();
                            if ($resellerV2 && $resellerV2->payment_type === 'credit_term') {
                                $defaultOption = 'cash_term_without_payment';
                            }
                        }

                        $formData = [
                            'reseller_option' => $defaultOption,
                            'reseller_option_default' => $defaultOption,
                            'reseller_invoice' => [],
                        ];

                        if ($financeInvoice) {
                            $invoiceFilename = 'FI_' . $financeInvoice->fc_number . '_' .
                                \Illuminate\Support\Str::upper(\Illuminate\Support\Str::replace('-', '_', \Illuminate\Support\Str::slug($financeInvoice->reseller_name))) . '.pdf';

                            $filePath = 'finance-invoices/' . $invoiceFilename;
                            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($filePath)) {
                                $formData['reseller_invoice'] = [$filePath];
                            }
                        }

                        return $formData;
                    })
                    ->form([
                        \Filament\Forms\Components\Placeholder::make('handover_info')
                            ->label('')
                            ->content(fn (ResellerHandoverFg $record): \Illuminate\Support\HtmlString =>
                                new \Illuminate\Support\HtmlString(
                                    "ID: {$record->fg_id}<br>RESELLER: {$record->reseller_company_name}<br>SUBSCRIBER: {$record->subscriber_name}"
                                )
                            )
                            ->columnSpanFull(),
                        Grid::make(2)
                            ->schema([
                                FileUpload::make('autocount_invoice')
                                    ->label('AutoCount Invoice')
                                    ->required()
                                    ->multiple()
                                    ->acceptedFileTypes(['application/pdf', 'image/*'])
                                    ->disk('public')
                                    ->directory('reseller-handover/autocount-invoices')
                                    ->maxSize(10240)
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        if (!$state) {
                                            return;
                                        }

                                        try {
                                            $ocrService = app(InvoiceOcrService::class);
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
                                            \Illuminate\Support\Facades\Log::error('OCR failed in ResellerHandoverFg', [
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
                                FileUpload::make('reseller_invoice')
                                    ->label('Self Billed Invoice')
                                    ->required()
                                    ->disabled()
                                    ->dehydrated(true)
                                    ->multiple()
                                    ->acceptedFileTypes(['application/pdf', 'image/*'])
                                    ->disk('public')
                                    ->openable()
                                    ->directory('reseller-handover/reseller-invoices')
                                    ->maxSize(10240),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('autocount_invoice_number')
                                    ->label('AutoCount Invoice Number')
                                    ->disabled()
                                    ->dehydrated(),
                                \Filament\Forms\Components\Group::make([
                                    \Filament\Forms\Components\Placeholder::make('export_actions_label')
                                        ->label('')
                                        ->content(''),
                                    \Filament\Forms\Components\Actions::make([
                                        \Filament\Forms\Components\Actions\Action::make('export_renewal_sales')
                                            ->label('Export - Renewal Sales')
                                            ->icon('heroicon-o-arrow-down-tray')
                                            ->color('primary')
                                            ->url(function (ResellerHandoverFg $record) {
                                                $encryptedId = \App\Classes\Encryptor::encrypt($record->id);
                                                return route('reseller-invoice-data.export-renewal', ['resellerHandover' => $encryptedId]);
                                            })
                                            ->openUrlInNewTab()
                                            ->extraAttributes(['style' => 'min-width: 200px;']),
                                        \Filament\Forms\Components\Actions\Action::make('export_addon_sales')
                                            ->label('Export - AddOn Sales')
                                            ->icon('heroicon-o-arrow-down-tray')
                                            ->color('success')
                                            ->url(function (ResellerHandoverFg $record) {
                                                $encryptedId = \App\Classes\Encryptor::encrypt($record->id);
                                                return route('reseller-invoice-data.export-addon', ['resellerHandover' => $encryptedId]);
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
                                        // 'cash_term' => 'Cash Term',
                                        'cash_term_without_payment' => new \Illuminate\Support\HtmlString('<span style="color: red; font-weight: bold;">Credit Term (Without Payment)</span>'),
                                    ])
                                    ->default('cash_term')
                                    ->live()
                                    ->columnSpan(1),


                                Grid::make(1)
                                    ->schema([
                                        \Filament\Forms\Components\Placeholder::make('category_display')
                                            ->label('')
                                            ->content(fn (ResellerHandoverFg $record): \Illuminate\Support\HtmlString =>
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
                    ->action(function (ResellerHandoverFg $record, array $data) {
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
                            'reseller_invoice' => $data['reseller_invoice'],
                            'autocount_invoice_number' => $data['autocount_invoice_number'],
                            'aci_submitted_at' => now(),
                            'reseller_option' => $data['reseller_option'],
                            'status' => $newStatus,
                            'completed_at' => now(),
                        ]);

                        // Reset the credit term warning flag
                        $this->creditTermWarningShown = false;

                        // Send email notification
                        if (\App\Mail\ResellerHandoverFgStatusUpdate::shouldSend($record->status)) {
                            try {
                                \Illuminate\Support\Facades\Mail::send(new \App\Mail\ResellerHandoverFgStatusUpdate($record));
                            } catch (\Exception $e) {
                                \Illuminate\Support\Facades\Log::error('Failed to send reseller handover email', [
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

                // Action for pending_timetec_license → pending_reseller_payment
                Action::make('complete_license')
                    ->label('Complete License')
                    ->icon('heroicon-o-key')
                    ->color('info')
                    ->visible(fn (ResellerHandoverFg $record) => $record->status === 'pending_timetec_license')
                    ->modalWidth('xl')
                    ->form([
                        \Filament\Forms\Components\Placeholder::make('handover_info')
                            ->label('')
                            ->content(fn (ResellerHandoverFg $record): \Illuminate\Support\HtmlString =>
                                new \Illuminate\Support\HtmlString(
                                    "ID: {$record->fg_id}<br>RESELLER: {$record->reseller_company_name}<br>SUBSCRIBER: {$record->subscriber_name}"
                                )
                            )
                            ->columnSpanFull(),
                        TextInput::make('official_receipt_number')
                            ->label('Official Receipt Number')
                            ->required()
                            ->maxLength(12)
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
                    ->action(function (ResellerHandoverFg $record, array $data) {
                        $newStatus = $record->reseller_option === 'cash_term'
                            ? 'pending_timetec_finance'
                            : 'pending_reseller_payment';

                        $record->update([
                            'official_receipt_number' => $data['official_receipt_number'],
                            'status' => $newStatus,
                            'completed_at' => now(),
                        ]);

                        // Send email notification
                        if (\App\Mail\ResellerHandoverFgStatusUpdate::shouldSend($record->status)) {
                            try {
                                \Illuminate\Support\Facades\Mail::send(new \App\Mail\ResellerHandoverFgStatusUpdate($record));
                            } catch (\Exception $e) {
                                \Illuminate\Support\Facades\Log::error('Failed to send FG handover email', [
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
                    ->modalSubmitActionLabel('Complete'),
            ])
            ->headerActions([
                Action::make('generate_invoice')
                    ->label('Generate Invoice')
                    ->icon('heroicon-o-plus')
                    ->form([
                        \Filament\Forms\Components\Select::make('handover_id')
                            ->label('Reseller USD Handover')
                            ->options(function () {
                                return ResellerHandoverFg::whereIn('status', ['pending_timetec_invoice'])
                                    ->get()
                                    ->mapWithKeys(function ($handover) {
                                        $resellerName = $handover->reseller_company_name ?? 'Unknown';
                                        $subscriberName = $handover->subscriber_name ?? 'Unknown';
                                        return [$handover->id => "{$handover->fg_id} - {$resellerName} - {$subscriberName}"];
                                    });
                            })
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, \Filament\Forms\Set $set) {
                                if ($state) {
                                    $handover = ResellerHandoverFg::find($state);
                                    if ($handover) {
                                        $set('reseller_name', strtoupper($handover->reseller_company_name ?? ''));
                                        $set('subscriber_name', strtoupper($handover->subscriber_name ?? ''));
                                        $set('timetec_invoice_number', strtoupper($handover->timetec_proforma_invoice ?? ''));
                                    }
                                }
                            }),

                        \Filament\Forms\Components\Hidden::make('currency')
                            ->default('USD'),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('timetec_invoice_number')
                                    ->label('TimeTec Invoice Number')
                                    ->required()
                                    ->disabled()
                                    ->dehydrated(true)
                                    ->dehydrateStateUsing(fn ($state) => strtoupper($state)),

                                TextInput::make('autocount_invoice_number')
                                    ->label('AutoCount Invoice Number')
                                    ->required()
                                    ->default('ERIN')
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
                                    ->minLength(13)
                                    ->maxLength(13)
                                    ->rules([
                                        'regex:/^ERIN/i',
                                    ])
                                    ->validationMessages([
                                        'min' => 'The AutoCount Invoice Number field must be at least 13 characters.',
                                        'max' => 'The AutoCount Invoice Number field must not exceed 13 characters.',
                                        'required' => 'The AutoCount Invoice Number field is required.',
                                        'regex' => 'The AutoCount Invoice Number must start with ERIN.',
                                    ])
                                    ->rule(function () {
                                        return function (string $attribute, $value, \Closure $fail) {
                                            if (!$value) {
                                                return;
                                            }
                                            $exists = FinanceInvoice::where('autocount_invoice_number', strtoupper($value))->exists();
                                            if ($exists) {
                                                $fail('This AutoCount Invoice Number already exists.');
                                            }
                                        };
                                    }),
                            ])
                            ->visible(fn ($get) => filled($get('handover_id'))),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('reseller_name')
                                    ->label('Reseller Name')
                                    ->disabled()
                                    ->dehydrated(true),

                                TextInput::make('reseller_commission_amount')
                                    ->label('Reseller Commission Amount')
                                    ->required()
                                    ->numeric()
                                    ->prefix('USD')
                                    ->step('0.01'),
                            ])
                            ->visible(fn ($get) => filled($get('handover_id'))),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('subscriber_name')
                                    ->label('Subscriber Name')
                                    ->disabled()
                                    ->dehydrated(true),

                                TextInput::make('currency_rate')
                                    ->label('Currency Rate')
                                    ->numeric()
                                    ->step('0.0001')
                                    ->default('1.0000')
                                    ->required()
                                    ->dehydrated(true),
                            ])
                            ->visible(fn ($get) => filled($get('handover_id'))),
                    ])
                    ->action(function (array $data): void {
                        try {
                            $invoice = FinanceInvoice::create([
                                'fc_number' => FinanceInvoice::generateFcNumber('reseller_usd', $data['autocount_invoice_number'] ?? null),
                                'handover_id' => $data['handover_id'],
                                'autocount_invoice_number' => $data['autocount_invoice_number'],
                                'timetec_invoice_number' => $data['timetec_invoice_number'] ?? null,
                                'reseller_name' => $data['reseller_name'],
                                'subscriber_name' => $data['subscriber_name'],
                                'reseller_commission_amount' => $data['reseller_commission_amount'],
                                'portal_type' => 'reseller_usd',
                                'status' => (float) $data['reseller_commission_amount'] == 0 ? 'completed' : 'new',
                                'created_by' => auth()->id(),
                                'currency' => 'USD',
                                'currency_rate' => $data['currency_rate'] ?? 1.00,
                            ]);

                            Notification::make()
                                ->title('Invoice Generated')
                                ->success()
                                ->body('Finance invoice has been generated successfully.')
                                ->send();

                            $this->dispatch('refresh-leadowner-tables');

                            $this->js('window.open("' . route('pdf.print-finance-invoice', $invoice) . '", "_blank")');
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error')
                                ->danger()
                                ->body('Failed to generate invoice: ' . $e->getMessage())
                                ->send();
                        }
                    }),
            ])
            ->defaultSort('updated_at', 'desc')
            ->paginated([10, 25, 50, 100])
            ->emptyState(fn () => view('components.empty-state-question'));
    }

    public function render()
    {
        return view('livewire.admin-reseller-handover-fg-pending-timetec-admin');
    }
}
