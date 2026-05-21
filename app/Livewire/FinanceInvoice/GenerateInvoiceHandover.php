<?php

namespace App\Livewire\FinanceInvoice;

use App\Models\FinanceInvoice;
use App\Models\ResellerHandover;
use App\Models\ResellerHandoverFg;
use App\Models\SoftwareHandover;
use App\Models\HardwareHandoverV2;
use App\Models\HeadcountHandover;
use App\Models\CrmInvoiceDetail;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\Attributes\On;
use Filament\Forms\Components\Grid;
use Illuminate\Support\Facades\Mail;

class GenerateInvoiceHandover extends Component implements HasTable, HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected $listeners = ['refresh-finance-invoice-tables' => '$refresh'];

    public $selectedUser;
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
                FinanceInvoice::whereIn('portal_type', ['software', 'hardware', 'reseller_handover', 'headcount', 'reseller_usd'])
                    ->with(['resellerHandover', 'creator'])
                    ->orderBy('created_at', 'desc')
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
                    ->openUrlInNewTab(),

                TextColumn::make('portal_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'software' => 'Software',
                        'hardware' => 'Hardware',
                        'reseller_handover' => 'Reseller',
                        'reseller_usd' => 'Reseller USD',
                        'headcount' => 'Headcount',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'software' => 'success',
                        'hardware' => 'info',
                        'reseller_handover' => 'warning',
                        'reseller_usd' => 'warning',
                        'headcount' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('autocount_invoice_number')
                    ->label('AC Invoice')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('timetec_invoice_number')
                    ->label('TT Invoice')
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
                    ->weight('bold'),

                TextColumn::make('reseller_name')
                    ->label('Reseller Name')
                    ->limit(35)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('subscriber_name')
                    ->label('Subscriber Name')
                    ->limit(25)
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('reseller_commission_amount')
                    ->label('Amount')
                    ->formatStateUsing(function ($state, $record) {
                        $currency = $record->currency ?? 'MYR';
                        return $currency . ' ' . number_format($state, 2);
                    })
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('view_pdf')
                        ->label('PDF')
                        ->icon('heroicon-o-document-text')
                        ->url(fn (FinanceInvoice $record): string => route('pdf.print-finance-invoice', $record))
                        ->openUrlInNewTab(),

                    Action::make('export_purchase_invoice')
                        ->label('Export Purchase Invoice')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('info')
                        ->url(fn (FinanceInvoice $record): string => route('finance-purchase-invoice.export', $record->id))
                        ->openUrlInNewTab(),
                ])->button(),
            ])
            ->headerActions([
                Action::make('create')
                    ->label('Generate Invoice')
                    ->icon('heroicon-o-plus')
                    ->form([
                        Select::make('handover_type')
                            ->label('Choose Handover Type')
                            ->options([
                                'software' => 'Software Handover',
                                'hardware' => 'Hardware Handover',
                                // 'reseller_handover' => 'Reseller Handover',
                                'reseller_usd' => 'Reseller USD',
                                'headcount' => 'Headcount Handover',
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $set('handover_id', null);
                                $set('reseller_name', null);
                                $set('subscriber_name', null);
                                $set('fc_id', null);
                            }),

                        Select::make('handover_id')
                            ->label(fn ($get) => match ($get('handover_type')) {
                                'software' => 'Software Handover',
                                'hardware' => 'Hardware Handover',
                                // 'reseller_handover' => 'Reseller Handover',
                                'headcount' => 'Headcount Handover',
                                'reseller_usd' => 'Reseller USD Handover',
                                default => 'Select Handover',
                            })
                            ->options(function ($get) {
                                $type = $get('handover_type');

                                if ($type === 'software') {
                                    return SoftwareHandover::where('status', 'new')
                                        ->whereNotNull('reseller_id')
                                        ->with(['reseller'])
                                        ->get()
                                        ->mapWithKeys(function ($handover) {
                                            $resellerName = $handover->reseller?->company_name ?? 'Unknown';
                                            $subscriberName = $handover->company_name ?? 'Unknown';
                                            return [$handover->id => "{$handover->formatted_handover_id} - {$resellerName} - {$subscriberName}"];
                                        });
                                } elseif ($type === 'hardware') {
                                    return HardwareHandoverV2::where('status', 'Pending Stock')
                                        ->whereNotNull('reseller_id')
                                        ->with(['reseller', 'lead'])
                                        ->get()
                                        ->mapWithKeys(function ($handover) {
                                            $resellerName = $handover->reseller?->company_name ?? 'Unknown';
                                            $subscriberName = $handover->lead?->companyDetail->company_name ?? 'Unknown';
                                            return [$handover->id => "{$handover->formatted_handover_id} - {$resellerName} - {$subscriberName}"];
                                        });
                                } elseif ($type === 'reseller_handover') {
                                    return ResellerHandover::where('status', 'new')
                                        ->get()
                                        ->mapWithKeys(function ($handover) {
                                            $resellerName = $handover->reseller_name ?? 'Unknown';
                                            $subscriberName = $handover->subscriber_name ?? 'Unknown';
                                            return [$handover->id => "RH-{$handover->id} - {$resellerName} - {$subscriberName}"];
                                        });
                                } elseif ($type === 'headcount') {
                                    return HeadcountHandover::where('status', 'new')
                                        ->whereNotNull('reseller_id')
                                        ->with(['reseller', 'lead.companyDetail'])
                                        ->get()
                                        ->mapWithKeys(function ($handover) {
                                            $resellerName = $handover->reseller?->company_name ?? 'Unknown';
                                            $companyName = $handover->lead?->companyDetail?->company_name ?? 'Unknown';
                                            return [$handover->id => "{$handover->formatted_handover_id} - {$resellerName} - {$companyName}"];
                                        });
                                } elseif ($type === 'reseller_usd') {
                                    return ResellerHandoverFg::whereIn('status', ['pending_timetec_invoice'])
                                        ->get()
                                        ->mapWithKeys(function ($handover) {
                                            $resellerName = $handover->reseller_company_name ?? 'Unknown';
                                            $subscriberName = $handover->subscriber_name ?? 'Unknown';
                                            return [$handover->id => "{$handover->fg_id} - {$resellerName} - {$subscriberName}"];
                                        });
                                }

                                return [];
                            })
                            ->searchable()
                            ->required()
                            ->visible(fn ($get) => filled($get('handover_type')))
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                if ($state) {
                                    $type = $get('handover_type');
                                    $handover = null;

                                    if ($type === 'software') {
                                        $handover = SoftwareHandover::with(['reseller'])->find($state);
                                        if ($handover) {
                                            $set('reseller_name', strtoupper($handover->reseller?->company_name ?? ''));
                                            $set('subscriber_name', strtoupper($handover->company_name ?? ''));
                                        }
                                    } elseif ($type === 'hardware') {
                                        $handover = HardwareHandoverV2::with(['reseller', 'lead'])->find($state);
                                        if ($handover) {
                                            $set('reseller_name', strtoupper($handover->reseller?->company_name ?? ''));
                                            $set('subscriber_name', strtoupper($handover->lead?->companyDetail->company_name ?? ''));
                                        }
                                    } elseif ($type === 'reseller_handover') {
                                        $handover = ResellerHandover::find($state);
                                        if ($handover) {
                                            $set('reseller_name', strtoupper($handover->reseller_name ?? ''));
                                            $set('subscriber_name', strtoupper($handover->subscriber_name ?? ''));
                                        }
                                    } elseif ($type === 'reseller_usd') {
                                        $handover = ResellerHandoverFg::find($state);
                                        if ($handover) {
                                            $set('reseller_name', strtoupper($handover->reseller_company_name ?? ''));
                                            $set('subscriber_name', strtoupper($handover->subscriber_name ?? ''));
                                            $set('timetec_invoice_number', strtoupper($handover->timetec_proforma_invoice ?? ''));
                                            $set('currency', 'USD');
                                        }
                                    } elseif ($type === 'headcount') {
                                        $handover = HeadcountHandover::with(['reseller', 'lead.companyDetail'])->find($state);
                                        if ($handover) {
                                            $set('reseller_name', strtoupper($handover->reseller?->company_name ?? ''));
                                            $set('subscriber_name', strtoupper($handover->lead?->companyDetail?->company_name ?? ''));
                                        }
                                    }
                                }
                            }),

                        Hidden::make('currency')
                            ->default('MYR'),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('timetec_invoice_number')
                                    ->label('TimeTec Invoice Number')
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
                                    ->minLength(12)
                                    ->maxLength(12)
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

                                            $exists = \App\Models\FinanceInvoice::where('autocount_invoice_number', strtoupper($value))->exists();

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
                                    ->prefix(fn ($get) => $get('currency') ?? 'RM')
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
                                    ->visible(fn ($get) => $get('handover_type') === 'reseller_usd')
                                    ->required(fn ($get) => $get('handover_type') === 'reseller_usd')
                                    ->dehydrated(true),
                            ])
                            ->visible(fn ($get) => filled($get('handover_id'))),
                    ])
                    ->action(function (array $data): void {
                        try {
                            // Find the handover based on type
                            $resellerHandoverId = null;
                            if ($data['handover_type'] === 'reseller_handover') {
                                $resellerHandoverId = $data['handover_id'];
                            }

                            $invoice = FinanceInvoice::create([
                                'fc_number' => FinanceInvoice::generateFcNumber($data['handover_type'], $data['autocount_invoice_number'] ?? null),
                                'handover_id' => $data['handover_id'],
                                'autocount_invoice_number' => $data['autocount_invoice_number'],
                                'timetec_invoice_number' => $data['timetec_invoice_number'] ?? null,
                                'reseller_name' => $data['reseller_name'],
                                'subscriber_name' => $data['subscriber_name'],
                                'reseller_commission_amount' => $data['reseller_commission_amount'],
                                'portal_type' => $data['handover_type'],
                                'status' => (float) $data['reseller_commission_amount'] == 0 ? 'completed' : 'new',
                                'created_by' => auth()->id(),
                                'currency' => $data['currency'] ?? 'MYR',
                                'currency_rate' => $data['currency_rate'] ?? 1.00,
                            ]);

                            Notification::make()
                                ->title('Invoice Generated')
                                ->success()
                                ->body('Finance invoice has been generated successfully.')
                                ->send();

                            $this->dispatch('refresh-finance-invoice-tables');

                            // Open PDF in new tab
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
            ->defaultSort('created_at', 'desc')
            ->poll('300s');
    }

    public function render()
    {
        return view('livewire.finance-invoice.generate-invoice-handover');
    }
}
