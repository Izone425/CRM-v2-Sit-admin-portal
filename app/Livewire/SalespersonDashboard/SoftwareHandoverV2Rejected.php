<?php

namespace App\Livewire\SalespersonDashboard;

use App\Classes\Encryptor;
use App\Filament\Filters\SortFilter;
use App\Http\Controllers\GenerateSoftwareHandoverPdfController;
use App\Models\CompanyDetail;
use App\Models\Lead;
use App\Models\SoftwareHandover;
use App\Models\User;
use App\Services\CategoryService;
use Filament\Forms;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\Filter;
use Illuminate\Contracts\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\Builder as DatabaseQueryBuilder;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Illuminate\Database\Eloquent\Builder;

class SoftwareHandoverV2Rejected extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected static ?int $indexRepeater = 0;
    protected static ?int $indexRepeater2 = 0;

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

    #[On('refresh-softwarehandover-tables')]
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

    public function getPendingKickOffs()
    {
        $this->selectedUser = $this->selectedUser ?? session('selectedUser') ?? auth()->id();

        $query = SoftwareHandover::query();
        $query->where('hr_version', 2);
        $query->whereIn('status', ['Rejected']);

        // Apply normal salesperson filtering for other roles
        if ($this->selectedUser === 'all-salespersons') {
            // Keep as is - show all salespersons' handovers
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
                // Salespersons (role_id 2) can see Draft, New, Approved, and Completed
                $query->whereIn('status', ['Rejected']);

                // But only THEIR OWN records
                $userId = auth()->id();
                $query->whereHas('lead', function ($leadQuery) use ($userId) {
                    $leadQuery->where('salesperson', $userId);
                });
            } else {
                // Other users (admin, managers) can only see New, Approved, and Completed
                $query->whereIn('status', ['Rejected']);
                // But they can see ALL records
            }
        }

        $query->orderByRaw("CASE
            WHEN status = 'New' THEN 1
            WHEN status = 'Approved' THEN 2
            WHEN status = 'Rejected' THEN 3
            ELSE 4
        END")
            ->orderBy('updated_at', 'desc');

        return $query;
    }

    public function getEditForm(): array
    {
        return [
            Forms\Components\ToggleButtons::make('hr_version')
                ->label('Select HR Version')
                ->options([
                    '1' => 'HR Version 1',
                    '2' => 'HR Version 2',
                ])
                ->default('1')
                ->inline()
                ->required()
                ->live()
                ->visible(fn () => auth()->user()->role_id === 3),

            Section::make('Step 1: Database Details')
                ->schema([
                    Grid::make(3)
                        ->schema([
                            TextInput::make('company_name')
                                ->label('Company Name')
                                ->hidden()
                                ->dehydrated(true)
                                ->default(fn (?SoftwareHandover $record = null) =>
                                    $record?->company_name ?? $record?->lead?->companyDetail?->company_name ?? null),
                            TextInput::make('pic_name')
                                ->label('Name')
                                ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                ->afterStateHydrated(fn($state) => Str::upper($state))
                                ->afterStateUpdated(fn($state) => Str::upper($state))
                                ->default(fn (?SoftwareHandover $record = null) =>
                                    $record?->pic_name ?? $record?->lead?->companyDetail?->name ?? $record?->lead?->name),
                            TextInput::make('pic_phone')
                                ->label('HP Number')
                                ->default(fn (?SoftwareHandover $record = null) =>
                                    $record?->pic_phone ?? $record?->lead?->companyDetail?->contact_no ?? $record?->lead?->phone),
                        ]),
                    Grid::make(3)
                        ->schema([
                            TextInput::make('salesperson')
                                ->readOnly()
                                ->dehydrated(true)
                                ->label('Salesperson')
                                ->default(fn (?SoftwareHandover $record = null) =>
                                    $record?->salesperson ?? ($record?->lead?->salesperson ? User::find($record->lead->salesperson)->name : null))
                                ->hidden(),

                            TextInput::make('headcount')
                                ->numeric()
                                ->live(debounce: 550)
                                ->afterStateUpdated(function (Forms\Set $set, ?string $state, CategoryService $category) {
                                    /**
                                    * set this company's category based on head count
                                    */
                                    $set('category', $category->retrieve($state));
                                })
                                ->required()
                                ->disabled()
                                ->dehydrated(true)
                                ->default(fn (?SoftwareHandover $record = null) => $record?->headcount ?? null)
                                ->hidden(),

                            TextInput::make('category')
                                ->label('Company Size')
                                ->dehydrated(false)
                                ->autocapitalize()
                                ->placeholder('Select a category')
                                ->default(function (?SoftwareHandover $record = null, CategoryService $category = null) {
                                    // If record exists with headcount, calculate category from headcount
                                    if ($record && $record->headcount && $category) {
                                        return $category->retrieve($record->headcount);
                                    }
                                    // If record has a saved category, use that
                                    if ($record && $record->category) {
                                        return $record->category;
                                    }
                                    return null;
                                })
                                ->readOnly()
                                ->hidden(),
                        ]),
                ]),

            Section::make('Step 2: Invoice Details')
                ->schema([
                    Grid::make(1)
                        ->schema([
                            Forms\Components\Actions::make([
                                Forms\Components\Actions\Action::make('export_invoice_info')
                                    ->label('Export AutoCount Debtor')
                                    ->color('success')
                                    ->icon('heroicon-o-document-arrow-down')
                                    ->url(function (?SoftwareHandover $record = null) {
                                        $leadId = $record?->lead_id;
                                        if (!$leadId) {
                                            return '#';
                                        }
                                        return route('software-handover.export-customer', ['lead' => Encryptor::encrypt($leadId)]);
                                    })
                                    ->openUrlInNewTab(),
                            ])
                            ->extraAttributes(['class' => 'space-y-2']),
                        ]),
                ]),

            Section::make('Step 3: Implementation Details')
                ->schema([
                    Forms\Components\Repeater::make('implementation_pics')
                        ->hiddenLabel(true)
                        ->schema([
                            Grid::make(4)
                            ->schema([
                                TextInput::make('pic_name_impl')
                                    ->required()
                                    ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                    ->afterStateHydrated(fn($state) => Str::upper($state))
                                    ->afterStateUpdated(fn($state) => Str::upper($state))
                                    ->label('Name'),
                                TextInput::make('position')
                                    ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                    ->afterStateHydrated(fn($state) => Str::upper($state))
                                    ->afterStateUpdated(fn($state) => Str::upper($state))
                                    ->label('Position'),
                                TextInput::make('pic_phone_impl')
                                    ->required()
                                    ->label('HP Number'),
                                TextInput::make('pic_email_impl')
                                    ->label('Email Address')
                                    ->required()
                                    ->email()
                                    ->extraAlpineAttributes([
                                        'x-on:input' => '
                                            const start = $el.selectionStart;
                                            const end = $el.selectionEnd;
                                            const value = $el.value;
                                            $el.value = value.toLowerCase();
                                            $el.setSelectionRange(start, end);
                                        '
                                    ])
                                    ->dehydrateStateUsing(fn ($state) => strtolower($state)),
                            ]),
                        ])
                        ->addActionLabel('Add PIC')
                        ->minItems(1)
                        ->itemLabel(fn() => __('Person In Charge') . ' ' . ++self::$indexRepeater)
                        ->columns(2)
                        // Add default implementation PICs from lead data or existing record
                        ->default(function (?SoftwareHandover $record = null) {
                            if ($record && $record->implementation_pics) {
                                // If it's a string, decode it
                                if (is_string($record->implementation_pics)) {
                                    return json_decode($record->implementation_pics, true);
                                }
                                // If it's already an array, return it
                                if (is_array($record->implementation_pics)) {
                                    return $record->implementation_pics;
                                }
                            }

                            // If no record, use lead data as default
                            $lead = $record?->lead;
                            if (!$lead) {
                                return [
                                    [
                                        'pic_name_impl' => '',
                                        'position' => '',
                                        'pic_phone_impl' => '',
                                        'pic_email_impl' => '',
                                    ],
                                ];
                            }
                            return [
                                [
                                    'pic_name_impl' => $lead->companyDetail->name ?? $lead->name ?? '',
                                    'position' => $lead->companyDetail->position ?? '',
                                    'pic_phone_impl' => $lead->companyDetail->contact_no ?? $lead->phone ?? '',
                                    'pic_email_impl' => $lead->companyDetail->email ?? $lead->email ?? '',
                                ],
                            ];
                        }),
                ]),

            Section::make('Step 4: Remark Details')
                ->schema([
                    Grid::make(1)
                        ->schema([
                            Textarea::make('remarks')
                                ->label('Remarks')
                                ->placeholder('Write Remarks')
                                ->rows(3)
                                ->maxLength(5000)
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
                                ->default(fn (?SoftwareHandover $record = null) => $record?->remarks),
                        ])
                ]),

            Grid::make(2)
            ->schema([
                Section::make('Step 5: Training Category')
                ->schema([
                    Forms\Components\Radio::make('training_type')
                        ->label('')
                        ->options([
                            'online_webinar_training' => 'Online Webinar Training',
                            'online_hrdf_training' => 'Online HRDF Training',
                        ])
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            // Clear proforma invoice fields when training category changes
                            $set('product_pi', null);
                            $set('non_hrdf_inv', null);
                            $set('hrdf_inv', null);
                            $set('sw_pi', null);
                        })
                        ->default(fn (?SoftwareHandover $record = null) => $record?->training_type ?? null),
                ])->columnSpan(1),

                Section::make('Step 6: Speaker Category')
                    ->schema([
                        Forms\Components\Radio::make('speaker_category')
                            ->label('')
                            ->options([
                                'english / malay' => 'English / Malay',
                                'mandarin' => 'Mandarin',
                            ])
                            ->live() // Make it react to headcount changes
                            ->afterStateHydrated(function (Forms\Set $set, Forms\Get $get, $state) {
                                $headcount = (int)$get('headcount');

                                // If headcount <= 25 and value is mandarin, reset to english/malay
                                if ($headcount <= 25 && $state === 'mandarin') {
                                    $set('speaker_category', 'english / malay');
                                }
                            })
                            ->required()
                            ->default(fn (?SoftwareHandover $record = null) => $record?->speaker_category ?? null),
                    ])->columnSpan(1),
            ]),

            Section::make('Step 7: Proforma Invoice')
                ->columnSpan(1) // Ensure it spans one column
                ->schema([
                    Grid::make(4)
                        ->schema([
                            Select::make('proforma_invoice_product')
                                ->label('Software + Hardware')
                                ->required(fn (callable $get) => $get('training_type') === 'online_webinar_training')
                                ->options(function (?SoftwareHandover $record = null) {
                                    $leadId = null;
                                    $currentRecordId = null;

                                    if ($record) {
                                        $leadId = $record->lead_id;
                                        $currentRecordId = $record->id;
                                    }

                                    if (!$leadId) {
                                        return [];
                                    }

                                    $usedPiIds = [];
                                    $softwareHandovers = SoftwareHandover::where('lead_id', $leadId)
                                        ->when($currentRecordId, function ($query) use ($currentRecordId) {
                                            return $query->where('id', '!=', $currentRecordId);
                                        })
                                        ->get();

                                    foreach ($softwareHandovers as $handover) {
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

                                    // Apply the module checking filter
                                    $availableQuotations = \App\Models\Quotation::where('lead_id', $leadId)
                                        ->where('quotation_type', 'product')
                                        ->where('status', \App\Enums\QuotationStatusEnum::accepted)
                                        ->whereNotIn('id', array_filter($usedPiIds))
                                        ->where('quotation_date', '>=', now()->toDateString());

                                    // Filter quotations that contain the required module products
                                    $moduleProductIds = [31, 118, 114, 108, 60, 38, 119, 115, 109, 60, 39, 120, 116, 110, 60, 40, 121, 117, 111, 60, 59, 41, 112, 93, 113, 42];

                                    $availableQuotations = $availableQuotations->whereHas('items', function ($query) use ($moduleProductIds) {
                                        $query->whereIn('product_id', $moduleProductIds);
                                    });

                                    $options = [];
                                    foreach ($availableQuotations->with(['subsidiary', 'lead.companyDetail'])->get() as $quotation) {
                                        $companyName = 'N/A';
                                        if ($quotation->subsidiary_id && $quotation->subsidiary) {
                                            $companyName = $quotation->subsidiary->company_name;
                                        } elseif ($quotation->lead && $quotation->lead->companyDetail) {
                                            $companyName = $quotation->lead->companyDetail->company_name;
                                        }
                                        $options[$quotation->id] = $quotation->pi_reference_no . ' - ' . $companyName;
                                    }
                                    return $options;
                                })
                                ->multiple()
                                ->searchable()
                                ->preload()
                                ->live()
                                ->afterStateUpdated(function (Forms\Set $set, ?array $state, CategoryService $category) {
                                    if (empty($state)) {
                                        return;
                                    }
                                    $highestQuantity = \App\Models\QuotationDetail::whereIn('quotation_id', $state)
                                        ->max('quantity');
                                    if ($highestQuantity) {
                                        $set('headcount', $highestQuantity);
                                        $set('category', $category->retrieve($highestQuantity));
                                    }
                                })
                                ->visible(fn (callable $get) => $get('training_type') === 'online_webinar_training')
                                ->default(function (?SoftwareHandover $record = null) {
                                    if (!$record || !$record->proforma_invoice_product) {
                                        return [];
                                    }
                                    if (is_string($record->proforma_invoice_product)) {
                                        return json_decode($record->proforma_invoice_product, true) ?? [];
                                    }
                                    return is_array($record->proforma_invoice_product) ? $record->proforma_invoice_product : [];
                                }),

                            // Software + Hardware PI - visible only for Online HRDF Training
                            Select::make('software_hardware_pi')
                                ->required(fn (callable $get) => $get('training_type') === 'online_hrdf_training')
                                ->label('Software + Hardware')
                                ->options(function (?SoftwareHandover $record = null) {
                                    $leadId = $record?->lead_id;
                                    $currentRecordId = $record?->id;

                                    if (!$leadId) {
                                        return [];
                                    }

                                    $usedPiIds = [];
                                    $softwareHandovers = SoftwareHandover::where('lead_id', $leadId)
                                        ->when($currentRecordId, function ($query) use ($currentRecordId) {
                                            return $query->where('id', '!=', $currentRecordId);
                                        })
                                        ->get();

                                    foreach ($softwareHandovers as $handover) {
                                        $fields = ['proforma_invoice_product', 'software_hardware_pi', 'non_hrdf_pi'];

                                        foreach ($fields as $field) {
                                            $piData = $handover->$field;
                                            if (!empty($piData)) {
                                                if (is_string($piData)) {
                                                    $piIds = json_decode($piData, true);
                                                    if (is_array($piIds)) {
                                                        $usedPiIds = array_merge($usedPiIds, $piIds);
                                                    }
                                                } elseif (is_array($piData)) {
                                                    $usedPiIds = array_merge($usedPiIds, $piData);
                                                }
                                            }
                                        }
                                    }

                                    $availableQuotations = \App\Models\Quotation::where('lead_id', $leadId)
                                        ->where('quotation_type', 'product')
                                        ->where('status', \App\Enums\QuotationStatusEnum::accepted)
                                        ->whereNotIn('id', array_filter($usedPiIds))
                                        ->where('quotation_date', '>=', now()->toDateString())
                                        // Exclude quotations that contain product ID 94
                                        ->whereDoesntHave('items', function ($query) {
                                            $query->where('product_id', 94);
                                        })
                                        ->with(['subsidiary', 'lead.companyDetail'])
                                        ->get();

                                    $options = [];
                                    foreach ($availableQuotations as $quotation) {
                                        $companyName = 'N/A';
                                        if ($quotation->subsidiary_id && $quotation->subsidiary) {
                                            $companyName = $quotation->subsidiary->company_name;
                                        } elseif ($quotation->lead && $quotation->lead->companyDetail) {
                                            $companyName = $quotation->lead->companyDetail->company_name;
                                        }
                                        $options[$quotation->id] = $quotation->pi_reference_no . ' - ' . $companyName;
                                    }
                                    return $options;
                                })
                                ->multiple()
                                ->searchable()
                                ->preload()
                                ->live() // Add live to trigger updates
                                ->afterStateUpdated(function (Forms\Set $set, ?array $state, CategoryService $category) {
                                    if (empty($state)) {
                                        return;
                                    }

                                    // Get the highest quantity from selected quotations
                                    $highestQuantity = \App\Models\QuotationDetail::whereIn('quotation_id', $state)
                                        ->max('quantity');

                                    if ($highestQuantity) {
                                        $set('headcount', $highestQuantity);
                                        // Also update the category based on the new headcount
                                        $set('category', $category->retrieve($highestQuantity));
                                    }
                                })
                                ->visible(fn (callable $get) => $get('training_type') === 'online_hrdf_training')
                                ->default(function (?SoftwareHandover $record = null) {
                                    if (!$record || !$record->software_hardware_pi) {
                                        return [];
                                    }
                                    if (is_string($record->software_hardware_pi)) {
                                        return json_decode($record->software_hardware_pi, true) ?? [];
                                    }
                                    return is_array($record->software_hardware_pi) ? $record->software_hardware_pi : [];
                                }),

                            // Non-HRDF PI - visible only for Online HRDF Training
                            Select::make('non_hrdf_pi')
                                ->label('Non-HRDF Invoice')
                                ->options(function (?SoftwareHandover $record = null) {
                                    $leadId = $record?->lead_id;
                                    $currentRecordId = $record?->id;

                                    if (!$leadId) {
                                        return [];
                                    }

                                    $usedPiIds = [];
                                    $softwareHandovers = SoftwareHandover::where('lead_id', $leadId)
                                        ->when($currentRecordId, function ($query) use ($currentRecordId) {
                                            return $query->where('id', '!=', $currentRecordId);
                                        })
                                        ->get();

                                    foreach ($softwareHandovers as $handover) {
                                        $fields = ['proforma_invoice_product', 'software_hardware_pi', 'non_hrdf_pi'];

                                        foreach ($fields as $field) {
                                            $piData = $handover->$field;
                                            if (!empty($piData)) {
                                                if (is_string($piData)) {
                                                    $piIds = json_decode($piData, true);
                                                    if (is_array($piIds)) {
                                                        $usedPiIds = array_merge($usedPiIds, $piIds);
                                                    }
                                                } elseif (is_array($piData)) {
                                                    $usedPiIds = array_merge($usedPiIds, $piData);
                                                }
                                            }
                                        }
                                    }

                                    $availableQuotations = \App\Models\Quotation::where('lead_id', $leadId)
                                        ->where('quotation_type', 'product')
                                        ->where('status', \App\Enums\QuotationStatusEnum::accepted)
                                        ->whereNotIn('id', array_filter($usedPiIds))
                                        ->where('quotation_date', '>=', now()->toDateString())
                                        // Only show quotations that contain product ID 94
                                        ->whereHas('items', function ($query) {
                                            $query->where('product_id', 94);
                                        })
                                        // Exclude quotations that have any product IDs other than 94
                                        ->whereDoesntHave('items', function ($query) {
                                            $query->where('product_id', '!=', 94);
                                        })
                                        ->with(['subsidiary', 'lead.companyDetail'])
                                        ->get();

                                    $options = [];
                                    foreach ($availableQuotations as $quotation) {
                                        $companyName = 'N/A';
                                        if ($quotation->subsidiary_id && $quotation->subsidiary) {
                                            $companyName = $quotation->subsidiary->company_name;
                                        } elseif ($quotation->lead && $quotation->lead->companyDetail) {
                                            $companyName = $quotation->lead->companyDetail->company_name;
                                        }
                                        $options[$quotation->id] = $quotation->pi_reference_no . ' - ' . $companyName;
                                    }
                                    return $options;
                                })
                                ->multiple()
                                ->searchable()
                                ->preload()
                                ->visible(fn (callable $get) => $get('training_type') === 'online_hrdf_training')
                                ->default(function (?SoftwareHandover $record = null) {
                                    if (!$record || !$record->non_hrdf_pi) {
                                        return [];
                                    }
                                    if (is_string($record->non_hrdf_pi)) {
                                        return json_decode($record->non_hrdf_pi, true) ?? [];
                                    }
                                    return is_array($record->non_hrdf_pi) ? $record->non_hrdf_pi : [];
                                }),

                            Select::make('proforma_invoice_hrdf')
                                ->label('HRDF Invoice')
                                ->required(fn (callable $get) => $get('training_type') === 'online_hrdf_training')
                                ->visible(fn (callable $get) => $get('training_type') === 'online_hrdf_training')
                                ->options(function (?SoftwareHandover $record = null) {
                                    $leadId = $record?->lead_id;
                                    $currentRecordId = $record?->id;

                                    if (!$leadId) {
                                        return [];
                                    }

                                    // Get all PI IDs already used in other software handovers for this lead
                                    $usedPiIds = [];
                                    $softwareHandovers = SoftwareHandover::where('lead_id', $leadId)
                                        ->when($currentRecordId, function ($query) use ($currentRecordId) {
                                            // Exclude current record if we're editing
                                            return $query->where('id', '!=', $currentRecordId);
                                        })
                                        ->get();

                                    // Extract used HRDF PI IDs from all handovers
                                    foreach ($softwareHandovers as $handover) {
                                        $piHrdf = $handover->proforma_invoice_hrdf;
                                        if (!empty($piHrdf)) {
                                            // Handle JSON string format
                                            if (is_string($piHrdf)) {
                                                $piIds = json_decode($piHrdf, true);
                                                if (is_array($piIds)) {
                                                    $usedPiIds = array_merge($usedPiIds, $piIds);
                                                }
                                            }
                                            // Handle array format
                                            elseif (is_array($piHrdf)) {
                                                $usedPiIds = array_merge($usedPiIds, $piHrdf);
                                            }
                                        }
                                    }

                                    // Get available HRDF PIs excluding already used ones
                                    $availableQuotations = \App\Models\Quotation::where('lead_id', $leadId)
                                        ->where('quotation_type', 'hrdf')
                                        ->where('status', \App\Enums\QuotationStatusEnum::accepted)
                                        ->whereNotIn('id', array_filter($usedPiIds)) // Filter out null/empty values
                                        ->where('quotation_date', '>=', now()->toDateString())
                                        ->with(['subsidiary', 'lead.companyDetail'])
                                        ->get();

                                    $options = [];
                                    foreach ($availableQuotations as $quotation) {
                                        $companyName = 'N/A';
                                        if ($quotation->subsidiary_id && $quotation->subsidiary) {
                                            $companyName = $quotation->subsidiary->company_name;
                                        } elseif ($quotation->lead && $quotation->lead->companyDetail) {
                                            $companyName = $quotation->lead->companyDetail->company_name;
                                        }
                                        $options[$quotation->id] = $quotation->pi_reference_no . ' - ' . $companyName;
                                    }
                                    return $options;
                                })
                                ->multiple()
                                ->searchable()
                                ->preload()
                                ->live()
                                ->afterStateUpdated(function (callable $set, callable $get, ?array $state) {
                                    $this->updateHrdfGrantIdRepeater($set, $get, $state);
                                })
                                ->default(function (?SoftwareHandover $record = null) {
                                    if (!$record || !$record->proforma_invoice_hrdf) {
                                        return [];
                                    }
                                    if (is_string($record->proforma_invoice_hrdf)) {
                                        return json_decode($record->proforma_invoice_hrdf, true) ?? [];
                                    }
                                    return is_array($record->proforma_invoice_hrdf) ? $record->proforma_invoice_hrdf : [];
                                }),

                            // HRDF Grant IDs Repeater - dynamically shown based on selected HRDF invoices
                                Repeater::make('hrdf_grant_ids')
                                    ->label('HRDF Grant IDs')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('proforma_invoice_name')
                                                    ->label('Proforma Invoice')
                                                    ->disabled()
                                                    ->dehydrated(false),
                                                TextInput::make('hrdf_grant_id')
                                                    ->label('HRDF Grant ID')
                                                    ->placeholder('Enter HRDF Grant ID')
                                                    ->required()
                                                    ->live(debounce: 500)
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
                                                    ->rules([
                                                        function () {
                                                            return function (string $attribute, $value, \Closure $fail) {
                                                                if (empty($value)) {
                                                                    return;
                                                                }

                                                                $hrdfClaim = \App\Models\HrdfClaim::where('hrdf_grant_id', $value)->first();

                                                                if (!$hrdfClaim) {
                                                                    $fail('HRDF Grant ID not found in HRDF Claims.');
                                                                    return;
                                                                }

                                                                // Check if required fields have values
                                                                $requiredFields = [
                                                                    'invoice_amount' => 'Invoice Amount',
                                                                    // 'upfront_payment' => 'Upfront Payment',
                                                                    'pax' => 'Pax'
                                                                ];

                                                                $missingFields = [];
                                                                foreach ($requiredFields as $field => $label) {
                                                                    if (empty($hrdfClaim->$field) || (is_numeric($hrdfClaim->$field) && $hrdfClaim->$field <= 0)) {
                                                                        $missingFields[] = $label;
                                                                    }
                                                                }

                                                                if (!empty($missingFields)) {
                                                                    $fail('HRDF Grant ID is missing required data: ' . implode(', ', $missingFields));
                                                                }
                                                            };
                                                        },
                                                    ])
                                            ])
                                    ])
                                    ->addable(false)
                                    ->deletable(false)
                                    ->reorderable(false)
                                    ->visible(fn (callable $get) => $get('training_type') === 'online_hrdf_training' && !empty($get('proforma_invoice_hrdf')))
                                    ->live()
                                    ->afterStateHydrated(function (callable $set, callable $get, ?array $state) {
                                        $this->updateHrdfGrantIdRepeater($set, $get, $state);
                                    })
                                    ->columnSpanFull()
                                    ->default(function (?SoftwareHandover $record = null) {
                                        if (!$record) return [];

                                        // If record has hrdf_grant_id (old single field), convert to array format
                                        if (!empty($record->hrdf_grant_id) && empty($record->hrdf_grant_ids)) {
                                            return [['hrdf_grant_id' => $record->hrdf_grant_id]];
                                        }

                                        // If record has the new hrdf_grant_ids field
                                        if (!empty($record->hrdf_grant_ids)) {
                                            if (is_string($record->hrdf_grant_ids)) {
                                                return json_decode($record->hrdf_grant_ids, true) ?? [];
                                            }
                                            return is_array($record->hrdf_grant_ids) ? $record->hrdf_grant_ids : [];
                                        }

                                        return [];
                                    }),
                        ])
                ]),

            Section::make('Step 8: Attachment')
                ->columnSpan(1) // Ensure it spans one column
                ->schema([
                    Grid::make(3)
                        ->schema([
                        FileUpload::make('confirmation_order_file')
                            ->label('Upload Confirmation Order')
                            ->disk('public')
                            ->directory('handovers/confirmation_orders')
                            ->visibility('public')
                            ->multiple()
                            ->maxFiles(1)
                            ->openable()
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                            ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, callable $get, ?SoftwareHandover $record = null): string {
                                // Get lead ID from record
                                $leadId = $record?->lead_id;
                                // Use standardized format matching SoftwareHandover accessor
                                $formattedId = SoftwareHandover::generateFormattedId($leadId);
                                // Get extension
                                $extension = $file->getClientOriginalExtension();

                                // Generate a unique identifier (timestamp) to avoid overwriting files
                                $timestamp = now()->format('YmdHis');
                                $random = rand(1000, 9999);

                                return "{$formattedId}-CONFIRM-{$timestamp}-{$random}.{$extension}";
                            })
                            ->default(function (?SoftwareHandover $record = null) {
                                if (!$record || !$record->confirmation_order_file) {
                                    return [];
                                }
                                if (is_string($record->confirmation_order_file)) {
                                    return json_decode($record->confirmation_order_file, true) ?? [];
                                }
                                return is_array($record->confirmation_order_file) ? $record->confirmation_order_file : [];
                            }),

                        FileUpload::make('payment_slip_file')
                            ->label('Upload Payment Slip')
                            ->disk('public')
                            ->live(debounce:500)
                            ->directory('handovers/payment_slips')
                            ->visibility('public')
                            ->multiple()
                            ->maxFiles(1)
                            ->openable()
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                            ->openable()
                            ->required(function (Get $get) {
                                // Check if HRDF grant has actual files
                                $hrdfGrantFiles = $get('hrdf_grant_file');
                                $hasHrdfGrant = is_array($hrdfGrantFiles) && count($hrdfGrantFiles) > 0 && !empty(array_filter($hrdfGrantFiles));

                                // Only required if HRDF grant is empty
                                return !$hasHrdfGrant;
                            })
                            ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, callable $get, ?SoftwareHandover $record = null): string {
                                // Get lead ID from record
                                $leadId = $record?->lead_id;
                                // Use standardized format matching SoftwareHandover accessor
                                $formattedId = SoftwareHandover::generateFormattedId($leadId);
                                // Get extension
                                $extension = $file->getClientOriginalExtension();

                                // Generate a unique identifier (timestamp) to avoid overwriting files
                                $timestamp = now()->format('YmdHis');
                                $random = rand(1000, 9999);

                                return "{$formattedId}-PAYMENT-{$timestamp}-{$random}.{$extension}";
                            })
                            ->default(function (?SoftwareHandover $record = null) {
                                if (!$record || !$record->payment_slip_file) {
                                    return [];
                                }
                                if (is_string($record->payment_slip_file)) {
                                    return json_decode($record->payment_slip_file, true) ?? [];
                                }
                                return is_array($record->payment_slip_file) ? $record->payment_slip_file : [];
                            }),

                        FileUpload::make('hrdf_grant_file')
                            ->label('Upload HRDF Grant Approval Letter')
                            ->disk('public')
                            ->directory('handovers/hrdf_grant')
                            ->visibility('public')
                            ->multiple()
                            ->maxFiles(10)
                            ->openable()
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                            ->openable()
                            ->visible(fn (callable $get) => $get('training_type') === 'online_hrdf_training')
                            ->required(function (Get $get) {
                                // Check if payment slip has actual files
                                $paymentSlipFiles = $get('payment_slip_file');
                                $hasPaymentSlip = is_array($paymentSlipFiles) && count($paymentSlipFiles) > 0 && !empty(array_filter($paymentSlipFiles));

                                // Only required if payment slip is empty
                                return !$hasPaymentSlip;
                            })
                            ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, callable $get, ?SoftwareHandover $record = null): string {
                                // Get lead ID from record
                                $leadId = $record?->lead_id;
                                // Use standardized format matching SoftwareHandover accessor
                                $formattedId = SoftwareHandover::generateFormattedId($leadId);
                                // Get extension
                                $extension = $file->getClientOriginalExtension();

                                // Generate a unique identifier (timestamp) to avoid overwriting files
                                $timestamp = now()->format('YmdHis');
                                $random = rand(1000, 9999);

                                return "{$formattedId}-HRDF-{$timestamp}-{$random}.{$extension}";
                            })
                            ->afterStateUpdated(function () {
                                // Reset the counter after the upload is complete
                                session()->forget('hrdf_upload_count');
                            })
                            ->default(function (?SoftwareHandover $record = null) {
                                if (!$record || !$record->hrdf_grant_file) {
                                    return [];
                                }
                                if (is_string($record->hrdf_grant_file)) {
                                    return json_decode($record->hrdf_grant_file, true) ?? [];
                                }
                                return is_array($record->hrdf_grant_file) ? $record->hrdf_grant_file : [];
                            }),

                        FileUpload::make('invoice_file')
                            ->label('Upload Invoice TimeTec Penang')
                            ->disk('public')
                            ->directory('handovers/invoices')
                            ->visibility('public')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                            ->multiple()
                            ->maxFiles(10)
                            ->visible(fn () => in_array(auth()->id(), [1, 25]))
                            ->openable()
                            ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, callable $get): string {
                                $companyName = Str::slug($get('company_name') ?? 'invoice');
                                $date = now()->format('Y-m-d');
                                $random = Str::random(5);
                                $extension = $file->getClientOriginalExtension();

                                return "{$companyName}-invoice-{$date}-{$random}.{$extension}";
                            })
                            ->default(function (?SoftwareHandover $record = null) {
                                if (!$record || !$record->invoice_file) {
                                    return [];
                                }
                                if (is_string($record->invoice_file)) {
                                    return json_decode($record->invoice_file, true) ?? [];
                                }
                                return is_array($record->invoice_file) ? $record->invoice_file : [];
                            }),
                        ])
                ]),

            Section::make('Step 9: Renewal Note')
                ->columnSpan(1)
                ->schema([
                    Grid::make(1)
                        ->schema([
                            Textarea::make('renewal_note')
                                ->label('Renewal Note')
                                ->placeholder('Write Renewal Notes')
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
                                ->default(function (?SoftwareHandover $record = null) {
                                    if (!$record) {
                                        return null;
                                    }

                                    // Get the latest renewal note for this lead
                                    $latestNote = \App\Models\RenewalNote::where('lead_id', $record->lead_id)
                                        ->latest()
                                        ->first();

                                    return $latestNote?->content ?? null;
                                }),
                        ])
                ]),

            Section::make('Step 10: Invoice to Reseller')
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
                                ->default(function (?SoftwareHandover $record = null) {
                                    return $record?->reseller_id ?? null;
                                }),
                        ])
                ]),

            Section::make('Step 11: Implement By')
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
                                ->default(function (?SoftwareHandover $record = null) {
                                    return $record?->implement_by ?? null;
                                }),
                        ])
                ]),
        ];
    }

    private function updateHrdfGrantIdRepeater(callable $set, callable $get, ?array $state): void
    {
        $selectedHrdfInvoices = $get('proforma_invoice_hrdf') ?? [];

        if (empty($selectedHrdfInvoices)) {
            $set('hrdf_grant_ids', []);
            return;
        }

        // Get existing hrdf_grant_ids to preserve user input
        $existingGrantIds = $get('hrdf_grant_ids') ?? [];
        $existingGrantIdsMap = [];

        // Create a map of quotation_id to grant ID for preservation
        foreach ($existingGrantIds as $entry) {
            if (isset($entry['quotation_id']) && isset($entry['hrdf_grant_id'])) {
                $existingGrantIdsMap[$entry['quotation_id']] = $entry['hrdf_grant_id'];
            }
        }

        // Get the quotations for the selected HRDF invoices
        $quotations = \App\Models\Quotation::whereIn('id', $selectedHrdfInvoices)
            ->with(['subsidiary', 'lead.companyDetail'])
            ->get();

        $hrdfGrantEntries = [];
        foreach ($quotations as $quotation) {
            $companyName = 'N/A';
            if ($quotation->subsidiary_id && $quotation->subsidiary) {
                $companyName = $quotation->subsidiary->company_name;
            } elseif ($quotation->lead && $quotation->lead->companyDetail) {
                $companyName = $quotation->lead->companyDetail->company_name;
            }

            $piReference = $quotation->pi_reference_no . ' - ' . $companyName;

            $hrdfGrantEntries[] = [
                'quotation_id' => $quotation->id,
                'proforma_invoice_name' => $piReference,
                'hrdf_grant_id' => $existingGrantIdsMap[$quotation->id] ?? ''
            ];
        }

        $set('hrdf_grant_ids', $hrdfGrantEntries);
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('300s')
            ->query($this->getPendingKickOffs())
            // ->defaultSort('updated_at', 'desc')
            ->emptyState(fn() => view('components.empty-state-question'))
            ->defaultPaginationPageOption(5)
            ->paginated([5])
            ->filters([
                // Add this new filter for status
                SelectFilter::make('status')
                    ->label('Filter by Status')
                    ->options([
                        'New' => 'New',
                        'Rejected' => 'Rejected',
                        'Completed' => 'Completed',
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
                    ->multiple(),

                SelectFilter::make('implementer')
                    ->label('Filter by Implementer')
                    ->options(function () {
                        return User::whereIn('role_id', ['4', '5'])
                            ->pluck('name', 'name')
                            ->toArray();
                    })
                    ->placeholder('All Implementer')
                    ->multiple(),

                SortFilter::make("sort_by")
            ])
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->formatStateUsing(function ($state, SoftwareHandover $record) {
                        // If no state (ID) is provided, return a fallback
                        if (!$state) {
                            return 'Unknown';
                        }

                        // For handover_pdf, extract filename
                        if ($record->handover_pdf) {
                            // Extract just the filename without extension
                            $filename = basename($record->handover_pdf, '.pdf');
                            return $filename;
                        }


                        return $record->formatted_handover_id;
                    })
                    ->color('primary') // Makes it visually appear as a link
                    ->weight('bold')
                    ->action(
                        Action::make('viewHandoverDetails')
                            ->modalHeading(false)
                            ->modalWidth('4xl')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalContent(function (SoftwareHandover $record): View {
                                return view('components.software-handover')
                                    ->with('extraAttributes', ['record' => $record]);
                            })
                    ),

                TextColumn::make('salesperson')
                    ->label('SalesPerson')
                    ->visible(fn(): bool => auth()->user()->role_id !== 2),

                TextColumn::make('implementer')
                    ->label('Implementer')
                    ->visible(fn(): bool => auth()->user()->role_id !== 2),

                TextColumn::make('company_name')
                    ->label('Company Name')
                    ->searchable()
                    ->formatStateUsing(function ($state, $record) {
                        $company = CompanyDetail::where('company_name', $state)->first();

                        if (!empty($record->lead_id)) {
                            $company = CompanyDetail::where('lead_id', $record->lead_id)->first();
                        }

                        if ($company) {
                            $shortened = strtoupper(Str::limit($company->company_name, 20, '...'));
                            $encryptedId = \App\Classes\Encryptor::encrypt($company->lead_id);

                            return new HtmlString('<a href="' . url('admin/leads/' . $encryptedId) . '"
                                    target="_blank"
                                    title="' . e($state) . '"
                                    class="inline-block"
                                    style="color:#338cf0;">
                                    ' . $company->company_name . '
                                </a>');
                        }

                        $shortened = strtoupper(Str::limit($state, 20, '...'));
                        return "<span title='{$state}'>{$state}</span>";
                    })
                    ->html(),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('view')
                        ->label('View')
                        ->icon('heroicon-o-eye')
                        ->color('secondary')
                        ->modalHeading(false)
                        ->modalWidth('4xl')
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        ->modalContent(function (SoftwareHandover $record): View {
                            return view('components.software-handover')
                                ->with('extraAttributes', ['record' => $record]);
                        }),

                    Action::make('view_reason')
                        ->label('View Reason')
                        ->visible(fn (SoftwareHandover $record): bool => $record->status === 'Rejected')
                        ->icon('heroicon-o-magnifying-glass-plus')
                        ->modalHeading('Change Request Reason')
                        ->modalContent(fn (SoftwareHandover $record) => view('components.view-reason', [
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
                        ->visible(fn (SoftwareHandover $record): bool => $record->status === 'Rejected')
                        ->action(function (SoftwareHandover $record): void {
                            $record->update([
                                'status' => 'Draft'
                            ]);

                            Notification::make()
                                ->title('Handover converted to draft')
                                ->success()
                                ->send();
                        }),

                    Action::make('edit_software_handover')
                        ->modalHeading(fn (SoftwareHandover $record): string => "Edit Software Handover {$record->formatted_handover_id}")
                        ->label('Edit Software Handover')
                        ->icon('heroicon-o-pencil')
                        ->color('warning')
                        ->modalSubmitActionLabel('Save')
                        ->visible(fn (SoftwareHandover $record): bool => $record->status === 'Draft')
                        ->modalWidth(MaxWidth::FourExtraLarge)
                        ->slideOver()
                        ->form($this->getEditForm())
                        ->action(function (SoftwareHandover $record, array $data): void {
                            $renewalNote = $data['renewal_note'] ?? null;
                            unset($data['renewal_note']);

                            // Process JSON encoding for array fields
                            foreach (['confirmation_order_file', 'payment_slip_file', 'implementation_pics',
                                     'proforma_invoice_product', 'proforma_invoice_hrdf', 'invoice_file', 'hrdf_grant_file',
                                     'software_hardware_pi', 'non_hrdf_pi', 'hrdf_grant_ids'] as $field) {
                                if (isset($data[$field]) && is_array($data[$field])) {
                                    $data[$field] = json_encode($data[$field]);
                                }
                            }

                            // Update the record
                            $record->update($data);

                            if (!empty($renewalNote)) {
                                try {
                                    \App\Models\RenewalNote::create([
                                        'lead_id' => $record->lead_id,
                                        'user_id' => auth()->id(),
                                        'content' => strtoupper($renewalNote),
                                    ]);
                                } catch (\Exception $e) {
                                    Log::error('Failed to update renewal note', [
                                        'error' => $e->getMessage(),
                                        'lead_id' => $record->lead_id,
                                    ]);
                                }
                            }

                            // Generate PDF for non-draft handovers
                            if ($record->status !== 'Draft') {
                                app(\App\Http\Controllers\GenerateSoftwareHandoverPdfController::class)->generateInBackground($record);
                            }

                            Notification::make()
                                ->title('Software handover updated successfully')
                                ->success()
                                ->send();
                        }),
                ])->button()
                    ->color('warning')
            ]);
    }

    public function render()
    {
        return view('livewire.salesperson_dashboard.software-handover-v2-rejected');
    }
}
