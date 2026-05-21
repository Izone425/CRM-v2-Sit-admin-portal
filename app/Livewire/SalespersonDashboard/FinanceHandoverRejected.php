<?php

namespace App\Livewire\SalespersonDashboard;

use App\Models\FinanceHandover;
use App\Models\HardwareHandoverV2;
use App\Models\Reseller;
use App\Models\ResellerInstallationPayment;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class FinanceHandoverRejected extends Component implements HasForms, HasTable
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

    #[On('updateTablesForUser')]
    public function updateTablesForUser($selectedUser)
    {
        $this->selectedUser = $selectedUser;
        session(['selectedUser' => $selectedUser]);

        $this->resetTable();
    }

    public function getRejectedFinanceHandovers()
    {
        $this->selectedUser = $this->selectedUser ?? session('selectedUser') ?? auth()->id();

        $query = FinanceHandover::query()
            ->whereIn('status', ['Rejected', 'Draft'])
            ->with(['lead.companyDetail', 'creator', 'reseller']);

        if ($this->selectedUser === 'all-salespersons') {
            $salespersonIds = User::where('role_id', 2)->pluck('id');
            $query->whereHas('lead', function ($leadQuery) use ($salespersonIds) {
                $leadQuery->whereIn('salesperson', $salespersonIds);
            });
        } elseif (is_numeric($this->selectedUser)) {
            $userExists = User::where('id', $this->selectedUser)->where('role_id', 2)->exists();

            if ($userExists) {
                $selectedUser = $this->selectedUser;
                $query->whereHas('lead', function ($leadQuery) use ($selectedUser) {
                    $leadQuery->where('salesperson', $selectedUser);
                });
            } else {
                $query->whereHas('lead', function ($leadQuery) {
                    $leadQuery->where('salesperson', auth()->id());
                });
            }
        } else {
            if (auth()->user()->role_id === 2) {
                $userId = auth()->id();
                $query->whereHas('lead', function ($leadQuery) use ($userId) {
                    $leadQuery->where('salesperson', $userId);
                });
            }
        }

        return $query;
    }

    public function getEditForm(): array
    {
        return [
            Section::make('Step 1: Reseller Details')
                ->schema([
                    Grid::make(3)
                        ->schema([
                        Select::make('related_hardware_handovers')
                            ->label('Hardware Handover')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->options(function (?FinanceHandover $record = null) {
                                $leadId = $record?->lead_id;
                                if (!$leadId) return [];

                                return HardwareHandoverV2::where('lead_id', $leadId)
                                    ->get()
                                    ->mapWithKeys(function ($handover) {
                                        $formattedId = $handover->formatted_handover_id;
                                        $displayName = $formattedId;

                                        if ($handover->status) {
                                            $displayName .= ' - ' . $handover->status;
                                        }

                                        if ($handover->created_at) {
                                            $displayName .= ' (' . $handover->created_at->format('d M Y') . ')';
                                        }

                                        return [$handover->id => $displayName];
                                    })
                                    ->toArray();
                            }),

                        Select::make('reseller_id')
                            ->label('Reseller')
                            ->required()
                            ->options(function () {
                                return Reseller::pluck('company_name', 'id')->toArray();
                            })
                            ->searchable()
                            ->preload()
                            ->live(),

                        TextInput::make('reseller_invoice_number')
                            ->label('Reseller Invoice Number')
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
                    ]),

                    Grid::make(3)
                        ->schema([
                            TextInput::make('pic_name')
                                ->label('Name')
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
                                ->dehydrateStateUsing(fn ($state) => strtoupper($state)),

                            TextInput::make('pic_phone')
                                ->label('HP Number')
                                ->required()
                                ->tel()
                                ->numeric(),

                            TextInput::make('pic_email')
                                ->label('Email Address')
                                ->required()
                                ->email(),
                        ]),
                ]),

            Section::make('Step 2: Payment Method')
                ->schema([
                    Radio::make('payment_method')
                        ->label('Payment Method')
                        ->options([
                            'bank_transfer' => 'Via Bank Transfer',
                            'hrdf' => 'Via HRDF',
                        ])
                        ->inline()
                        ->inlineLabel(false)
                        ->required()
                        ->live()
                        ->default(fn (?FinanceHandover $record) => $record?->payment_method),
                ]),

            Section::make('Step 3: Upload Documents')
                ->schema([
                    Grid::make(3)
                        ->schema([
                            FileUpload::make('quotation_by_reseller')
                                ->label('Quotation by Reseller')
                                ->disk('public')
                                ->directory('finance_handovers/quotation_reseller')
                                ->visibility('public')
                                ->multiple()
                                ->maxFiles(5)
                                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                                ->openable()
                                ->downloadable()
                                ->required()
                                ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, ?FinanceHandover $record = null): string {
                                    $leadId = $record?->lead_id ?? 0;
                                    $year = now()->format('y');
                                    $formattedId = sprintf('FN_%02d%04d', $year, $leadId);
                                    $extension = $file->getClientOriginalExtension();
                                    $timestamp = now()->format('YmdHis');
                                    $random = rand(1000, 9999);
                                    return "{$formattedId}-QUO-RESELLER-{$timestamp}-{$random}.{$extension}";
                                }),

                            FileUpload::make('invoice_by_reseller')
                                ->label('Invoice by Reseller')
                                ->disk('public')
                                ->directory('finance_handovers/invoice_reseller')
                                ->visibility('public')
                                ->multiple()
                                ->maxFiles(5)
                                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                                ->openable()
                                ->downloadable()
                                ->required()
                                ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, ?FinanceHandover $record = null): string {
                                    $leadId = $record?->lead_id ?? 0;
                                    $year = now()->format('y');
                                    $formattedId = sprintf('FN_%02d%04d', $year, $leadId);
                                    $extension = $file->getClientOriginalExtension();
                                    $timestamp = now()->format('YmdHis');
                                    $random = rand(1000, 9999);
                                    return "{$formattedId}-INV-RESELLER-{$timestamp}-{$random}.{$extension}";
                                }),

                            FileUpload::make('invoice_by_customer')
                                ->label('Invoice by Customer')
                                ->disk('public')
                                ->directory('finance_handovers/invoice_customer')
                                ->visibility('public')
                                ->multiple()
                                ->maxFiles(5)
                                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                                ->openable()
                                ->downloadable()
                                ->required()
                                ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, ?FinanceHandover $record = null): string {
                                    $leadId = $record?->lead_id ?? 0;
                                    $year = now()->format('y');
                                    $formattedId = sprintf('FN_%02d%04d', $year, $leadId);
                                    $extension = $file->getClientOriginalExtension();
                                    $timestamp = now()->format('YmdHis');
                                    $random = rand(1000, 9999);
                                    return "{$formattedId}-INV-CUSTOMER-{$timestamp}-{$random}.{$extension}";
                                }),

                            FileUpload::make('payment_by_customer')
                                ->label('Payment by Customer')
                                ->disk('public')
                                ->directory('finance_handovers/payment_customer')
                                ->visibility('public')
                                ->multiple()
                                ->maxFiles(5)
                                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                                ->openable()
                                ->downloadable()
                                ->required()
                                ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, ?FinanceHandover $record = null): string {
                                    $leadId = $record?->lead_id ?? 0;
                                    $year = now()->format('y');
                                    $formattedId = sprintf('FN_%02d%04d', $year, $leadId);
                                    $extension = $file->getClientOriginalExtension();
                                    $timestamp = now()->format('YmdHis');
                                    $random = rand(1000, 9999);
                                    return "{$formattedId}-PAY-CUSTOMER-{$timestamp}-{$random}.{$extension}";
                                }),

                            FileUpload::make('product_quotation')
                                ->label('Product Quotation')
                                ->disk('public')
                                ->directory('finance_handovers/product_quotation')
                                ->visibility('public')
                                ->multiple()
                                ->maxFiles(5)
                                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                                ->openable()
                                ->downloadable()
                                ->required()
                                ->visible(fn (Forms\Get $get) => $get('payment_method') === 'hrdf')
                                ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, ?FinanceHandover $record = null): string {
                                    $leadId = $record?->lead_id ?? 0;
                                    $year = now()->format('y');
                                    $formattedId = sprintf('FN_%02d%04d', $year, $leadId);
                                    $extension = $file->getClientOriginalExtension();
                                    $timestamp = now()->format('YmdHis');
                                    $random = rand(1000, 9999);
                                    return "{$formattedId}-PROD-QUOTATION-{$timestamp}-{$random}.{$extension}";
                                }),
                        ]),
                ]),

            Section::make('Step 4: Bind Installation Payment')
                ->schema([
                    Select::make('installation_payment_id')
                        ->label('Pending Installation Payment')
                        ->searchable()
                        ->required()
                        ->options(function () {
                            return ResellerInstallationPayment::where('status', 'new')
                                ->where('attention_to', auth()->id())
                                ->whereNull('finance_handover_id')
                                ->orderBy('created_at', 'desc')
                                ->get()
                                ->mapWithKeys(function ($payment) {
                                    $label = $payment->formatted_id . ' | ' . $payment->customer_name;
                                    return [$payment->id => $label];
                                })
                                ->toArray();
                        }),
                ]),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getRejectedFinanceHandovers())
            ->emptyState(fn () => view('components.empty-state-question'))
            ->defaultPaginationPageOption(5)
            ->paginated([5])
            ->filters([
                SelectFilter::make('salesperson')
                    ->label('Filter by Salesperson')
                    ->options(function () {
                        return User::where('role_id', '2')
                            ->whereNot('id', 15)
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
                    ->visible(fn(): bool => auth()->user()->role_id !== 2),
            ])
            ->columns([
                TextColumn::make('formatted_handover_id')
                    ->label('ID')
                    ->sortable()
                    ->color('primary')
                    ->weight('bold')
                    ->action(
                        Action::make('viewFinanceDetails')
                            ->modalHeading(false)
                            ->modalWidth('4xl')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalContent(function (FinanceHandover $record) {
                                return view('components.finance-handover-details', [
                                    'record' => $record
                                ]);
                            })
                    ),

                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn(string $state): HtmlString => match ($state) {
                        'Draft' => new HtmlString('<span style="color: #f97316;">Draft</span>'),
                        'Rejected' => new HtmlString('<span style="color: #ef4444;">Rejected</span>'),
                        default => new HtmlString('<span>' . ucfirst($state) . '</span>'),
                    })
                    ->sortable(),

                TextColumn::make('creator.name')
                    ->label('SalesPerson')
                    ->sortable(),

                TextColumn::make('lead.companyDetail.company_name')
                    ->label('Company Name')
                    ->sortable()
                    ->wrap()
                    ->formatStateUsing(function ($state, $record) {
                        $displayName = $state ?? $record->lead?->name ?? 'N/A';
                        $leadId = $record->lead_id;

                        if ($leadId) {
                            $encryptedId = \App\Classes\Encryptor::encrypt($leadId);
                            return new HtmlString('<a href="' . url('admin/leads/' . $encryptedId) . '"
                                    target="_blank"
                                    title="' . e($displayName) . '"
                                    class="inline-block"
                                    style="color:#338cf0;">
                                    ' . $displayName . '
                                </a>');
                        }

                        return $displayName;
                    })
                    ->html(),

                TextColumn::make('reseller.company_name')
                    ->label('Reseller Name')
                    ->sortable()
                    ->default('N/A'),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('view')
                        ->label('View Details')
                        ->icon('heroicon-o-eye')
                        ->color('secondary')
                        ->modalHeading(false)
                        ->modalWidth('4xl')
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        ->modalContent(function (FinanceHandover $record): View {
                            return view('components.finance-handover-details', [
                                'record' => $record,
                            ]);
                        }),

                    Action::make('viewReason')
                        ->label('View Rejected Reason')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn (FinanceHandover $record): bool => $record->status === 'Rejected')
                        ->modalHeading('Rejected Reason')
                        ->modalWidth('md')
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Close')
                        ->modalContent(function (FinanceHandover $record): HtmlString {
                            $reason = $record->remarks ?? 'No reason provided';
                            return new HtmlString('
                                <div style="padding: 1rem; border-radius: 0.5rem; background-color: #fef2f2; border-left: 4px solid #dc2626;">
                                    <p style="color: #991b1b; font-weight: 500; margin: 0;">' . e($reason) . '</p>
                                </div>
                            ');
                        }),

                    Action::make('convert_to_draft')
                        ->label('Convert to Draft')
                        ->icon('heroicon-o-document')
                        ->color('warning')
                        ->visible(fn (FinanceHandover $record): bool => $record->status === 'Rejected')
                        ->requiresConfirmation()
                        ->modalHeading('Convert to Draft')
                        ->modalDescription('Are you sure you want to convert this finance handover back to draft? This will allow editing.')
                        ->action(function (FinanceHandover $record): void {
                            $record->update(['status' => 'Draft']);

                            Notification::make()
                                ->title('Finance handover converted to draft')
                                ->success()
                                ->send();
                        }),

                    Action::make('edit_finance_handover')
                        ->modalHeading(fn (FinanceHandover $record): string => "Edit Finance Handover {$record->formatted_handover_id}")
                        ->label('Edit Finance Handover')
                        ->icon('heroicon-o-pencil')
                        ->color('warning')
                        ->modalSubmitActionLabel('Save & Submit')
                        ->visible(fn (FinanceHandover $record): bool => $record->status === 'Draft')
                        ->modalWidth(MaxWidth::FourExtraLarge)
                        ->slideOver()
                        ->fillForm(fn (FinanceHandover $record) => [
                            'related_hardware_handovers' => $this->ensureArray($record->related_hardware_handovers),
                            'reseller_id' => $record->reseller_id,
                            'reseller_invoice_number' => $record->reseller_invoice_number,
                            'pic_name' => $record->pic_name,
                            'pic_phone' => $record->pic_phone,
                            'pic_email' => $record->pic_email,
                            'payment_method' => $record->payment_method,
                            'quotation_by_reseller' => $this->ensureArray($record->quotation_by_reseller),
                            'invoice_by_reseller' => $this->ensureArray($record->invoice_by_reseller),
                            'invoice_by_customer' => $this->ensureArray($record->invoice_by_customer),
                            'payment_by_customer' => $this->ensureArray($record->payment_by_customer),
                            'product_quotation' => $this->ensureArray($record->product_quotation),
                        ])
                        ->form($this->getEditForm())
                        ->action(function (FinanceHandover $record, array $data): void {
                            $installationPaymentId = $data['installation_payment_id'] ?? null;

                            $data['status'] = 'New';

                            if (($data['payment_method'] ?? null) !== 'hrdf') {
                                $data['product_quotation'] = null;
                            }

                            $record->update($data);

                            if ($installationPaymentId) {
                                $payment = ResellerInstallationPayment::find($installationPaymentId);
                                if ($payment) {
                                    $payment->update([
                                        'status' => 'completed',
                                        'completed_at' => now(),
                                        'finance_handover_id' => $record->formatted_id,
                                    ]);
                                }
                            }

                            Notification::make()
                                ->title('Finance Handover Updated & Submitted')
                                ->success()
                                ->send();
                        }),
                ])->button()
                ->label('Actions')
                ->color('primary'),
            ])
            ->defaultSort('submitted_at', 'desc');
    }

    private function ensureArray($value): array
    {
        if (is_null($value)) return [];
        if (is_array($value)) return $value;
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    }

    public function render()
    {
        return view('livewire.salesperson-dashboard.finance-handover-rejected');
    }
}
