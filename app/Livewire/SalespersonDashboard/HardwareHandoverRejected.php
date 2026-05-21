<?php

namespace App\Livewire\SalespersonDashboard;

use App\Classes\Encryptor;
use App\Filament\Filters\SortFilter;
use App\Http\Controllers\GenerateHardwareHandoverPdfController;
use App\Models\HardwareHandoverV2;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class HardwareHandoverRejected extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public $selectedUser;
    public $lastRefreshTime;
    public $currentDashboard;

    public function mount($currentDashboard = null)
    {
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
        $this->currentDashboard = $currentDashboard;
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

    #[On('refresh-hardwarehandover-tables')]
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

    public function getOverdueHardwareHandovers()
    {
        $this->selectedUser = $this->selectedUser ?? session('selectedUser') ?? auth()->id();

        $query = HardwareHandoverV2::query()
            ->whereIn('status', ['Rejected','Draft']);

        if ($this->selectedUser === 'all-salespersons') {
            // Show all salespersons' handovers
        } elseif (is_numeric($this->selectedUser)) {
            $userExists = User::where('id', $this->selectedUser)->where('role_id', 2)->exists();

            if ($userExists) {
                $selectedUser = $this->selectedUser;
                $query->whereHas('lead', function ($leadQuery) use ($selectedUser) {
                    $leadQuery->where('salesperson', $selectedUser);
                });
            } else {
                // role_id 3 (managers) see all records, role_id 2 see only their own
                if (auth()->user()->role_id === 2) {
                    $query->whereHas('lead', function ($leadQuery) {
                        $leadQuery->where('salesperson', auth()->id());
                    });
                }
            }
        } else {
            if (auth()->user()->role_id === 2) {
                $userId = auth()->id();
                $query->whereHas('lead', function ($leadQuery) use ($userId) {
                    $leadQuery->where('salesperson', $userId);
                });
            }
        }

        $query->orderBy('created_at', 'desc')
            ->with(['lead', 'lead.companyDetail', 'creator']);

        return $query;
    }

    public function getHardwareHandoverCount()
    {
        $this->selectedUser = $this->selectedUser ?? session('selectedUser') ?? auth()->id();

        $query = HardwareHandoverV2::query()
            ->whereIn('status', ['Rejected','Draft']);

        if ($this->selectedUser === 'all-salespersons') {
            // Show all salespersons' handovers
        } elseif (is_numeric($this->selectedUser)) {
            $userExists = User::where('id', $this->selectedUser)->where('role_id', 2)->exists();

            if ($userExists) {
                $selectedUser = $this->selectedUser;
                $query->whereHas('lead', function ($leadQuery) use ($selectedUser) {
                    $leadQuery->where('salesperson', $selectedUser);
                });
            } else {
                if (auth()->user()->role_id === 2) {
                    $query->whereHas('lead', function ($leadQuery) {
                        $leadQuery->where('salesperson', auth()->id());
                    });
                }
            }
        } else {
            if (auth()->user()->role_id === 2) {
                $userId = auth()->id();
                $query->whereHas('lead', function ($leadQuery) use ($userId) {
                    $leadQuery->where('salesperson', $userId);
                });
            }
        }

        return $query->count();
    }

    public function getEditForm(): array
    {
        return [
            Section::make('Step 1: Invoice Type')
                ->schema([
                    Forms\Components\Radio::make('invoice_type')
                        ->hiddenLabel()
                        ->options([
                            'single' => 'Single Invoice (Hardware Only)',
                            'combined' => 'Combined Invoice (Hardware + Software)',
                        ])
                        ->default(function (?HardwareHandoverV2 $record = null) {
                            return $record?->invoice_type ?? 'single';
                        })
                        ->reactive()
                        ->inline()
                        ->inlineLabel(false)
                        ->required(),

                    Forms\Components\Select::make('related_software_handovers')
                        ->label('Select Software Handovers to Combine With')
                        ->options(function (?HardwareHandoverV2 $record = null) {
                            $leadId = $record?->lead_id;
                            if (!$leadId) return [];
                            return \App\Models\SoftwareHandover::where('lead_id', $leadId)
                                ->orderBy('created_at', 'desc')
                                ->get()
                                ->mapWithKeys(function ($handover) {
                                    $id = $handover->id;
                                    $formattedId = $handover->formatted_handover_id;
                                    $date = $handover->created_at ? $handover->created_at->format('d M Y') : 'Unknown date';
                                    return [$id => "{$formattedId} - {$date}"];
                                })
                                ->toArray();
                        })
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->visible(fn (callable $get) => $get('invoice_type') === 'combined')
                        ->required(fn (callable $get) => $get('invoice_type') === 'combined')
                        ->default(function (?HardwareHandoverV2 $record = null) {
                            if (!$record || !$record->related_software_handovers) {
                                return [];
                            }

                            if (is_string($record->related_software_handovers)) {
                                return json_decode($record->related_software_handovers, true) ?? [];
                            }

                            return is_array($record->related_software_handovers) ? $record->related_software_handovers : [];
                        }),
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
                                    ->url(function (?HardwareHandoverV2 $record = null) {
                                        $leadId = $record?->lead_id;
                                        if (!$leadId) return '#';
                                        return route('software-handover.export-customer', ['lead' => Encryptor::encrypt($leadId)]);
                                    })
                                    ->openUrlInNewTab(),
                            ])
                                ->extraAttributes(['class' => 'space-y-2']),
                        ]),
                ]),
            Section::make('Step 3: Contact Detail')
                ->schema([
                    Forms\Components\Repeater::make('contact_detail')
                        ->label('Contact Detail')
                        ->hiddenLabel(true)
                        ->schema([
                            Grid::make(3)
                                ->schema([
                                    TextInput::make('pic_name')
                                        ->required()
                                        ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                        ->afterStateHydrated(fn($state) => Str::upper($state))
                                        ->afterStateUpdated(fn($state) => Str::upper($state))
                                        ->label('Name'),
                                    TextInput::make('pic_phone')
                                        ->required()
                                        ->numeric()
                                        ->label('HP Number'),
                                    TextInput::make('pic_email')
                                        ->required()
                                        ->label('Email Address')
                                        ->email(),
                                ]),
                        ])
                        ->itemLabel(function (array $state): ?string {
                            static $counter = 0;
                            $counter++;
                            return 'Contact Person ' . $counter;
                        })
                        ->default(function (?HardwareHandoverV2 $record = null) {
                            if (!$record) {
                                return null;
                            } elseif ($record && $record->contact_detail) {
                                return json_decode($record->contact_detail, true);
                            } else {
                                $lead = $record->lead;
                                if ($lead) {
                                    return [
                                        [
                                            'pic_name' => $lead->companyDetail->name ?? $lead->name,
                                            'pic_phone' => $lead->companyDetail->contact_no ?? $lead->phone,
                                            'pic_email' => $lead->companyDetail->email ?? $lead->email,
                                        ]
                                    ];
                                }
                                return null;
                            }
                        })
                ]),


            Section::make('Step 4: Category 1')
                ->schema([
                    Forms\Components\Radio::make('installation_type')
                        ->label('')
                        ->options([
                            'courier' => 'Courier',
                            'internal_installation' => 'Internal Installation',
                            'external_installation' => 'External Installation',
                            'self_pick_up' => 'Pick-Up',
                        ])
                        ->live(debounce: 500)
                        ->afterStateUpdated(function ($set, $state, ?HardwareHandoverV2 $record = null) {
                            if ($state === 'external_installation') {
                                $set('category2.pic_name', '');
                                $set('category2.pic_phone', '');
                                $set('category2.email', '');
                            } elseif ($state === 'courier') {
                                $lead = $record?->lead;
                                if ($lead) {
                                    $set('category2.pic_name', $lead->companyDetail->name ?? $lead->name);
                                    $set('category2.pic_phone', $lead->companyDetail->contact_no ?? $lead->contact_no);
                                    $set('category2.email', $lead->companyDetail->email ?? $lead->email);
                                }
                            }
                        })
                        ->columns(4)
                        ->default(fn(?HardwareHandoverV2 $record = null) => $record->installation_type ?? null)
                        ->required(),
                ]),

            Section::make('Step 5: Category 2')
                ->schema([
                    Forms\Components\Placeholder::make('installation_type_helper')
                        ->label('')
                        ->content('Please select any option Installation Type 1 at Step 4 to see the relevant fields.')
                        ->visible(fn(callable $get) => empty($get('installation_type')))
                        ->inlineLabel(),

                    Grid::make(1)
                        ->schema([
                            Select::make('category2.installer')
                                ->label('Installer')
                                ->visible(fn(callable $get) => $get('installation_type') === 'internal_installation')
                                ->required()
                                ->options(function () {
                                    return \App\Models\Installer::whereNotNull('company_name')->pluck('company_name', 'id')->toArray();
                                })
                                ->default(function (?HardwareHandoverV2 $record = null) {
                                    if ($record && $record->category2) {
                                        $category2 = is_string($record->category2) ? json_decode($record->category2, true) : $record->category2;
                                        if (isset($category2['installer']) && !empty($category2['installer'])) {
                                            return $category2['installer'];
                                        }
                                    }
                                    return null;
                                })
                                ->searchable()
                                ->preload(),
                            Select::make('category2.reseller')
                                ->label('Reseller')
                                ->visible(fn(callable $get) => $get('installation_type') === 'external_installation')
                                ->required()
                                ->options(function () {
                                    return \App\Models\Reseller::whereNotNull('company_name')->pluck('company_name', 'id')->toArray();
                                })
                                ->default(function (?HardwareHandoverV2 $record = null) {
                                    if ($record && $record->category2) {
                                        $category2 = is_string($record->category2) ? json_decode($record->category2, true) : $record->category2;
                                        if (isset($category2['reseller']) && !empty($category2['reseller'])) {
                                            return $category2['reseller'];
                                        }
                                    }
                                    return null;
                                })
                                ->searchable()
                                ->preload(),
                            Forms\Components\Repeater::make('category2.courier_addresses')
                                ->label('Courier Addresses')
                                ->schema([
                                    TextArea::make('address')
                                        ->label('ADDRESS:')
                                        ->required()
                                        ->rows(3)
                                        ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                        ->afterStateHydrated(fn($state) => Str::upper($state))
                                        ->afterStateUpdated(fn($state) => Str::upper($state))
                                        ->default("ADDRESS:\nDEVICE MODEL:\nTOTAL UNIT:"),
                                    ])
                                ->itemLabel(function (array $state): ?string {
                                    static $counter = 0;
                                    $counter++;
                                    return 'Courier Address ' . $counter;
                                })
                                ->addActionLabel('Add Another Address')
                                ->maxItems(10)
                                ->defaultItems(1)
                                ->default(function (?HardwareHandoverV2 $record = null) {
                                    // If editing existing record, return saved data
                                    if ($record && $record->category2) {
                                        $category2 = is_string($record->category2) ? json_decode($record->category2, true) : $record->category2;
                                        if (isset($category2['courier_addresses']) && !empty($category2['courier_addresses'])) {
                                            return $category2['courier_addresses'];
                                        }
                                    }

                                    // Default template for new records with pre-filled address
                                    $owner = $record?->lead;
                                    $defaultAddress = '';

                                    if ($owner && $owner->companyDetail) {
                                        $defaultAddress = $owner->companyDetail->company_address1 ?? '';
                                        if (!empty($owner->companyDetail->company_address2)) {
                                            $defaultAddress .= ", " . $owner->companyDetail->company_address2;
                                        }
                                        if (!empty($owner->companyDetail->postcode) || !empty($owner->companyDetail->state)) {
                                            $defaultAddress .= ", " .
                                                ($owner->companyDetail->postcode ?? '') . " " .
                                                ($owner->companyDetail->state ?? '');
                                        }
                                    } elseif ($owner) {
                                        $defaultAddress = $owner->address1 ?? '';
                                        if (!empty($owner->address2)) {
                                            $defaultAddress .= ", " . $owner->address2;
                                        }
                                        if (!empty($owner->postcode) || !empty($owner->state)) {
                                            $defaultAddress .= ", " .
                                                ($owner->postcode ?? '') . " " .
                                                ($owner->state ?? '');
                                        }
                                    }

                                    return [
                                        [
                                            'address' => "ADDRESS: " . strtoupper($defaultAddress) . "\nDEVICE MODEL:\nTOTAL UNIT:",
                                        ]
                                    ];
                                })
                                ->visible(fn(callable $get) => $get('installation_type') === 'courier')
                                ->columnSpanFull(),
                            TextArea::make('category2.pickup_address')
                                ->label('Pickup Address')
                                ->required()
                                ->rows(2)
                                ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                ->afterStateHydrated(fn($state) => Str::upper($state))
                                ->afterStateUpdated(fn($state) => Str::upper($state))
                                ->default(function (?HardwareHandoverV2 $record = null) {
                                    return 'TimeTec Cloud @ PFCC, Puchong Selangor';
                                })
                                ->visible(fn(callable $get) => $get('installation_type') === 'self_pick_up'),
                            Grid::make(3)
                                ->schema([
                                    TextInput::make('category2.pic_name')
                                        ->label('Name')
                                        ->required()
                                        ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                        ->afterStateHydrated(fn($state) => $state ? Str::upper($state) : $state)
                                        ->afterStateUpdated(fn($state) => $state ? Str::upper($state) : $state)
                                        ->default(function (?HardwareHandoverV2 $record = null) {
                                            if ($record && $record->category2) {
                                                $category2 = is_string($record->category2) ? json_decode($record->category2, true) : $record->category2;
                                                if (isset($category2['pic_name']) && !empty($category2['pic_name'])) {
                                                    return $category2['pic_name'];
                                                }
                                            }
                                            return $record?->lead?->companyDetail->name ?? $record?->lead?->name;
                                        })
                                        ->visible(fn(callable $get) => $get('installation_type') === 'external_installation'),

                                    TextInput::make('category2.pic_phone')
                                        ->label('HP Number')
                                        ->tel()
                                        ->required()
                                        ->default(function (?HardwareHandoverV2 $record = null) {
                                            if ($record && $record->category2) {
                                                $category2 = is_string($record->category2) ? json_decode($record->category2, true) : $record->category2;
                                                if (isset($category2['pic_phone']) && !empty($category2['pic_phone'])) {
                                                    return $category2['pic_phone'];
                                                }
                                            }
                                            return $record?->lead?->companyDetail->contact_no ?? $record?->lead?->contact_no;
                                        })
                                        ->visible(fn(callable $get) => $get('installation_type') === 'external_installation'),

                                    TextInput::make('category2.email')
                                        ->label('Email Address')
                                        ->required()
                                        ->email()
                                        ->default(function (?HardwareHandoverV2 $record = null) {
                                            if ($record && $record->category2) {
                                                $category2 = is_string($record->category2) ? json_decode($record->category2, true) : $record->category2;
                                                if (isset($category2['email']) && !empty($category2['email'])) {
                                                    return $category2['email'];
                                                }
                                            }
                                            return $record?->lead?->companyDetail->email ?? $record?->lead?->email;
                                        })
                                        ->visible(fn(callable $get) => $get('installation_type') === 'external_installation'),
                                ]),
                        ]),
                ]),

            Section::make('Step 6: Remark Details')
                ->schema([
                    Textarea::make('remarks')
                        ->label('Remarks')
                        ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                        ->afterStateHydrated(fn($state) => Str::upper($state))
                        ->afterStateUpdated(fn($state) => Str::upper($state))
                        ->placeholder('Enter remark here')
                        ->autosize()
                        ->rows(3)
                        ->default(function (?HardwareHandoverV2 $record = null) {
                            return $record?->remarks ?? '';
                        }),
                ]),

            Section::make('Step 7: Video Details')
                ->schema([
                    FileUpload::make('video_files')
                        ->label('Upload Videos (MP4, MOV, AVI)')
                        ->disk('public')
                        ->directory('handovers/videos')
                        ->visibility('public')
                        ->multiple()
                        ->maxFiles(3)
                        ->maxSize(10000)
                        ->acceptedFileTypes([
                            'video/mp4',
                            'video/quicktime',
                            'video/x-msvideo',
                            'video/x-ms-wmv',
                            'video/webm'
                        ])
                        ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, ?HardwareHandoverV2 $record = null): string {
                            $leadId = $record?->lead_id ?? 0;
                            $year = now()->format('y');
                            $formattedId = sprintf('HW_%02d%04d', $year, $leadId);
                            $extension = $file->getClientOriginalExtension();
                            $timestamp = now()->format('YmdHis');
                            $random = rand(1000, 9999);

                            return "{$formattedId}-VIDEO-{$timestamp}-{$random}.{$extension}";
                        })
                        ->openable()
                        ->previewable(false)
                        ->downloadable()
                        ->default(function (?HardwareHandoverV2 $record = null) {
                            if (!$record || !$record->video_files) {
                                return [];
                            }
                            if (is_string($record->video_files)) {
                                return json_decode($record->video_files, true) ?? [];
                            }
                            return is_array($record->video_files) ? $record->video_files : [];
                        }),
                ]),

            Section::make('Step 8: Proforma Invoice')
                ->columnSpan(1)
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Select::make('proforma_invoice_product')
                                ->required()
                                ->label('Product')
                                ->options(function (?HardwareHandoverV2 $record = null) {
                                    $leadId = $record?->lead_id;
                                    if (!$leadId) return [];
                                    $currentRecordId = $record?->id;

                                    $usedPiIds = [];
                                    $hardwareHandovers = HardwareHandoverV2::where('lead_id', $leadId)
                                        ->when($currentRecordId, function ($query) use ($currentRecordId) {
                                            return $query->where('id', '!=', $currentRecordId);
                                        })
                                        ->get();

                                    foreach ($hardwareHandovers as $handover) {
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
                                        ->whereNotIn('id', array_filter($usedPiIds))
                                        ->whereNotNull('pi_reference_no')
                                        ->pluck('pi_reference_no', 'id')
                                        ->toArray();
                                })
                                ->multiple()
                                ->searchable()
                                ->default(function (?HardwareHandoverV2 $record = null) {
                                    if (!$record || !$record->proforma_invoice_product) {
                                        return [];
                                    }
                                    if (is_string($record->proforma_invoice_product)) {
                                        return json_decode($record->proforma_invoice_product, true) ?? [];
                                    }
                                    return is_array($record->proforma_invoice_product) ? $record->proforma_invoice_product : [];
                                })
                                ->preload(),

                            Select::make('proforma_invoice_hrdf')
                                ->label('HRDF')
                                ->options(function (?HardwareHandoverV2 $record = null) {
                                    $leadId = $record?->lead_id;
                                    if (!$leadId) return [];
                                    $currentRecordId = $record?->id;

                                    $usedPiIds = [];
                                    $hardwareHandovers = HardwareHandoverV2::where('lead_id', $leadId)
                                        ->when($currentRecordId, function ($query) use ($currentRecordId) {
                                            return $query->where('id', '!=', $currentRecordId);
                                        })
                                        ->get();

                                    foreach ($hardwareHandovers as $handover) {
                                        $piHrdf = $handover->proforma_invoice_hrdf;
                                        if (!empty($piHrdf)) {
                                            if (is_string($piHrdf)) {
                                                $piIds = json_decode($piHrdf, true);
                                                if (is_array($piIds)) {
                                                    $usedPiIds = array_merge($usedPiIds, $piIds);
                                                }
                                            } elseif (is_array($piHrdf)) {
                                                $usedPiIds = array_merge($usedPiIds, $piHrdf);
                                            }
                                        }
                                    }

                                    return \App\Models\Quotation::where('lead_id', $leadId)
                                        ->where('quotation_type', 'hrdf')
                                        ->where('status', \App\Enums\QuotationStatusEnum::accepted)
                                        ->whereNotIn('id', array_filter($usedPiIds))
                                        ->whereNotNull('pi_reference_no')
                                        ->pluck('pi_reference_no', 'id')
                                        ->toArray();
                                })
                                ->multiple()
                                ->searchable()
                                ->preload()
                                ->default(function (?HardwareHandoverV2 $record = null) {
                                    if (!$record || !$record->proforma_invoice_hrdf) {
                                        return [];
                                    }
                                    if (is_string($record->proforma_invoice_hrdf)) {
                                        return json_decode($record->proforma_invoice_hrdf, true) ?? [];
                                    }
                                    return is_array($record->proforma_invoice_hrdf) ? $record->proforma_invoice_hrdf : [];
                                }),
                        ])
                ]),

            Section::make('Step 9: Attachment')
                ->columnSpan(1)
                ->schema([
                    Grid::make(2)
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
                                ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, callable $get, ?HardwareHandoverV2 $record = null): string {
                                    $leadId = $record?->lead_id ?? 0;
                                    $year = now()->format('y');
                                    $formattedId = sprintf('HW_%02d%04d', $year, $leadId);
                                    $extension = $file->getClientOriginalExtension();
                                    $timestamp = now()->format('YmdHis');
                                    $random = rand(1000, 9999);

                                    return "{$formattedId}-CONFIRM-{$timestamp}-{$random}.{$extension}";
                                })
                                ->default(function (?HardwareHandoverV2 $record = null) {
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
                                ->live(debounce: 500)
                                ->directory('handovers/payment_slips')
                                ->visibility('public')
                                ->multiple()
                                ->maxFiles(1)
                                ->openable()
                                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                ->openable()
                                ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, callable $get, ?HardwareHandoverV2 $record = null): string {
                                    $leadId = $record?->lead_id ?? 0;
                                    $year = now()->format('y');
                                    $formattedId = sprintf('HW_%02d%04d', $year, $leadId);
                                    $extension = $file->getClientOriginalExtension();
                                    $timestamp = now()->format('YmdHis');
                                    $random = rand(1000, 9999);

                                    return "{$formattedId}-PAYMENT-{$timestamp}-{$random}.{$extension}";
                                })
                                ->default(function (?HardwareHandoverV2 $record = null) {
                                    if (!$record || !$record->payment_slip_file) {
                                        return [];
                                    }
                                    if (is_string($record->payment_slip_file)) {
                                        return json_decode($record->payment_slip_file, true) ?? [];
                                    }
                                    return is_array($record->payment_slip_file) ? $record->payment_slip_file : [];
                                }),

                            FileUpload::make('invoice_file')
                                ->label('Upload Invoice')
                                ->disk('public')
                                ->directory('handovers/invoices')
                                ->visibility('public')
                                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                ->multiple()
                                ->maxFiles(10)
                                ->helperText('Upload invoice files (PDF, JPG, PNG formats accepted)')
                                ->openable()
                                ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, callable $get): string {
                                    $companyName = Str::slug($get('company_name') ?? 'invoice');
                                    $date = now()->format('Y-m-d');
                                    $random = Str::random(5);
                                    $extension = $file->getClientOriginalExtension();

                                    return "{$companyName}-invoice-{$date}-{$random}.{$extension}";
                                })
                                ->default(function (?HardwareHandoverV2 $record = null) {
                                    if (!$record || !$record->invoice_file) {
                                        return [];
                                    }
                                    if (is_string($record->invoice_file)) {
                                        return json_decode($record->invoice_file, true) ?? [];
                                    }
                                    return is_array($record->invoice_file) ? $record->invoice_file : [];
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
                                ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, callable $get, ?HardwareHandoverV2 $record = null): string {
                                    $leadId = $record?->lead_id ?? 0;
                                    $year = now()->format('y');
                                    $formattedId = sprintf('HW_%02d%04d', $year, $leadId);
                                    $extension = $file->getClientOriginalExtension();
                                    $timestamp = now()->format('YmdHis');
                                    $random = rand(1000, 9999);

                                    return "{$formattedId}-HRDF-{$timestamp}-{$random}.{$extension}";
                                })
                                ->afterStateUpdated(function () {
                                    session()->forget('hrdf_upload_count');
                                })
                                ->default(function (?HardwareHandoverV2 $record = null) {
                                    if (!$record || !$record->hrdf_grant_file) {
                                        return [];
                                    }
                                    if (is_string($record->hrdf_grant_file)) {
                                        return json_decode($record->hrdf_grant_file, true) ?? [];
                                    }
                                    return is_array($record->hrdf_grant_file) ? $record->hrdf_grant_file : [];
                                }),


                        ])
                ]),
        ];
    }

    protected function processFormData(array $data): array
    {
        $contactFields = ['pic_name', 'pic_phone', 'pic_email'];
        $category2 = [];

        if (isset($data['category2']) && is_string($data['category2'])) {
            $category2 = json_decode($data['category2'], true) ?: [];
        } elseif (isset($data['category2']) && is_array($data['category2'])) {
            $category2 = $data['category2'];
        }

        foreach ($contactFields as $field) {
            if (isset($data[$field])) {
                $category2[$field] = $data[$field];
                unset($data[$field]);
            }
        }

        $data['category2'] = json_encode($category2);

        return $data;
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('300s')
            ->query($this->getOverdueHardwareHandovers())
            ->defaultSort('created_at', 'desc')
            ->emptyState(fn () => view('components.empty-state-question'))
            ->defaultPaginationPageOption(5)
            ->paginated([5])
            ->filters([
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
                            ->whereNot('id', 15)
                            ->whereNotNull('name')
                            ->pluck('name', 'name')
                            ->toArray();
                    })
                    ->placeholder('All Salesperson')
                    ->multiple(),

                SelectFilter::make('implementer')
                    ->label('Filter by Implementer')
                    ->options(function () {
                        return User::whereIn('role_id', ['4', '5'])
                            ->whereNotNull('name')
                            ->pluck('name', 'name')
                            ->toArray();
                    })
                    ->placeholder('All Implementers')
                    ->multiple(),
            ])
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->formatStateUsing(function ($state, HardwareHandoverV2 $record) {
                        if (!$state) {
                            return 'Unknown';
                        }

                        // For handover_pdf, extract filename
                        if ($record->handover_pdf) {
                            $filename = basename($record->handover_pdf, '.pdf');
                            return $filename;
                        }

                        // Format ID with HW_250 prefix and pad with zeros
                        return 'HW_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);
                    })
                    ->color('primary')
                    ->weight('bold')
                    ->action(
                        Action::make('viewHandoverDetails')
                            ->modalHeading(false)
                            ->modalWidth('4xl')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalContent(function (HardwareHandoverV2 $record): View {
                                return view('components.hardware-handover')
                                    ->with('extraAttributes', ['record' => $record]);
                            })
                    ),

                TextColumn::make('lead.salesperson')
                    ->label('SalesPerson')
                    ->getStateUsing(function (HardwareHandoverV2 $record) {
                        $lead = $record->lead;
                        if (!$lead) {
                            return '-';
                        }

                        $salespersonId = $lead->salesperson;
                        return User::find($salespersonId)?->name ?? '-';
                    })
                    ->visible(fn(): bool => auth()->user()->role_id !== 2),

                TextColumn::make('implementer')
                    ->label('Implementer')
                    ->visible(fn(): bool => auth()->user()->role_id !== 2),

                TextColumn::make('lead.companyDetail.company_name')
                    ->label('Company Name')
                    ->searchable()
                    ->formatStateUsing(function ($state, $record) {
                        $fullName = $state ?? 'N/A';
                        $shortened = strtoupper(Str::limit($fullName, 30, '...'));
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

                TextColumn::make('invoice_type')
                    ->label('Invoice Type')
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'single' => 'Single Invoice',
                        'combined' => 'Combined Invoice',
                        default => ucfirst($state ?? 'Unknown')
                    })
                    ->visible(fn(): bool => auth()->user()->role_id !== 2),

                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): HtmlString => match ($state) {
                        'New' => new HtmlString('<span style="background-color: #dbeafe; color: #1e40af; padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 500; text-transform: uppercase;">' . $state . '</span>'),
                        'Rejected' => new HtmlString('<span style="background-color: #fee2e2; color: #dc2626; padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 500; text-transform: uppercase;">' . $state . '</span>'),
                        'Draft' => new HtmlString('<span style="background-color: #f3f4f6; color: #6b7280; padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 500; text-transform: uppercase;">' . $state . '</span>'),
                        default => new HtmlString('<span style="padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 500; text-transform: uppercase;">' . $state . '</span>'),
                    }),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('view')
                        ->label('View')
                        ->icon('heroicon-o-eye')
                        ->color('secondary')
                        ->modalHeading(false)
                        ->modalWidth('6xl')
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        ->modalContent(function (HardwareHandoverV2 $record): View {
                            return view('components.hardware-handover')
                            ->with('extraAttributes', ['record' => $record]);
                        }),

                    Action::make('view_reason')
                        ->label('View Reason')
                        ->visible(fn (HardwareHandoverV2 $record): bool => $record->status === 'Rejected')
                        ->icon('heroicon-o-magnifying-glass-plus')
                        ->modalHeading('Change Request Reason')
                        ->modalContent(fn (HardwareHandoverV2 $record) => view('components.view-reason', [
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
                        ->visible(fn (HardwareHandoverV2 $record): bool => $record->status === 'Rejected')
                        ->action(function (HardwareHandoverV2 $record): void {
                            $record->update([
                                'status' => 'Draft'
                            ]);

                            Notification::make()
                                ->title('Handover converted to draft')
                                ->success()
                                ->send();
                        }),

                    Action::make('edit_hardware_handover')
                        ->modalHeading(fn (HardwareHandoverV2 $record): string => "Edit Hardware Handover {$record->formatted_handover_id}")
                        ->label('Edit Hardware Handover')
                        ->icon('heroicon-o-pencil')
                        ->color('warning')
                        ->modalSubmitActionLabel('Save')
                        ->visible(fn (HardwareHandoverV2 $record): bool => $record->status === 'Draft')
                        ->modalWidth(MaxWidth::FourExtraLarge)
                        ->slideOver()
                        ->form($this->getEditForm())
                        ->action(function (HardwareHandoverV2 $record, array $data): void {
                            $data = $this->processFormData($data);
                            $data['created_by'] = auth()->id();
                            $data['lead_id'] = $record->lead_id;
                            $data['status'] = 'New';
                            $data['submitted_at'] = now();

                            if(isset($data['contact_detail']) && is_array($data['contact_detail'])){
                                $data['contact_detail'] = json_encode($data['contact_detail']);
                            }
                            foreach (['confirmation_order_file', 'payment_slip_file', 'video_files', 'invoice_file', 'hrdf_grant_file'] as $field) {
                                if (isset($data[$field]) && is_array($data[$field])) {
                                    $data[$field] = json_encode($data[$field]);
                                }
                            }
                            $record->update($data);

                            app(\App\Http\Controllers\GenerateHardwareHandoverPdfController::class)->generateInBackground($record);

                            Notification::make()->title('Hardware handover updated and resubmitted successfully')->success()->send();
                        }),
                ])
                ->button()
                ->color('success')
                ->label('Actions')
            ]);
    }

    public function render()
    {
        return view('livewire.salesperson_dashboard.hardware-handover-rejected');
    }
}
