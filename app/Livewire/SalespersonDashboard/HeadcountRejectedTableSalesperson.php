<?php

namespace App\Livewire\SalespersonDashboard;

use Livewire\Component;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Support\Enums\MaxWidth;
use Filament\Forms\Get;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use App\Models\HeadcountHandover;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Illuminate\Support\Str;

class HeadcountRejectedTableSalesperson extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public $lastRefreshTime;
    public $selectedUser;

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

    #[On('refresh-headcount-tables')]
    public function refreshData()
    {
        $this->resetTable();
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
    }

    #[On('updateTablesForUser')] // Listen for updates
    public function updateTablesForUser($selectedUser)
    {
        $this->selectedUser = $selectedUser;
        session(['selectedUser' => $selectedUser]); // Store for consistency

        $this->resetTable(); // Refresh the table
    }

    public function getRejectedHeadcountHandovers()
    {
        $this->selectedUser = $this->selectedUser ?? session('selectedUser') ?? auth()->id();

        $query = HeadcountHandover::query()
            ->whereIn('status', ['Rejected', 'Draft'])
            ->orderBy('submitted_at', 'desc')
            ->with(['lead', 'lead.companyDetail', 'creator']);

        if ($this->selectedUser === 'all-salespersons') {
            // Show all salespersons' handovers
            $salespersonIds = User::where('role_id', 2)->pluck('id');
            $query->whereHas('lead', function ($leadQuery) use ($salespersonIds) {
                $leadQuery->whereIn('salesperson', $salespersonIds);
            });
        } elseif (is_numeric($this->selectedUser)) {
            // Validate that the selected user exists and is a salesperson
            $userExists = User::where('id', $this->selectedUser)->where('role_id', 2)->exists();

            if ($userExists) {
                $selectedUser = $this->selectedUser; // Create a local variable
                $query->whereHas('lead', function ($leadQuery) use ($selectedUser) {
                    $leadQuery->where('salesperson', $selectedUser);
                });
            } else {
                // Invalid user ID or not a salesperson, fall back to default
                $query->whereHas('lead', function ($leadQuery) {
                    $leadQuery->where('salesperson', auth()->id());
                });
            }
        } else {
            if (auth()->user()->role_id === 2) {
                // Salesperson - show only THEIR OWN records
                $userId = auth()->id();
                $query->whereHas('lead', function ($leadQuery) use ($userId) {
                    $leadQuery->where('salesperson', $userId);
                });
            }
            // For non-salesperson users, no additional filtering
        }

        return $query;
    }

    public function getHeadcountCount()
    {
        return $this->getRejectedHeadcountHandovers()->count();
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('300s')
            ->query($this->getRejectedHeadcountHandovers())
            ->defaultSort('submitted_at', 'desc')
            ->emptyState(fn() => view('components.empty-state-question'))
            ->defaultPaginationPageOption(5)
            ->paginated([5])
            ->filters([
                SelectFilter::make('status')
                    ->label('Filter by Status')
                    ->options([
                        'Rejected' => 'Rejected',
                        'Draft' => 'Draft',
                    ])
                    ->placeholder('All Statuses')
                    ->multiple(),

                SelectFilter::make('salesperson')
                    ->label('Filter by Salesperson')
                    ->options(function () {
                        return User::where('role_id', '2')
                            ->whereNot('id', 15) // Exclude Testing Account
                            ->pluck('name', 'name')
                            ->toArray();
                    })
                    ->placeholder('All Salesperson')
                    ->multiple()
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['value'])) {
                            $salespersonNames = $data['value'];
                            $salespersonIds = User::whereIn('name', $salespersonNames)
                                ->where('role_id', '2')
                                ->pluck('id')
                                ->toArray();

                            $query->whereHas('lead', function ($leadQuery) use ($salespersonIds) {
                                $leadQuery->whereIn('salesperson', $salespersonIds);
                            });
                        }
                    })
                    ->visible(fn(): bool => auth()->user()->role_id !== 2), // Hide filter for salespersons
            ])
            ->columns([
                TextColumn::make('id')
                    ->label('Headcount ID')
                    ->formatStateUsing(function ($state, HeadcountHandover $record) {
                        if (!$state) {
                            return 'Unknown';
                        }
                        return $record->formatted_handover_id;
                    })
                    ->color('primary')
                    ->weight('bold')
                    ->action(
                        Action::make('viewHeadcountHandoverDetails')
                            ->modalHeading(false)
                            ->modalWidth('3xl')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalContent(function (HeadcountHandover $record): View {
                                return view('components.headcount-handover')
                                    ->with('extraAttributes', ['record' => $record]);
                            })
                    ),

                TextColumn::make('submitted_at')
                    ->label('Date Submitted')
                    ->dateTime('d M Y, g:ia')
                    ->sortable(),

                TextColumn::make('lead.companyDetail.company_name')
                    ->label('Company Name')
                    ->searchable()
                    ->formatStateUsing(function ($state, $record) {
                        $fullName = $state ?? 'N/A';
                        $shortened = strtoupper(Str::limit($fullName, 25, '...'));
                        $encryptedId = \App\Classes\Encryptor::encrypt($record->lead->id);

                        return '<a href="' . url('admin/leads/' . $encryptedId) . '"
                                    target="_blank"
                                    title="' . e($fullName) . '"
                                    class="inline-block"
                                    style="color:#338cf0;">
                                    ' . $shortened . '
                                </a>';
                    })
                    ->html(),

                TextColumn::make('salesperson_name')
                    ->label('Salesperson')
                    ->getStateUsing(function (HeadcountHandover $record) {
                        if ($record->lead && $record->lead->salesperson) {
                            $user = User::find($record->lead->salesperson);
                            return $user ? $user->name : 'N/A';
                        }
                        return 'N/A';
                    })
                    ->searchable()
                    ->visible(fn(): bool => auth()->user()->role_id !== 2), // Hide for salespersons

                TextColumn::make('status')
                    ->label('STATUS')
                    ->formatStateUsing(fn (string $state): HtmlString => match ($state) {
                        'Draft' => new HtmlString('<span style="color: orange; font-weight: bold;">Draft</span>'),
                        'Rejected' => new HtmlString('<span style="color: red; font-weight: bold;">Rejected</span>'),
                        default => new HtmlString('<span style="font-weight: bold;">' . ucfirst($state) . '</span>'),
                    }),

                TextColumn::make('reject_reason')
                    ->label('Reject Reason')
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    })
                    ->visible(fn ($record) => $record instanceof HeadcountHandover && $record->status === 'Rejected'),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('view')
                        ->label('View Details')
                        ->icon('heroicon-o-eye')
                        ->color('secondary')
                        ->modalHeading(false)
                        ->modalWidth('3xl')
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        ->modalContent(function (HeadcountHandover $record): View {
                            return view('components.headcount-handover')
                                ->with('extraAttributes', ['record' => $record]);
                        }),

                    Action::make('view_reason')
                        ->label('View Reason')
                        ->visible(fn (HeadcountHandover $record): bool => $record->status === 'Rejected')
                        ->icon('heroicon-o-magnifying-glass-plus')
                        ->modalHeading('Rejection Reason')
                        ->modalContent(fn (HeadcountHandover $record) => view('components.view-reason', [
                            'reason' => $record->reject_reason,
                        ]))
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        ->modalWidth('3xl')
                        ->color('warning'),

                    Action::make('convert_to_draft')
                        ->label('Convert to Draft')
                        ->icon('heroicon-o-document')
                        ->color('warning')
                        ->visible(fn (HeadcountHandover $record): bool => $record->status === 'Rejected')
                        ->action(function (HeadcountHandover $record): void {
                            $record->update([
                                'status' => 'Draft'
                            ]);

                            Notification::make()
                                ->title('Headcount handover converted to draft')
                                ->success()
                                ->send();
                        }),

                    Action::make('edit_headcount_handover')
                        ->modalHeading(fn (HeadcountHandover $record): string => "Edit Headcount Handover {$record->formatted_handover_id}")
                        ->label('Edit Headcount Handover')
                        ->icon('heroicon-o-pencil')
                        ->color('warning')
                        ->modalSubmitActionLabel('Save Changes')
                        ->visible(fn (HeadcountHandover $record): bool => $record->status === 'Draft')
                        ->modalWidth(MaxWidth::FourExtraLarge)
                        ->slideOver()
                        ->form($this->getEditForm())
                        ->action(function (HeadcountHandover $record, array $data): void {
                            // Validation
                            $hasPaymentSlip = !empty($data['payment_slip_file']);
                            $hasConfirmationOrder = !empty($data['confirmation_order_file']);
                            if (!$hasPaymentSlip && !$hasConfirmationOrder) {
                                Notification::make()->danger()->title('Upload Required')->body('You must upload at least one document: Payment Slip OR Confirmation Order.')->persistent()->send();
                                return;
                            }
                            // Handle file array encodings
                            foreach (['payment_slip_file', 'confirmation_order_file', 'proforma_invoice_product', 'proforma_invoice_hrdf'] as $field) {
                                if (isset($data[$field]) && is_array($data[$field])) {
                                    $data[$field] = json_encode($data[$field]);
                                }
                            }
                            $data['status'] = 'New';
                            $record->update($data);
                            Notification::make()->title('Headcount handover updated successfully')->body('Status has been changed to "New" and is ready for review.')->success()->send();
                        }),
                ])->button()
                ->label('Actions')
                ->color('primary'),
            ]);
    }

    public function getEditForm(): array
    {
        return [
            Grid::make(2)
                ->schema([
                    Select::make('proforma_invoice_product')
                        ->label('Product PI')
                        ->options(function (?HeadcountHandover $record = null) {
                            $leadId = $record->lead_id;
                            $currentRecordId = $record->id;

                            // Get all PI IDs already used in other headcount handovers for this lead
                            $usedPiIds = [];
                            $headcountHandovers = HeadcountHandover::where('lead_id', $leadId)
                                ->when($currentRecordId, function ($query) use ($currentRecordId) {
                                    return $query->where('id', '!=', $currentRecordId);
                                })
                                ->get();

                            foreach ($headcountHandovers as $handover) {
                                $piProduct = $handover->proforma_invoice_product;
                                if (!empty($piProduct)) {
                                    if (is_string($piProduct)) {
                                        $piIds = json_decode($piProduct, true);
                                        if (is_array($piIds)) {
                                            $usedPiIds = array_merge($usedPiIds, $piIds);
                                        }
                                    } elseif (is_array($piProduct)) {
                                        $usedPiIds = array_merge($usedPiIds, $piProduct);
                                    }
                                }
                            }

                            return \App\Models\Quotation::where('lead_id', $leadId)
                                ->where('quotation_type', 'product')
                                ->where('status', \App\Enums\QuotationStatusEnum::accepted)
                                ->whereDate('created_at', today())
                                ->whereNotIn('id', array_filter($usedPiIds))
                                ->pluck('pi_reference_no', 'id')
                                ->toArray();
                        })
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->helperText('Select Product PI (Required)')
                        ->default(function (?HeadcountHandover $record = null) {
                            if (!$record || !$record->proforma_invoice_product) {
                                return [];
                            }
                            if (is_string($record->proforma_invoice_product)) {
                                return json_decode($record->proforma_invoice_product, true) ?? [];
                            }
                            return is_array($record->proforma_invoice_product) ? $record->proforma_invoice_product : [];
                        }),
                ]),

            Grid::make(2)
                ->schema([
                    FileUpload::make('payment_slip_file')
                        ->label('Upload Payment Slip')
                        ->disk('public')
                        ->directory('handovers/headcount/payment_slips')
                        ->visibility('public')
                        ->multiple()
                        ->maxFiles(5)
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                        ->helperText('Upload Payment Slip files (Maximum 5 files)')
                        ->openable()
                        ->downloadable()
                        ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, callable $get, ?HeadcountHandover $record = null): string {
                            $formattedId = HeadcountHandover::generateFormattedId($record->id);
                            $extension = $file->getClientOriginalExtension();
                            $timestamp = now()->format('YmdHis');
                            $random = rand(1000, 9999);

                            return "{$formattedId}-HC-PAYMENT-{$timestamp}-{$random}.{$extension}";
                        })
                        ->default(function (?HeadcountHandover $record = null) {
                            if (!$record || !$record->payment_slip_file) {
                                return [];
                            }
                            if (is_string($record->payment_slip_file)) {
                                return json_decode($record->payment_slip_file, true) ?? [];
                            }
                            return is_array($record->payment_slip_file) ? $record->payment_slip_file : [];
                        }),

                    FileUpload::make('confirmation_order_file')
                        ->label('Upload Confirmation Order')
                        ->disk('public')
                        ->directory('handovers/headcount/confirmation_orders')
                        ->visibility('public')
                        ->multiple()
                        ->maxFiles(5)
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                        ->helperText('Upload Confirmation Order files (Maximum 5 files)')
                        ->openable()
                        ->downloadable()
                        ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, callable $get, ?HeadcountHandover $record = null): string {
                            $formattedId = HeadcountHandover::generateFormattedId($record->id);
                            $extension = $file->getClientOriginalExtension();
                            $timestamp = now()->format('YmdHis');
                            $random = rand(1000, 9999);

                            return "{$formattedId}-HC-CONFIRM-{$timestamp}-{$random}.{$extension}";
                        })
                        ->default(function (?HeadcountHandover $record = null) {
                            if (!$record || !$record->confirmation_order_file) {
                                return [];
                            }
                            if (is_string($record->confirmation_order_file)) {
                                return json_decode($record->confirmation_order_file, true) ?? [];
                            }
                            return is_array($record->confirmation_order_file) ? $record->confirmation_order_file : [];
                        }),
                ]),

            Section::make('Invoice to Reseller')
                ->columnSpan(1)
                ->schema([
                    Grid::make(1)
                        ->schema([
                            Select::make('reseller_id')
                                ->label(false)
                                ->placeholder('Select Reseller Company (Optional)')
                                ->options(function () {
                                    return \App\Models\Reseller::orderBy('company_name')
                                        ->pluck('company_name', 'id')
                                        ->toArray();
                                })
                                ->searchable()
                                ->preload()
                                ->nullable()
                                ->live()
                                ->default(function (?HeadcountHandover $record = null) {
                                    return $record?->reseller_id ?? null;
                                }),
                        ])
                ]),

            Section::make('Implement By')
                ->columnSpan(1)
                ->visible(fn (Get $get) => !empty($get('reseller_id')))
                ->schema([
                    Grid::make(1)
                        ->schema([
                            Select::make('implement_by')
                                ->label(false)
                                ->options([
                                    'TimeTec' => 'TimeTec',
                                    'Reseller' => 'Reseller',
                                ])
                                ->required()
                                ->placeholder('Select Implement By')
                                ->default(function (?HeadcountHandover $record = null) {
                                    return $record?->implement_by ?? null;
                                }),
                        ])
                ]),

            Textarea::make('salesperson_remark')
                ->label('SalesPerson Remark')
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
                ->dehydrateStateUsing(fn ($state) => strtoupper($state))
                ->default(fn (?HeadcountHandover $record = null) => $record?->salesperson_remark ?? null),
        ];
    }

    public function render()
    {
        return view('livewire.salesperson-dashboard.headcount-rejected-table-salesperson');
    }
}
