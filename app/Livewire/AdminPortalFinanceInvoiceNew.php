<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\FinanceInvoice;
use App\Models\ResellerHandover;
use App\Models\CrmInvoiceDetail;
use App\Models\AdminPortalInvoice;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\DB;

class AdminPortalFinanceInvoiceNew extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

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

    #[On('refresh-adminrepair-tables')]
    public function refreshData()
    {
        $this->resetTable();
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
    }

    #[On('updateTablesForUser')]
    public function updateTablesForUser($selectedUser)
    {
        $this->selectedUser = $selectedUser;
        session(['selectedUser' => $selectedUser]);

        $this->resetTable();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(CrmInvoiceDetail::query()->pendingInvoices())
            ->defaultSort('f_payment_time', 'desc')
            ->defaultPaginationPageOption('all')
            ->paginated('all')
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('f_currency')
                    ->label('Currency')
                    ->options([
                        'MYR' => 'MYR',
                        'USD' => 'USD',
                    ]),
                \Filament\Tables\Filters\SelectFilter::make('f_payment_method')
                    ->label('Payment Method')
                    ->options([
                        'pp' => 'PayPal',
                        'bt' => 'Bank Transfer',
                        'cs' => 'Cash',
                        'cq' => 'Cheque',
                        'pt' => 'Point',
                        'cc' => 'Credit Card',
                        'rz' => 'RazerPay',
                    ]),
                \Filament\Tables\Filters\Filter::make('subscriber_availability')
                    ->label('Subscriber Name')
                    ->form([
                        \Filament\Forms\Components\Select::make('availability')
                            ->options([
                                'available' => 'Available',
                                'not_available' => 'Not Available',
                            ])
                            ->placeholder('All'),
                    ])
                    ->query(function ($query, array $data) {
                        if (isset($data['availability'])) {
                            if ($data['availability'] === 'available') {
                                return $query->whereNotNull('company.f_company_name')
                                    ->where('company.f_company_name', '!=', '');
                            } elseif ($data['availability'] === 'not_available') {
                                return $query->where(function($q) {
                                    $q->whereNull('company.f_company_name')
                                      ->orWhere('company.f_company_name', '=', '');
                                });
                            }
                        }
                        return $query;
                    }),
            ])
            ->columns([
                TextColumn::make('row_number')
                    ->label('No')
                    ->rowIndex(),
                TextColumn::make('f_payment_time')
                    ->label('Date')
                    ->formatStateUsing(fn ($state) => $state ? date('d M Y', strtotime($state)) : '-')
                    ->sortable()
                    ->default('-')
                    ->placeholder('-'),
                TextColumn::make('f_invoice_no')
                    ->label('TT Invoice')
                    ->searchable()
                    ->sortable()
                    ->url(function ($record) {
                        $aesKey = 'Epicamera@99';
                        try {
                            $encrypted = openssl_encrypt($record->f_id, "AES-128-ECB", $aesKey);
                            $encryptedBase64 = base64_encode($encrypted);
                            return 'https://www.timeteccloud.com/paypal_reseller_invoice?iIn=' . $encryptedBase64;
                        } catch (\Exception $e) {
                            return null;
                        }
                    })
                    ->openUrlInNewTab()
                    ->color('primary')
                    ->weight('bold'),
                TextColumn::make('subscriber_name')
                    ->label('Reseller Name')
                    ->searchable()
                    ->formatStateUsing(fn ($state) => strtoupper($state ?? '-'))
                    ->tooltip(fn ($state) => strtoupper($state ?? '-'))
                    ->default('-')
                    ->placeholder('-'),
                TextColumn::make('company_name')
                    ->label('Subscriber Name')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->getStateUsing(function ($record) {
                        // Get the raw value directly
                        $value = $record->company_name;

                        // Check if value exists and is not empty
                        if (!is_null($value) && trim($value) !== '') {
                            return 'Available';
                        }
                        return 'Not Available';
                    })
                    ->color(function ($record) {
                        $value = $record->company_name;
                        if (!is_null($value) && trim($value) !== '') {
                            return 'success';
                        }
                        return 'gray';
                    })
                    ->tooltip(function ($record) {
                        $value = $record->company_name;
                        if (!is_null($value) && trim($value) !== '') {
                            return strtoupper($value);
                        }
                        return 'NOT AVAILABLE';
                    }),
                TextColumn::make('f_payment_method')
                    ->label('Method')
                    ->searchable()
                    ->formatStateUsing(function ($state) {
                        $methods = [
                            'pp' => 'PayPal',
                            'bt' => 'Bank Transfer',
                            'cs' => 'Cash',
                            'cq' => 'Cheque',
                            'pt' => 'Point',
                            'cc' => 'Credit Card',
                            'rz' => 'RazerPay',
                        ];
                        return $methods[strtolower($state ?? '')] ?? ($state ?? '-');
                    })
                    ->limit(30)
                    ->tooltip(function ($state) {
                        $methods = [
                            'pp' => 'PayPal',
                            'bt' => 'Bank Transfer',
                            'cs' => 'Cash',
                            'cq' => 'Cheque',
                            'pt' => 'Point',
                            'cc' => 'Credit Card',
                            'rz' => 'RazerPay',
                        ];
                        return $methods[strtolower($state ?? '')] ?? ($state ?? '-');
                    })
                    ->default('-')
                    ->placeholder('-'),
                TextColumn::make('f_currency')
                    ->label('Currency')
                    ->searchable()
                    ->sortable()
                    ->default('-')
                    ->placeholder('-'),
                TextColumn::make('f_total_amount')
                    ->label('Amount')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format($state, 2))
                    ->default('0.00')
                    ->placeholder('0.00'),
            ])
            ->headerActions([
                Action::make('generate_invoice')
                    ->label('Generate Invoice')
                    ->icon('heroicon-o-plus')
                    ->form([
                        Select::make('crm_invoice_detail_id')
                            ->label('Admin Portal')
                            ->options(function () {
                                return CrmInvoiceDetail::query()
                                    ->pendingInvoices()
                                    ->get()
                                    ->filter(function ($invoice) {
                                        return !FinanceInvoice::where('timetec_invoice_number', $invoice->f_invoice_no)->exists();
                                    })
                                    ->mapWithKeys(function ($invoice) {
                                        $companyName = strtoupper($invoice->subscriber_name ?? 'Unknown Company');
                                        $subscriberName = strtoupper($invoice->company_name ?? 'Not Available');
                                        $amount = number_format($invoice->f_total_amount, 2);
                                        return [$invoice->f_id => "{$invoice->f_invoice_no} - {$companyName} - {$subscriberName} - {$invoice->f_currency} {$amount}"];
                                    });
                            })
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $invoice = CrmInvoiceDetail::query()
                                        ->pendingInvoices()
                                        ->havingRaw('MIN(crm_invoice_details.f_id) = ?', [$state])
                                        ->first();

                                    if ($invoice) {
                                        $set('reseller_name', strtoupper($invoice->subscriber_name ?? ''));
                                        $set('subscriber_name', strtoupper($invoice->company_name ?? ''));
                                        $set('timetec_invoice_number', strtoupper($invoice->f_invoice_no ?? ''));
                                        $set('currency', $invoice->f_currency ?? 'MYR');
                                        $set('currency_rate', $invoice->f_currency === 'MYR' ? '1.00' : null);
                                    }
                                }
                            }),

                        \Filament\Forms\Components\Hidden::make('currency')
                            ->default('MYR'),

                        \Filament\Forms\Components\Grid::make(2)
                            ->schema([
                                TextInput::make('timetec_invoice_number')
                                    ->label('TimeTec Invoice Number')
                                    ->disabled()
                                    ->dehydrated(true),

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
                                    ->rules(['regex:/^ERIN/i'])
                                    ->validationMessages([
                                        'min' => 'Must be at least 13 characters.',
                                        'max' => 'Must not exceed 13 characters.',
                                        'regex' => 'Must start with ERIN.',
                                    ])
                                    ->rule(function () {
                                        return function (string $attribute, $value, \Closure $fail) {
                                            if ($value && FinanceInvoice::where('autocount_invoice_number', strtoupper($value))->exists()) {
                                                $fail('This AutoCount Invoice Number already exists.');
                                            }
                                        };
                                    }),
                            ]),

                        \Filament\Forms\Components\Grid::make(2)
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
                            ]),

                        \Filament\Forms\Components\Grid::make(2)
                            ->schema([
                                TextInput::make('subscriber_name')
                                    ->label('Subscriber Name')
                                    ->disabled()
                                    ->dehydrated(true),

                                TextInput::make('currency_rate')
                                    ->label('Currency Rate')
                                    ->required()
                                    ->numeric()
                                    ->step('0.0001')
                                    ->default('1.0000'),
                            ]),
                    ])
                    ->action(function (array $data): void {
                        try {
                            $resellerHandover = null;
                            if (!empty($data['timetec_invoice_number'])) {
                                $resellerHandover = ResellerHandover::where('timetec_proforma_invoice', $data['timetec_invoice_number'])
                                    ->where('subscriber_name', $data['subscriber_name'])
                                    ->first();
                            }

                            $invoice = FinanceInvoice::create([
                                'fc_number' => FinanceInvoice::generateFcNumber('admin', $data['autocount_invoice_number'] ?? null),
                                'reseller_handover_id' => $resellerHandover?->id,
                                'autocount_invoice_number' => $data['autocount_invoice_number'],
                                'timetec_invoice_number' => $data['timetec_invoice_number'] ?? null,
                                'reseller_name' => $data['reseller_name'],
                                'subscriber_name' => $data['subscriber_name'],
                                'reseller_commission_amount' => $data['reseller_commission_amount'],
                                'portal_type' => 'admin',
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

                            $this->js('window.open("' . route('pdf.print-finance-invoice', $invoice) . '", "_blank")');
                            $this->resetTable();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error')
                                ->danger()
                                ->body('Failed to generate invoice: ' . $e->getMessage())
                                ->send();
                        }
                    }),
            ])
            ->actions([
                Action::make('update_autocount')
                    ->label('Update')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->fillForm(function ($record) {
                        // Try to find matching finance invoice — only those still
                        // selectable in the dropdown (i.e. not yet linked to a
                        // reseller handover), so the auto-filled ID actually exists
                        // in the options list and renders with its proper label.
                        $matchingInvoice = FinanceInvoice::where('portal_type', 'admin')
                            ->where('status', 'new')
                            ->whereNull('reseller_handover_id')
                            ->where('reseller_name', strtoupper($record->subscriber_name ?? ''))
                            ->where('subscriber_name', strtoupper($record->company_name ?? ''))
                            ->first();

                        if ($matchingInvoice) {
                            return [
                                'finance_invoice' => $matchingInvoice->id,
                                'autocount_invoice' => $matchingInvoice->autocount_invoice_number,
                            ];
                        }

                        return [];
                    })
                    ->modalWidth('md')
                    ->form([
                        Select::make('finance_invoice')
                            ->label('Self Billed Invoice')
                            ->options(function () {
                                $validInvoiceNumbers = CrmInvoiceDetail::on('frontenddb')
                                    ->pluck('f_invoice_no')
                                    ->filter()
                                    ->toArray();

                                return FinanceInvoice::where('portal_type', 'admin')
                                    ->whereNull('reseller_handover_id')
                                    ->whereIn('timetec_invoice_number', $validInvoiceNumbers)
                                    ->select('id', 'timetec_invoice_number', 'autocount_invoice_number', 'reseller_name', 'subscriber_name', 'created_at')
                                    ->get()
                                    ->mapWithKeys(function ($invoice) {
                                        return [$invoice->id => "{$invoice->timetec_invoice_number} - {$invoice->autocount_invoice_number} - {$invoice->reseller_name}"];
                                    });
                            })
                            ->searchable()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, $set) {
                                if ($state) {
                                    $invoice = FinanceInvoice::find($state);
                                    if ($invoice) {
                                        $set('autocount_invoice', $invoice->autocount_invoice_number);
                                    }
                                }
                            }),
                        TextInput::make('autocount_invoice')
                            ->label('Autocount Invoice Number')
                            ->maxLength(13)
                            ->minLength(13)
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
                            ->required(),
                    ])
                    ->action(function (array $data, $record) {
                        try {
                            // Update ac_invoice
                            DB::connection('frontenddb')
                                ->table('ac_invoice')
                                ->where('f_id', $record->f_id)
                                ->limit(1)
                                ->update(['f_auto_count_inv' => $data['autocount_invoice']]);

                            // Get finance invoice details
                            $financeInvoice = FinanceInvoice::with('resellerHandover')->find($data['finance_invoice']);

                            // Get reseller and subscriber names directly from finance invoice
                            $resellerName = $financeInvoice?->reseller_name ?? '-';
                            $subscriberName = $financeInvoice?->subscriber_name ?? '-';

                            // Create AdminPortalInvoice record
                            // AdminPortalInvoice::create([
                            //     'finance_invoice_id' => $data['finance_invoice'],
                            //     'reseller_name' => $resellerName,
                            //     'subscriber_name' => $subscriberName,
                            //     'tt_invoice' => $record->f_invoice_no,
                            //     'autocount_invoice' => $data['autocount_invoice'],
                            // ]);

                            // Send email notification
                            // try {
                            //     \Illuminate\Support\Facades\Mail::send('emails.admin-portal-finance-completed', [
                            //         'financeInvoice' => $financeInvoice,
                            //         'resellerName' => $resellerName,
                            //         'subscriberName' => $subscriberName,
                            //         'autocountInvoice' => $data['autocount_invoice']
                            //     ], function ($message) use ($financeInvoice) {
                            //         $message->to(['auni1@timeteccloud.com', 'faiz@timeteccloud.com'])
                            //             ->subject('ADMIN PORTAL | ' . $financeInvoice->formatted_id . ' | COMPLETED');
                            //     });
                            // } catch (\Exception $e) {
                            //     \Illuminate\Support\Facades\Log::error('Failed to send admin portal finance completion email', [
                            //         'error' => $e->getMessage(),
                            //         'finance_invoice_id' => $data['finance_invoice']
                            //     ]);
                            // }

                            Notification::make()
                                ->title('Autocount invoice updated successfully')
                                ->success()
                                ->send();

                            $this->dispatch('refresh-finance-invoice-counts');
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Failed to update autocount invoice')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->emptyState(fn () => view('components.empty-state-question'));
    }

    public function render()
    {
        return view('livewire.admin-portal-finance-invoice-new');
    }
}
