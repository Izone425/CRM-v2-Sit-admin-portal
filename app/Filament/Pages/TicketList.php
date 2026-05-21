<?php
namespace App\Filament\Pages;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketComment;
use App\Models\TicketLog;
use App\Models\TicketPriority;
use App\Models\TicketModule;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use App\Models\TicketingUser;
use App\Services\TicketNotificationService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TicketList extends Page implements HasTable, HasActions, HasForms
{
    use InteractsWithTable;
    use InteractsWithActions;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static ?string $navigationLabel = 'Tickets';
    protected static ?string $title = 'Ticket List';
    protected static ?int $navigationSort = 3;
    protected static string $view = 'filament.pages.ticket-list';

    public $selectedTicket = null;
    public $showTicketModal = false;
    public $newComment = '';
    public $attachments = [];
    public $activeTab = 'all';
    public string $ticketScope = 'all';
    public $showReopenModal = false;
    public $reopenComment = '';
    public $showImageModal = false;
    public $selectedImageUrl = null;
    public $showOnHoldModal = false;
    public $kivReason = '';
    public $internalComment = '';

    public function mount(): void
    {
        $this->form->fill();

        // Auto-open ticket modal from deep link (?ticket=TC-HR1-0145)
        $ticketId = request()->query('ticket');
        if ($ticketId) {
            $ticket = Ticket::where('ticket_id', $ticketId)->first();
            if ($ticket) {
                $this->viewTicket($ticket->id);
            }
        }
    }

    public function getFormStatePath(): ?string
    {
        return null;
    }

    // Switch tab methods
    public function setActiveTab(string $tab): void
    {
        $this->activeTab = in_array($tab, ['all', 'v1', 'v2'], true) ? $tab : 'all';
    }

    public function switchToV1(): void
    {
        $this->setActiveTab('v1');
    }

    public function switchToV2(): void
    {
        $this->setActiveTab('v2');
    }

    public function setTicketScope(string $scope): void
    {
        $this->ticketScope = in_array($scope, ['all', 'my'], true) ? $scope : 'all';
        $this->resetTable();
    }

    protected function currentTicketingUserId(): ?int
    {
        $email = auth()->user()?->email;
        if (!$email) {
            return null;
        }
        return TicketingUser::where('email', $email)->value('id');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Ticket::whereIn('product_id', [1, 2])
            )
            ->paginated([50])
            ->paginationPageOptions([50, 100])
            ->columns([
                Tables\Columns\TextColumn::make('ticket_id')
                    ->label('ID')
                    ->sortable()
                    ->searchable()
                    ->weight('bold')
                    ->color('primary'),

                Tables\Columns\TextColumn::make('requestor.name')
                    ->label('Requestor')
                    ->searchable()
                    ->sortable()
                    ->default('Unknown User')
                    ->formatStateUsing(fn ($state) => $state ?? 'Unknown User'),

                Tables\Columns\TextColumn::make('company_name')
                    ->label('Company')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => strtoupper($state)),

                Tables\Columns\TextColumn::make('product.name')
                    ->label('Product')
                    ->default('N/A'),

                Tables\Columns\TextColumn::make('module.name')
                    ->label('Module')
                    ->sortable()
                    ->badge()
                    ->default('N/A'),

                Tables\Columns\BadgeColumn::make('priority.name')
                    ->label('Priority')
                    ->colors([
                        'danger' => fn ($state) => str_contains(strtolower($state ?? ''), 'bug') || str_contains(strtolower($state ?? ''), 'software'),
                        'warning' => fn ($state) => str_contains(strtolower($state ?? ''), 'backend') || str_contains(strtolower($state ?? ''), 'assistance'),
                        'primary' => fn ($state) => str_contains(strtolower($state ?? ''), 'critical enhancement'),
                        'info' => fn ($state) => str_contains(strtolower($state ?? ''), 'paid') || str_contains(strtolower($state ?? ''), 'customization'),
                        'success' => fn ($state) => str_contains(strtolower($state ?? ''), 'non-critical'),
                    ])
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'gray' => 'New',
                        'warning' => 'In Progress',
                        'success' => 'Resolved',
                        'danger' => 'Closed',
                    ]),

                Tables\Columns\TextColumn::make('device_type')
                    ->label('Device')
                    ->badge()
                    ->color(fn ($state) => $state === 'Mobile' ? 'info' : 'gray'),

                Tables\Columns\TextColumn::make('zoho_id')
                    ->label('Zoho Ticket'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('d M Y, H:i'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('product_id')
                    ->label('Product')
                    ->options([
                        1 => 'Version 1',
                        2 => 'Version 2',
                    ]),

                Tables\Filters\SelectFilter::make('module_id')
                    ->label('Module')
                    ->options(
                        TicketModule::where('is_active', true)
                            ->whereIn('name', [
                                'PROFILE',
                                'ATTENDANCE',
                                'LEAVE',
                                'CLAIM',
                                'PAYROLL',
                                'APPRAISAL',
                                'HIRE',
                                'IOT'
                            ])
                            ->orderByRaw("FIELD(name, 'PROFILE', 'ATTENDANCE', 'LEAVE', 'CLAIM', 'PAYROLL', 'APPRAISAL', 'HIRE', 'IOT')")
                            ->pluck('name', 'id')
                            ->toArray()
                    ),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'New' => 'New',
                        'In Progress' => 'In Progress',
                        'Resolved' => 'Resolved',
                        'Closed' => 'Closed',
                    ]),

                Tables\Filters\SelectFilter::make('priority_id')
                    ->label('Priority')
                    ->options(
                        TicketPriority::where('is_active', true)
                            ->pluck('name', 'id')
                            ->toArray()
                    ),
            ])
            // ->actions([
            //     Tables\Actions\ActionGroup::make([
            //         Tables\Actions\Action::make('view')
            //             ->label('View')
            //             ->icon('heroicon-o-eye')
            //             ->action(fn (Ticket $record) => $this->viewTicket($record->id)),
            //     ])
            // ])
            ->recordAction('view')
            ->recordUrl(null)
            ->defaultSort('created_at', 'desc')
            ->poll('30s');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createTicket')
                ->label('Create Ticket')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->slideOver()
                ->modalWidth('3xl')
                ->closeModalByClickingAway(false)
                ->form([
                    Grid::make(3)
                        ->schema([
                            Select::make('priority_id')
                                ->label('Priority')
                                ->required()
                                ->options(function () {
                                    $authUser = auth()->user();
                                    $priorities = TicketPriority::where('is_active', true)
                                        ->orderBy('sort_order')
                                        ->orderBy('sort_order_suffix')
                                        ->get();

                                    // If user role_id is 2, exclude restricted priorities
                                    if ($authUser && $authUser->role_id == 2) {
                                        $restrictedPriorities = ['SOFTWARE BUGS', 'BACK END ASSISTANCE', 'PAID CUSTOMIZATION'];

                                        $priorities = $priorities->filter(function ($priority) use ($restrictedPriorities) {
                                            foreach ($restrictedPriorities as $restricted) {
                                                if (str_contains(strtoupper($priority->name), $restricted)) {
                                                    return false;
                                                }
                                            }
                                            return true;
                                        });
                                    }

                                    // Exclude priorities where sort_order_suffix ends with 'F'
                                    $priorities = $priorities->filter(function ($priority) {
                                        return !str_ends_with(strtoupper((string) $priority->sort_order_suffix), 'F');
                                    });

                                    return $priorities->mapWithKeys(function ($priority) {
                                        $label = 'P' . $priority->sort_order;
                                        if ($priority->sort_order_suffix) {
                                            $label .= $priority->sort_order_suffix;
                                        }
                                        $label .= ' - ' . $priority->name;
                                        return [$priority->id => $label];
                                    })->toArray();
                                })
                                ->live(),

                            Select::make('product_id')
                                ->label('Product')
                                ->required()
                                ->options([
                                    1 => 'TimeTec HR - Version 1',
                                    2 => 'TimeTec HR - Version 2',
                                ])
                                ->live()
                                ->afterStateUpdated(fn (callable $set) => $set('module_id', null)),

                            Select::make('module_id')
                                ->label('Module')
                                ->options(function (callable $get) {
                                    $productId = $get('product_id');

                                    if (!$productId) {
                                        return [];
                                    }

                                    return \Illuminate\Support\Facades\DB::connection('ticketingsystem_live')
                                        ->table('product_has_modules')
                                        ->join('modules', 'product_has_modules.module_id', '=', 'modules.id')
                                        ->where('product_has_modules.product_id', $productId)
                                        ->where('modules.is_active', true)
                                        ->orderBy('product_has_modules.id')
                                        ->pluck('modules.name', 'modules.id')
                                        ->toArray();
                                })
                                ->required()
                                ->disabled(fn (callable $get): bool => !$get('product_id'))
                                ->placeholder('Select a product first'),
                        ]),

                    Select::make('parent_ticket_id')
                        ->label('RFQ Customization Ticket')
                        ->searchable()
                        ->options(function () {
                            // Get P4A priority (RFQ Customization: sort_order=4, suffix=a)
                            $p4aPriority = TicketPriority::where('is_active', true)
                                ->where('sort_order', 4)
                                ->where('sort_order_suffix', 'a')
                                ->first();

                            if (!$p4aPriority) {
                                return [];
                            }

                            // Get all tickets with P4A priority
                            return Ticket::where('priority_id', $p4aPriority->id)
                                ->orderBy('created_at', 'desc')
                                ->get()
                                ->mapWithKeys(function ($ticket) {
                                    return [$ticket->id => $ticket->ticket_id . ' - ' . $ticket->title];
                                })
                                ->toArray();
                        })
                        ->getSearchResultsUsing(function (string $search) {
                            $p4aPriority = TicketPriority::where('is_active', true)
                                ->where('sort_order', 4)
                                ->where('sort_order_suffix', 'a')
                                ->first();

                            if (!$p4aPriority) {
                                return [];
                            }

                            return Ticket::where('priority_id', $p4aPriority->id)
                                ->where(function ($query) use ($search) {
                                    $query->where('ticket_id', 'like', "%{$search}%")
                                        ->orWhere('title', 'like', "%{$search}%");
                                })
                                ->orderBy('created_at', 'desc')
                                ->limit(50)
                                ->get()
                                ->mapWithKeys(function ($ticket) {
                                    return [$ticket->id => $ticket->ticket_id . ' - ' . $ticket->title];
                                })
                                ->toArray();
                        })
                        ->required(function (Get $get): bool {
                            $priorityId = $get('priority_id');
                            if (!$priorityId) return false;
                            $priority = TicketPriority::find($priorityId);
                            return $priority && $priority->sort_order == 4 && $priority->sort_order_suffix == 'b';
                        })
                        ->hidden(function (Get $get): bool {
                            $priorityId = $get('priority_id');
                            if (!$priorityId) return true;
                            $priority = TicketPriority::find($priorityId);
                            return !($priority && $priority->sort_order == 4 && $priority->sort_order_suffix == 'b');
                        })
                        ->columnSpanFull(),

                    // ✅ Update all other device-related fields to use hidden() consistently
                    Grid::make(3)
                        ->schema([
                            // ✅ Device Type field - now shows/hides based on priority
                            Select::make('device_type')
                                ->label('Device Type')
                                ->options([
                                    'Mobile' => 'Mobile',
                                    'Browser' => 'Browser',
                                ])
                                ->live()
                                ->required(function (Get $get): bool {
                                    // Required when priority is Back End Assistance
                                    $priorityId = $get('priority_id');
                                    if (!$priorityId) return false;

                                    $priority = TicketPriority::find($priorityId);
                                    return $priority && str_contains(strtolower($priority->name), 'back end assistance');
                                })
                                ->hidden(function (Get $get): bool {
                                    // Hide when NOT Back End Assistance priority
                                    $priorityId = $get('priority_id');
                                    if (!$priorityId) return true; // Hide when no priority selected

                                    $priority = TicketPriority::find($priorityId);
                                    return $priority && str_contains(strtolower($priority->name), 'back end assistance');
                                })
                                ->afterStateUpdated(function (callable $set, $state) {
                                    // Clear related fields when device type changes or is cleared
                                    if (!$state) {
                                        $set('mobile_type', null);
                                        $set('browser_type', null);
                                        $set('version_screenshot', null);
                                        // $set('device_id', null);
                                        // $set('os_version', null);
                                        // $set('app_version', null);
                                        // $set('windows_version', null);
                                    }
                                }),

                            Select::make('mobile_type')
                                ->label('Mobile Type')
                                ->options([
                                    'iOS' => 'iOS',
                                    'Android' => 'Android',
                                    'Huawei' => 'Huawei',
                                ])
                                ->hidden(function (Get $get): bool {
                                    // Hide if NOT (Software Bugs AND Mobile)
                                    $priorityId = $get('priority_id');
                                    if (!$priorityId) return true;

                                    $priority = TicketPriority::find($priorityId);
                                    $isSoftwareBugs = $priority && str_contains(strtolower($priority->name), 'software bugs');

                                    return !($isSoftwareBugs && $get('device_type') === 'Mobile');
                                })
                                ->required(function (Get $get): bool {
                                    $priorityId = $get('priority_id');
                                    if (!$priorityId) return false;

                                    $priority = TicketPriority::find($priorityId);
                                    $isSoftwareBugs = $priority && str_contains(strtolower($priority->name), 'software bugs');

                                    return $isSoftwareBugs && $get('device_type') === 'Mobile';
                                }),

                            FileUpload::make('version_screenshot')
                                ->label('Version Screenshot')
                                ->image()
                                ->maxSize(5120)
                                ->disk('s3-ticketing')
                                ->directory('version_screenshot')
                                ->visibility('private')
                                ->hidden(function (Get $get): bool {
                                    $priorityId = $get('priority_id');
                                    if (!$priorityId) return true;

                                    $priority = TicketPriority::find($priorityId);
                                    $isSoftwareBugs = $priority && str_contains(strtolower($priority->name), 'software bugs');

                                    return !($isSoftwareBugs && $get('device_type') === 'Mobile');
                                })
                                ->required(function (Get $get): bool {
                                    $priorityId = $get('priority_id');
                                    if (!$priorityId) return false;

                                    $priority = TicketPriority::find($priorityId);
                                    $isSoftwareBugs = $priority && str_contains(strtolower($priority->name), 'software bugs');

                                    return $isSoftwareBugs && $get('device_type') === 'Mobile';
                                }),

                            Select::make('browser_type')
                                ->label('Browser Type')
                                ->options([
                                    'Chrome' => 'Chrome',
                                    'Firefox' => 'Firefox',
                                    'Safari' => 'Safari',
                                    'Edge' => 'Edge',
                                    'Opera' => 'Opera',
                                ])
                                ->hidden(function (Get $get): bool {
                                    // Hide if NOT (Software Bugs AND Browser)
                                    $priorityId = $get('priority_id');
                                    if (!$priorityId) return true;

                                    $priority = TicketPriority::find($priorityId);
                                    $isSoftwareBugs = $priority && str_contains(strtolower($priority->name), 'software bugs');

                                    return !($isSoftwareBugs && $get('device_type') === 'Browser');
                                })
                                ->required(function (Get $get): bool {
                                    $priorityId = $get('priority_id');
                                    if (!$priorityId) return false;

                                    $priority = TicketPriority::find($priorityId);
                                    $isSoftwareBugs = $priority && str_contains(strtolower($priority->name), 'software bugs');

                                    return $isSoftwareBugs && $get('device_type') === 'Browser';
                                }),
                        ]),

                    Grid::make(2)
                        ->schema([


                            // TextInput::make('device_id')
                            //     ->label('Device ID')
                            //     ->placeholder('Enter device ID')
                            //     ->hidden(function (Get $get): bool {
                            //         $priorityId = $get('priority_id');
                            //         if (!$priorityId) return true;

                            //         $priority = TicketPriority::find($priorityId);
                            //         $isSoftwareBugs = $priority && str_contains(strtolower($priority->name), 'software bugs');

                            //         return !($isSoftwareBugs && $get('device_type') === 'Mobile');
                            //     })
                            //     ->required(function (Get $get): bool {
                            //         $priorityId = $get('priority_id');
                            //         if (!$priorityId) return false;

                            //         $priority = TicketPriority::find($priorityId);
                            //         $isSoftwareBugs = $priority && str_contains(strtolower($priority->name), 'software bugs');

                            //         return $isSoftwareBugs && $get('device_type') === 'Mobile';
                            //     }),
                        ]),

                    // ✅ Mobile version details (OS & App version)
                    // Grid::make(2)
                    //     ->schema([
                    //         TextInput::make('os_version')
                    //             ->label('OS Version')
                    //             ->placeholder('e.g., Android 14')
                    //             ->hidden(function (Get $get): bool {
                    //                 $priorityId = $get('priority_id');
                    //                 if (!$priorityId) return true;

                    //                 $priority = TicketPriority::find($priorityId);
                    //                 $isSoftwareBugs = $priority && str_contains(strtolower($priority->name), 'software bugs');

                    //                 return !($isSoftwareBugs && $get('device_type') === 'Mobile');
                    //             })
                    //             ->required(function (Get $get): bool {
                    //                 $priorityId = $get('priority_id');
                    //                 if (!$priorityId) return false;

                    //                 $priority = TicketPriority::find($priorityId);
                    //                 $isSoftwareBugs = $priority && str_contains(strtolower($priority->name), 'software bugs');

                    //                 return $isSoftwareBugs && $get('device_type') === 'Mobile';
                    //             }),

                    //         TextInput::make('app_version')
                    //             ->label('App Version')
                    //             ->placeholder('e.g., 1.2.3')
                    //             ->hidden(function (Get $get): bool {
                    //                 $priorityId = $get('priority_id');
                    //                 if (!$priorityId) return true;

                    //                 $priority = TicketPriority::find($priorityId);
                    //                 $isSoftwareBugs = $priority && str_contains(strtolower($priority->name), 'software bugs');

                    //                 return !($isSoftwareBugs && $get('device_type') === 'Mobile');
                    //             })
                    //             ->required(function (Get $get): bool {
                    //                 $priorityId = $get('priority_id');
                    //                 if (!$priorityId) return false;

                    //                 $priority = TicketPriority::find($priorityId);
                    //                 $isSoftwareBugs = $priority && str_contains(strtolower($priority->name), 'software bugs');

                    //                 return $isSoftwareBugs && $get('device_type') === 'Mobile';
                    //             }),
                    //     ]),

                    // Grid::make(2)
                    //     ->schema([
                    //         TextInput::make('windows_version')
                    //             ->label('Windows/OS Version')
                    //             ->placeholder('e.g., Windows 11, macOS 13.1 (optional)')
                    //             ->visible(fn (Get $get): bool => $get('device_type') === 'Browser')
                    //             ->columnSpan(1),
                    //     ])
                    //     ->visible(fn (Get $get): bool => $get('device_type') === 'Browser'),
                    Grid::make(3)
                        ->schema([
                            TextInput::make('zoho_id')
                                ->label('Zoho Ticket Number')
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
                                ->columnSpan(1),

                            Select::make('company_name')
                                ->label('Company Name')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->options(function () {
                                    return \Illuminate\Support\Facades\DB::connection('frontenddb')
                                        ->table('crm_expiring_license')
                                        ->select('f_company_name', 'f_created_time')
                                        ->groupBy('f_company_name', 'f_created_time')
                                        ->orderBy('f_company_name', 'asc')
                                        ->get()
                                        ->mapWithKeys(function ($company) {
                                            return [$company->f_company_name => strtoupper($company->f_company_name)];
                                        })
                                        ->toArray();
                                })
                                ->getSearchResultsUsing(function (string $search) {
                                    return \Illuminate\Support\Facades\DB::connection('frontenddb')
                                        ->table('crm_expiring_license')
                                        ->select('f_company_name', 'f_created_time')
                                        ->where('f_company_name', 'like', "%{$search}%")
                                        ->groupBy('f_company_name', 'f_created_time')
                                        ->orderBy('f_company_name', 'asc')
                                        ->limit(50)
                                        ->get()
                                        ->mapWithKeys(function ($company) {
                                            return [$company->f_company_name => strtoupper($company->f_company_name)];
                                        })
                                        ->toArray();
                                })
                                ->getOptionLabelUsing(function ($value) {
                                    return strtoupper($value);
                                })
                                ->columnSpan(2),
                        ]),

                    Grid::make(2)
                        ->schema([
                            FileUpload::make('invoice')
                                ->label('Invoice')
                                ->disk('s3-ticketing')
                                ->directory('ticket-attachments')
                                ->visibility('private')
                                ->acceptedFileTypes(['application/pdf', 'image/*'])
                                ->maxSize(10240)
                                ->required(function (Get $get): bool {
                                    $priorityId = $get('priority_id');
                                    if (!$priorityId) return false;
                                    $priority = TicketPriority::find($priorityId);
                                    return $priority && $priority->sort_order == 4 && $priority->sort_order_suffix == 'b';
                                })
                                ->hidden(function (Get $get): bool {
                                    $priorityId = $get('priority_id');
                                    if (!$priorityId) return true;
                                    $priority = TicketPriority::find($priorityId);
                                    return !($priority && $priority->sort_order == 4 && $priority->sort_order_suffix == 'b');
                                }),

                            FileUpload::make('payment_slip')
                                ->label('Payment Slip')
                                ->disk('s3-ticketing')
                                ->directory('ticket-attachments')
                                ->visibility('private')
                                ->acceptedFileTypes(['application/pdf', 'image/*'])
                                ->maxSize(10240)
                                ->required(function (Get $get): bool {
                                    $priorityId = $get('priority_id');
                                    if (!$priorityId) return false;
                                    $priority = TicketPriority::find($priorityId);
                                    return $priority && $priority->sort_order == 4 && $priority->sort_order_suffix == 'b';
                                })
                                ->hidden(function (Get $get): bool {
                                    $priorityId = $get('priority_id');
                                    if (!$priorityId) return true;
                                    $priority = TicketPriority::find($priorityId);
                                    return !($priority && $priority->sort_order == 4 && $priority->sort_order_suffix == 'b');
                                }),
                        ]),

                    Checkbox::make('is_internal')
                        ->label('Internal Ticket')
                        ->default(false)
                        ->hidden()
                        ->columnSpan(1),

                    TextInput::make('title')
                        ->label('Title')
                        ->required()
                        ->maxLength(255)
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
                        ->columnSpanFull(),

                    RichEditor::make('description')
                        ->label('Description')
                        ->required()
                        ->fileAttachmentsDisk('s3-ticketing')
                        ->fileAttachmentsDirectory('ticket_desc_images')
                        ->fileAttachmentsVisibility('private')
                        ->columnSpanFull(),
                ])
                ->action(function (array $data): void {
                    try {
                        $authUser = auth()->user();

                        $ticketSystemUser = null;
                        if ($authUser) {
                            $ticketSystemUser = \Illuminate\Support\Facades\DB::connection('ticketingsystem_live')
                                ->table('users')
                                ->where(function ($query) use ($authUser) {
                                    $query->where('name', $authUser->name)
                                        ->orWhere('name', 'LIKE', '%' . $authUser->name . '%')
                                        ->orWhere('email', $authUser->email);
                                })
                                ->first();
                        }

                        $requestorId = $ticketSystemUser?->id ?? 22;

                        $data['status'] = 'New';
                        $data['requestor_id'] = $requestorId;
                        $data['created_date'] = now()->subHours(8)->toDateString();
                        $data['isPassed'] = 0;
                        $data['is_internal'] = $data['is_internal'] ?? false;

                        $productCode = $data['product_id'] == 1 ? 'HR1' : 'HR2';

                        $lastTicket = Ticket::where('ticket_id', 'like', "TC-{$productCode}-%")
                            ->orderBy('id', 'desc')
                            ->first();

                        if ($lastTicket && $lastTicket->ticket_id) {
                            preg_match('/TC-' . $productCode . '-(\d+)/', $lastTicket->ticket_id, $matches);
                            $lastNumber = isset($matches[1]) ? (int)$matches[1] : 0;
                            $nextNumber = $lastNumber + 1;
                        } else {
                            $nextNumber = 1;
                        }

                        $data['ticket_id'] = sprintf('TC-%s-%04d', $productCode, $nextNumber);
                        $data['created_at'] = now()->subHours(8);
                        $data['updated_at'] = now()->subHours(8);

                        $ticket = Ticket::create($data);

                        // Handle invoice and payment slip uploads for P4B priority (Paid Customization)
                        $priority = TicketPriority::find($data['priority_id']);
                        if ($priority && $priority->sort_order == 4 && $priority->sort_order_suffix == 'b') {
                            // Handle invoice upload
                            if (!empty($data['invoice'])) {
                                $invoicePath = $data['invoice'];
                                $invoiceFile = \Illuminate\Support\Facades\Storage::disk('s3-ticketing');

                                TicketAttachment::create([
                                    'ticket_id' => $ticket->id,
                                    'type' => 'invoice',
                                    'original_filename' => basename($invoicePath),
                                    'stored_filename' => null,
                                    'file_path' => $invoicePath,
                                    'file_size' => $invoiceFile->size($invoicePath),
                                    'mime_type' => $invoiceFile->mimeType($invoicePath),
                                    'file_hash' => null,
                                    'uploaded_by' => $requestorId,
                                    'created_at' => now()->subHours(8),
                                    'updated_at' => now()->subHours(8),
                                ]);
                            }

                            // Handle payment slip upload
                            if (!empty($data['payment_slip'])) {
                                $paymentSlipPath = $data['payment_slip'];
                                $paymentSlipFile = \Illuminate\Support\Facades\Storage::disk('s3-ticketing');

                                TicketAttachment::create([
                                    'ticket_id' => $ticket->id,
                                    'type' => 'payment_slip',
                                    'original_filename' => basename($paymentSlipPath),
                                    'stored_filename' => null,
                                    'file_path' => $paymentSlipPath,
                                    'file_size' => $paymentSlipFile->size($paymentSlipPath),
                                    'mime_type' => $paymentSlipFile->mimeType($paymentSlipPath),
                                    'file_hash' => null,
                                    'uploaded_by' => $requestorId,
                                    'created_at' => now()->subHours(8),
                                    'updated_at' => now()->subHours(8),
                                ]);
                            }
                        }

                        // Get priority name for the log
                        $priorityName = $priority ? $priority->name : 'Unknown';

                        // Build detailed new_value string
                        $newValueDetails = "Ticket {$data['ticket_id']}\n";
                        $newValueDetails .= "Title: {$data['title']}\n";
                        $newValueDetails .= "Priority: {$priorityName}\n";
                        $newValueDetails .= "Category: {$priorityName}\n";
                        $newValueDetails .= "Requester: " . ($ticketSystemUser?->name ?? 'HRcrm User');

                        TicketLog::create([
                            'ticket_id' => $ticket->id,
                            'old_value' => 'No existing ticket',
                            'new_value' => $newValueDetails,
                            'action' => "Created new ticket {$data['ticket_id']}",
                            'field_name' => null,
                            'change_reason' => null,
                            'old_eta' => null,
                            'new_eta' => null,
                            'updated_by' => $requestorId,
                            'user_name' => $ticketSystemUser?->name ?? 'HRcrm User',
                            'user_role' => $ticketSystemUser?->role ?? 'Internal Staff',
                            'change_type' => 'ticket_creation',
                            'source' => 'crm',
                            'created_at' => now()->subHours(8),
                            'updated_at' => now()->subHours(8),
                        ]);

                        // Send email & in-app notifications based on product/module access
                        try {
                            $ticket->load('priority');
                            $actionBy = $ticketSystemUser
                                ? TicketingUser::find($ticketSystemUser->id)
                                : null;

                            $action = match ($priority?->name ?? '') {
                                'Software Bugs' => 'created_p1',
                                'Back End Assistance' => 'created_p2',
                                'RFQ Customization' => 'created_p4a',
                                default => 'created_p3_p5',
                            };

                            app(TicketNotificationService::class)->handleAction($action, $ticket, $actionBy);
                        } catch (\Exception $e) {
                            Log::error('Ticket notification failed: ' . $e->getMessage());
                        }

                        Notification::make()
                            ->title('Ticket Created')
                            ->success()
                            ->body("Ticket {$data['ticket_id']} (ID: #{$ticket->id}) has been created successfully.")
                            ->send();

                        $this->dispatch('$refresh');
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error')
                            ->danger()
                            ->body('Failed to create ticket: ' . $e->getMessage())
                            ->send();
                    }
                }),
        ];
    }

    public function viewTicket($ticketId): void
    {
        try {
            $this->selectedTicket = Ticket::with([
                'comments',
                'logs',
                'priority',
                'product',
                'module',
                'requestor',
                'attachments',
                'attachments.uploader',
            ])->find($ticketId);

            if ($this->selectedTicket) {
                $this->showTicketModal = true;
            }
        } catch (\Exception $e) {
            Log::error('Error viewing ticket: ' . $e->getMessage());
            $this->showTicketModal = false;
        }
    }

    public function closeTicketModal(): void
    {
        $this->showTicketModal = false;
        $this->selectedTicket = null;
        $this->newComment = '';
        $this->attachments = [];
        $this->showImageModal = false;
        $this->selectedImageUrl = null;
        $this->closeReopenModal();
        $this->closeOnHoldModal();
    }

    public function openOnHoldModal(): void
    {
        $this->kivReason = '';
        $this->showOnHoldModal = true;
    }

    public function closeOnHoldModal(): void
    {
        $this->showOnHoldModal = false;
        $this->kivReason = '';
    }

    public function markAsOnHold(): void
    {
        if (!$this->selectedTicket) {
            return;
        }

        if (empty($this->kivReason)) {
            \Filament\Notifications\Notification::make()
                ->title('On Hold Reason is required')
                ->danger()
                ->send();
            return;
        }

        $oldStatus = $this->selectedTicket->status;
        $decodedKivReason = html_entity_decode($this->kivReason);

        $this->selectedTicket->update([
            'status' => 'Pending RND',
            'kiv_reason' => $decodedKivReason,
            'pending_party' => 'Pending RND',
        ]);

        // Log the action
        $actionBy = auth()->user();
        $ticketSystemUser = null;
        if ($actionBy) {
            $ticketSystemUser = \Illuminate\Support\Facades\DB::connection('ticketingsystem_live')
                ->table('users')
                ->where('email', $actionBy->email)
                ->first();
        }
        $userId = $ticketSystemUser?->id ?? 22;

        \App\Models\TicketLog::create([
            'ticket_id' => $this->selectedTicket->id,
            'old_value' => $oldStatus,
            'new_value' => 'Pending RND',
            'action' => "Ticket {$this->selectedTicket->ticket_id} marked as Pending RND from '{$oldStatus}'. Reason: " . strip_tags($decodedKivReason),
            'field_name' => 'status',
            'change_reason' => $decodedKivReason,
            'updated_by' => $userId,
            'user_name' => $ticketSystemUser?->name ?? 'HRcrm User',
            'user_role' => $ticketSystemUser?->role ?? 'Support Staff',
            'change_type' => 'status_change',
            'source' => 'ticket_modal_on_hold',
        ]);

        // Create ticket comment with the kiv reason
        \App\Models\TicketComment::create([
            'ticket_id' => $this->selectedTicket->id,
            'user_id' => $userId,
            'comment' => '<p><strong>On Hold Reason:</strong></p>' . $decodedKivReason,
            'is_system' => true,
        ]);

        $this->selectedTicket = $this->selectedTicket->fresh(['logs', 'comments', 'attachments', 'priority', 'product', 'module', 'requestor']);

        $this->closeOnHoldModal();

        \Filament\Notifications\Notification::make()
            ->title('Ticket marked as On Hold')
            ->body("Ticket {$this->selectedTicket->ticket_id} has been marked as On Hold.")
            ->success()
            ->send();
    }

    public function openImageModal(string $url): void
    {
        $this->selectedImageUrl = $url;
        $this->showImageModal = true;
    }

    public function closeImageModal(): void
    {
        $this->showImageModal = false;
        $this->selectedImageUrl = null;
    }

    public function closeReopenModal(): void
    {
        $this->showReopenModal = false;
        $this->reopenComment = '';
        $this->form->fill();
    }

    public function openReopenModal($ticketId): void
    {
        $this->selectedTicket = Ticket::with([
            'comments',
            'logs',
            'priority',
            'product',
            'module',
            'requestor',
            'attachments',
            'attachments.uploader',
        ])->find($ticketId);

        if ($this->selectedTicket) {
            $this->showReopenModal = true;
        }
    }

    public function addComment(): void
    {
        if (empty($this->newComment) || !$this->selectedTicket) {
            return;
        }

        try {
            $authUser = auth()->user();

            $ticketSystemUser = null;
            if ($authUser) {
                $ticketSystemUser = \Illuminate\Support\Facades\DB::connection('ticketingsystem_live')
                    ->table('users')
                    ->where('email', $authUser->email)
                    ->first();
            }

            $userId = $ticketSystemUser?->id ?? 22;

            TicketComment::create([
                'ticket_id' => $this->selectedTicket->id,
                'user_id' => $userId,
                'comment' => $this->newComment,
                'created_at' => now()->subHours(8),
                'updated_at' => now()->subHours(8),
            ]);

            $this->newComment = '';

            $this->selectedTicket->refresh();
            $this->selectedTicket->load('comments');

            Notification::make()
                ->title('Comment Added')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Log::error('Error adding comment: ' . $e->getMessage());

            Notification::make()
                ->title('Error')
                ->danger()
                ->body('Failed to add comment')
                ->send();
        }
    }

    public function addInternalComment(): void
    {
        if (empty(trim(strip_tags($this->internalComment))) || !$this->selectedTicket) {
            return;
        }

        try {
            $authUser = auth()->user();
            $ticketSystemUser = null;
            if ($authUser) {
                $ticketSystemUser = \Illuminate\Support\Facades\DB::connection('ticketingsystem_live')
                    ->table('users')
                    ->where('email', $authUser->email)
                    ->first();
            }
            $userId = $ticketSystemUser?->id ?? 22;

            TicketComment::create([
                'ticket_id' => $this->selectedTicket->id,
                'user_id' => $userId,
                'comment' => $this->internalComment,
                'is_internal' => true,
            ]);

            $this->internalComment = '';
            $this->selectedTicket->refresh();
            $this->selectedTicket->load('comments');

            Notification::make()
                ->title('Internal Comment Added')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Log::error('Error adding internal comment: ' . $e->getMessage());

            Notification::make()
                ->title('Error')
                ->danger()
                ->body('Failed to add internal comment')
                ->send();
        }
    }

    public function uploadAttachments(): void
    {
        $this->validate([
            'attachments.*' => 'file|max:10240',
        ]);

        if (empty($this->attachments) || !$this->selectedTicket) {
            Notification::make()
                ->title('No files selected')
                ->warning()
                ->send();
            return;
        }

        try {
            $authUser = auth()->user();
            $ticketSystemUser = \Illuminate\Support\Facades\DB::connection('ticketingsystem_live')
                ->table('users')
                ->where('name', $authUser->name)
                ->first();

            $userId = $ticketSystemUser?->id ?? 22;

            foreach ($this->attachments as $file) {
                $originalFilename = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();

                $storedFilename = time() . '_' . \Illuminate\Support\Str::random(10) . '_' .
                                \Illuminate\Support\Str::slug(pathinfo($originalFilename, PATHINFO_FILENAME)) .
                                '.' . $extension;

                $path = $file->storeAs(
                    'ticket_attachments/' . date('Y/m/d'),
                    $storedFilename,
                    's3-ticketing'
                );

                $fileHash = hash_file('md5', $file->getRealPath());

                TicketAttachment::create([
                    'ticket_id' => $this->selectedTicket->id,
                    'original_filename' => $originalFilename,
                    'stored_filename' => $storedFilename,
                    'file_path' => $path,
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'file_hash' => $fileHash,
                    'uploaded_by' => $userId,
                ]);
            }

            $this->attachments = [];
            $this->selectedTicket->refresh();
            $this->selectedTicket->load('attachments');

            Notification::make()
                ->title('Files Uploaded')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Log::error('Error uploading attachments: ' . $e->getMessage());

            Notification::make()
                ->title('Upload Failed')
                ->danger()
                ->body('Failed to upload files: ' . $e->getMessage())
                ->send();
        }
    }

    protected function getForms(): array
    {
        return [
            'form' => $this->makeForm()
                ->schema([
                    RichEditor::make('newComment')
                        ->label('')
                        ->placeholder('Add a comment...')
                        ->required()
                        ->toolbarButtons([
                            'attachFiles',
                            'bold',
                            'italic',
                            'underline',
                            'strike',
                            'bulletList',
                            'orderedList',
                            'h2',
                            'h3',
                            'link',
                            'undo',
                            'redo',
                        ])
                        ->disableToolbarButtons([
                            'codeBlock',
                        ])
                        ->fileAttachmentsDisk('s3-ticketing')
                        ->fileAttachmentsDirectory('ticket_desc_images')
                        ->fileAttachmentsVisibility('public'),
                ]),
            'kivForm' => $this->makeForm()
                ->schema([
                    RichEditor::make('kivReason')
                        ->label('')
                        ->placeholder('Please provide a detailed reason for marking this ticket as On Hold...')
                        ->required()
                        ->toolbarButtons([
                            'bold',
                            'italic',
                            'underline',
                            'bulletList',
                            'orderedList',
                            'link',
                            'undo',
                            'redo',
                        ])
                        ->disableToolbarButtons([
                            'codeBlock',
                            'attachFiles',
                        ]),
                ]),
            'internalForm' => $this->makeForm()
                ->schema([
                    RichEditor::make('internalComment')
                        ->label('')
                        ->placeholder('Add an internal discussion comment...')
                        ->required()
                        ->toolbarButtons([
                            'attachFiles',
                            'bold',
                            'italic',
                            'underline',
                            'strike',
                            'bulletList',
                            'orderedList',
                            'h2',
                            'h3',
                            'link',
                            'undo',
                            'redo',
                        ])
                        ->disableToolbarButtons([
                            'codeBlock',
                        ])
                        ->fileAttachmentsDisk('s3-ticketing')
                        ->fileAttachmentsDirectory('ticket_desc_images')
                        ->fileAttachmentsVisibility('public'),
                ]),
        ];
    }

    private function isImageFile($attachment): bool
    {
        if (str_starts_with($attachment->mime_type, 'image/')) {
            return true;
        }
        $extension = strtolower(pathinfo($attachment->original_filename, PATHINFO_EXTENSION));
        return in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp']);
    }

    public function updateTicketStatus($ticketId, string $newStatus): void
    {
        try {
            $ticket = Ticket::findOrFail($ticketId);
            $authUser = auth()->user();

            $ticketSystemUser = null;
            if ($authUser) {
                $ticketSystemUser = \Illuminate\Support\Facades\DB::connection('ticketingsystem_live')
                    ->table('users')
                    ->where('email', $authUser->email)
                    ->first();
            }

            $userId = $ticketSystemUser?->id ?? 22;
            $oldStatus = $ticket->status;

            // Update ticket status
            $ticket->update(['status' => $newStatus]);

            // Log the status change
            TicketLog::create([
                'ticket_id' => $ticket->id,
                'old_value' => $oldStatus,
                'new_value' => $newStatus,
                'action' => "Changed status from '{$oldStatus}' to '{$newStatus}' for ticket {$ticket->ticket_id}.",
                'field_name' => 'status',
                'change_reason' => null,
                'old_eta' => null,
                'new_eta' => null,
                'updated_by' => $userId,
                'user_name' => $ticketSystemUser?->name ?? 'HRcrm User',
                'user_role' => $ticketSystemUser?->role ?? 'Support Staff',
                'change_type' => 'status_change',
                'source' => 'crm_modal',
                'created_at' => now()->subHours(8),
                'updated_at' => now()->subHours(8),
            ]);

            // Send notifications for status change
            try {
                $ticket->load('priority');
                $actionBy = $ticketSystemUser
                    ? TicketingUser::find($ticketSystemUser->id)
                    : null;

                $notifService = app(TicketNotificationService::class);
                $notifAction = $notifService->determineActionFromStatus($newStatus);

                if ($notifAction) {
                    $additionalData = [];
                    if ($newStatus === 'Closed System Configuration') {
                        $additionalData = ['rejector' => $authUser, 'rejected_at' => now(), 'rejection_reason' => 'System Configuration'];
                    } elseif ($newStatus === 'Closed with New CR') {
                        $additionalData = ['rejector' => $authUser, 'rejected_at' => now(), 'rejection_reason' => 'Change Request'];
                    }
                    $notifService->handleAction($notifAction, $ticket, $actionBy, $additionalData);
                }
            } catch (\Exception $e) {
                Log::error('Ticket status notification failed: ' . $e->getMessage());
            }

            // ✅ Refresh the selected ticket with fresh data including logs
            $this->selectedTicket = $ticket->fresh(['logs', 'comments', 'attachments', 'priority', 'product', 'module', 'requestor']);

            Notification::make()
                ->title('Status Updated')
                ->success()
                ->body("Ticket {$ticket->ticket_id} status changed from {$oldStatus} to {$newStatus}")
                ->send();

            // ✅ Dispatch event to refresh both V1 and V2 tables
            $this->dispatch('ticket-status-updated');

        } catch (\Exception $e) {
            Log::error('Error updating ticket status: ' . $e->getMessage());

            Notification::make()
                ->title('Error')
                ->danger()
                ->body('Failed to update ticket status: ' . $e->getMessage())
                ->send();
        }
    }

    public function reopenTicket(): void
    {
        if (!$this->selectedTicket) {
            return;
        }

        try {
            $authUser = auth()->user();
            $ticketSystemUser = null;
            if ($authUser) {
                $ticketSystemUser = \Illuminate\Support\Facades\DB::connection('ticketingsystem_live')
                    ->table('users')
                    ->where('email', $authUser->email)
                    ->first();
            }
            $userId = $ticketSystemUser?->id ?? 22;
            $oldStatus = $this->selectedTicket->status;

            // Update ticket status to Reopen
            $this->selectedTicket->update(['status' => 'Reopen']);

            // Add comment if provided
            $formData = $this->form->getState();
            if (!empty($formData['reopenComment'])) {
                TicketComment::create([
                    'ticket_id' => $this->selectedTicket->id,
                    'user_id' => $userId,
                    'comment' => $formData['reopenComment'],
                    'created_at' => now()->subHours(8),
                    'updated_at' => now()->subHours(8),
                ]);
            }

            // Log the status change
            TicketLog::create([
                'ticket_id' => $this->selectedTicket->id,
                'old_value' => $oldStatus,
                'new_value' => 'Reopen',
                'action' => "Ticket {$this->selectedTicket->ticket_id} was reopened from '{$oldStatus}' status." . (!empty($this->reopenComment) ? ' Comment: ' . $this->reopenComment : ''),
                'field_name' => 'status',
                'change_reason' => $this->reopenComment,
                'old_eta' => null,
                'new_eta' => null,
                'updated_by' => $userId,
                'user_name' => $ticketSystemUser?->name ?? 'HRcrm User',
                'user_role' => $ticketSystemUser?->role ?? 'Support Staff',
                'change_type' => 'status_change',
                'source' => 'crm_reopen',
                'created_at' => now()->subHours(8),
                'updated_at' => now()->subHours(8),
            ]);

            // Send reopen notification
            try {
                $this->selectedTicket->load('priority');
                $actionBy = $ticketSystemUser
                    ? TicketingUser::find($ticketSystemUser->id)
                    : null;

                app(TicketNotificationService::class)->handleAction('reopened', $this->selectedTicket, $actionBy, [
                    'reopener' => $authUser,
                    'reopened_at' => now(),
                    'reopen_reason' => $formData['reopenComment'] ?? 'Not specified',
                ]);
            } catch (\Exception $e) {
                Log::error('Ticket reopen notification failed: ' . $e->getMessage());
            }

            // Refresh the selected ticket with fresh data
            $this->selectedTicket = $this->selectedTicket->fresh(['logs', 'comments', 'attachments', 'priority', 'product', 'module', 'requestor']);

            // Close reopen modal
            $this->closeReopenModal();

            Notification::make()
                ->title('Ticket Reopen')
                ->success()
                ->body("Ticket {$this->selectedTicket->ticket_id} has been reopened successfully.")
                ->send();

            // Dispatch event to refresh tables
            $this->dispatch('ticket-status-updated');

        } catch (\Exception $e) {
            Log::error('Error reopening ticket: ' . $e->getMessage());

            Notification::make()
                ->title('Error')
                ->danger()
                ->body('Failed to reopen ticket: ' . $e->getMessage())
                ->send();
        }
    }
}
